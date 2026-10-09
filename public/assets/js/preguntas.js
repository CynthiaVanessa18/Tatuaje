(() => {
    const list = document.querySelector('[data-faq-list]');
    if (!list) return;

    const items = [...list.querySelectorAll('[data-faq-item]')];
    const search = document.querySelector('[data-faq-search]');
    const categoryButtons = [...document.querySelectorAll('[data-faq-category]')];
    const visibleCounter = document.querySelector('[data-faq-visible]');
    const emptyState = document.querySelector('[data-faq-empty]');
    let activeCategory = '';

    const normalize = (value) => value.toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g, '');

    const filter = () => {
        const query = normalize(search.value.trim());
        let visible = 0;

        items.forEach((item) => {
            const matchesCategory = activeCategory === '' || item.dataset.category === activeCategory;
            const matchesQuery = query === '' || normalize(item.textContent).includes(query);
            const show = matchesCategory && matchesQuery;
            item.hidden = !show;
            if (show) visible += 1;
        });

        visibleCounter.textContent = String(visible);
        emptyState.hidden = visible !== 0;
    };

    search.addEventListener('input', filter);
    categoryButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeCategory = button.dataset.faqCategory;
            categoryButtons.forEach((candidate) => candidate.setAttribute('aria-pressed', candidate === button ? 'true' : 'false'));
            filter();
        });
    });

    items.forEach((item) => {
        item.addEventListener('toggle', () => {
            if (!item.open) return;
            items.forEach((candidate) => {
                if (candidate !== item) candidate.open = false;
            });
        });
    });
})();
