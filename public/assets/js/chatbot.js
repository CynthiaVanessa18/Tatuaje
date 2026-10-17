(() => {
    'use strict';

    const widget = document.querySelector('[data-chatbot]');
    if (!widget) return;

    const summon = widget.querySelector('.tattoo-oracle__summon');
    const panel = widget.querySelector('.tattoo-oracle__panel');
    const close = widget.querySelector('[data-chatbot-close]');
    const messages = widget.querySelector('[data-chatbot-messages]');
    const prompts = widget.querySelector('[data-chatbot-prompts]');
    const form = widget.querySelector('[data-chatbot-form]');
    const input = form.querySelector('textarea');
    const submit = form.querySelector('button[type="submit"]');
    const status = widget.querySelector('[data-chatbot-status]');
    let loaded = false;
    let sending = false;

    const request = async (url, options = {}) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            ...options,
        });
        const payload = await response.json().catch(() => ({ok: false, error: 'Respuesta inválida del servidor.'}));
        if (!response.ok || !payload.ok) throw new Error(payload.error || 'No fue posible completar la consulta.');
        return payload.data ?? payload;
    };

    const scrollToEnd = () => { messages.scrollTop = messages.scrollHeight; };

    const actionElement = (action) => {
        if (!action) return null;
        if (action.prompt) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'tattoo-oracle__action';
            button.textContent = action.label;
            button.addEventListener('click', () => send(action.prompt));
            return button;
        }
        const link = document.createElement('a');
        link.className = 'tattoo-oracle__action';
        link.textContent = `${action.label} ↗`;
        link.href = new URL(action.url, new URL(widget.dataset.base || './', window.location.href)).href;
        return link;
    };

    const addMessage = (message, action = null, welcome = false) => {
        const article = document.createElement('article');
        const role = welcome ? 'asistente' : message.rol;
        article.className = `tattoo-oracle__message tattoo-oracle__message--${role}`;
        if (welcome) article.classList.add('tattoo-oracle__message--welcome');

        const meta = document.createElement('small');
        meta.textContent = role === 'usuario' ? 'TÚ' : 'ORÁCULO';
        const text = document.createElement('p');
        text.textContent = welcome ? message : message.contenido;
        article.append(meta, text);

        const actionNode = actionElement(action);
        if (actionNode) article.append(actionNode);

        if (!welcome && role === 'asistente' && message.id_mensaje) {
            const feedback = document.createElement('div');
            feedback.className = 'tattoo-oracle__feedback';
            const label = document.createElement('span');
            label.textContent = '¿Te ayudó?';
            feedback.append(label);
            [['1', 'Sí'], ['0', 'No']].forEach(([value, textLabel]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = textLabel;
                if (message.fue_util !== null && Number(message.fue_util) === Number(value)) button.classList.add('is-selected');
                button.addEventListener('click', async () => {
                    try {
                        const body = new URLSearchParams({
                            csrf: widget.dataset.csrf,
                            id_mensaje: message.id_mensaje,
                            fue_util: value,
                        });
                        await request(widget.dataset.feedback, {method: 'POST', body});
                        feedback.querySelectorAll('button').forEach(item => item.classList.remove('is-selected'));
                        button.classList.add('is-selected');
                        label.textContent = 'Gracias';
                    } catch (error) {
                        status.textContent = error.message;
                    }
                });
                feedback.append(button);
            });
            article.append(feedback);
        }
        messages.append(article);
        scrollToEnd();
        return article;
    };

    const setTyping = (visible) => {
        widget.querySelector('.tattoo-oracle__typing')?.remove();
        if (!visible) return;
        const typing = document.createElement('div');
        typing.className = 'tattoo-oracle__typing';
        typing.innerHTML = '<i></i><i></i><i></i>';
        messages.append(typing);
        scrollToEnd();
    };

    const loadHistory = async () => {
        if (loaded) return;
        try {
            const data = await request(widget.dataset.history);
            messages.replaceChildren();
            addMessage(data.welcome, null, true);
            data.messages.forEach(message => addMessage(message));
            if (data.state === 'escalada') {
                status.textContent = 'Esta conversación está esperando atención del estudio.';
                widget.classList.add('is-escalated');
            }
            loaded = true;
        } catch (error) {
            messages.replaceChildren();
            const errorNode = document.createElement('p');
            errorNode.className = 'tattoo-oracle__error';
            errorNode.textContent = error.message;
            messages.append(errorNode);
        }
    };

    const openPanel = async () => {
        panel.hidden = false;
        summon.setAttribute('aria-expanded', 'true');
        widget.classList.add('is-open');
        await loadHistory();
        input.focus();
    };

    const closePanel = () => {
        panel.hidden = true;
        summon.setAttribute('aria-expanded', 'false');
        widget.classList.remove('is-open');
        summon.focus();
    };

    const send = async (value) => {
        const content = (value ?? input.value).trim();
        if (!content || sending) return;
        sending = true;
        submit.disabled = true;
        status.textContent = '';
        input.value = '';
        prompts.hidden = true;
        addMessage({rol: 'usuario', contenido: content});
        setTyping(true);
        try {
            const body = new URLSearchParams({csrf: widget.dataset.csrf, mensaje: content});
            const data = await request(widget.dataset.message, {method: 'POST', body});
            setTyping(false);
            addMessage(data.message, data.action);
            if (data.state === 'escalada') {
                widget.classList.add('is-escalated');
                status.textContent = 'Conversación enviada al estudio.';
            }
        } catch (error) {
            setTyping(false);
            status.textContent = error.message;
            input.value = content;
        } finally {
            sending = false;
            submit.disabled = false;
            input.focus();
        }
    };

    summon.addEventListener('click', () => panel.hidden ? openPanel() : closePanel());
    close.addEventListener('click', closePanel);
    form.addEventListener('submit', event => { event.preventDefault(); send(); });
    input.addEventListener('keydown', event => {
        if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); send(); }
    });
    prompts.addEventListener('click', event => {
        const button = event.target.closest('[data-prompt]');
        if (button) send(button.dataset.prompt);
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !panel.hidden) closePanel();
    });
})();
