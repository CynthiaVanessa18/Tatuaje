'use strict';
const accountRole = document.querySelector('[data-account-role]');
if (accountRole) {
  const updateAccountFields = () => {
    const client = accountRole.value === 'cliente';
    const fields = document.querySelector('[data-client-account-fields]');
    fields.hidden = !client;
    fields.querySelectorAll('[data-client-required]').forEach((input) => { input.required = client; });
  };
  accountRole.addEventListener('change', updateAccountFields);
  updateAccountFields();
}
const storeModules = new Set(['productos', 'ventas', 'categorias_productos', 'imagenes', 'detalle', 'promociones_tienda', 'grupos_clientes', 'clientes_grupos']);
function initializePromotionForms() {
  document.querySelectorAll('[data-promotion-form]').forEach((form) => {
    const scope = form.querySelector('[name=alcance]').value;
    const audience = form.querySelector('[name=publico]').value;
    const minimum = form.querySelector('[name=regla_minimo]').value;
    form.querySelectorAll('[data-promotion-scope], [data-promotion-audience], [data-promotion-minimum]').forEach((section) => {
      const visible = section.hasAttribute('data-promotion-scope') ? section.dataset.promotionScope === scope :
        section.hasAttribute('data-promotion-audience') ? audience === 'grupo' : minimum === 'minimo';
      section.hidden = !visible;
      section.querySelectorAll('input, select').forEach((input) => { input.disabled = !visible; });
    });
    form.querySelector('[name=id_grupo]').required = audience === 'grupo';
    form.querySelector('[name=minimo_compra]').required = minimum === 'minimo';
  });
}
document.addEventListener('change', (event) => {
  if (event.target.closest('[data-promotion-form]')) initializePromotionForms();
});
document.addEventListener('input', (event) => {
  if (!event.target.matches('[data-promotion-search]')) return;
  const search = event.target.value.toLocaleLowerCase();
  event.target.closest('fieldset').querySelectorAll('.promotion-choice').forEach((choice) => {
    choice.hidden = !choice.textContent.toLocaleLowerCase().includes(search);
  });
});
initializePromotionForms();
let storeLoading = false;
function confirmAction(message) {
  return new Promise((resolve) => {
    const dialog = document.createElement('dialog');
    dialog.className = 'confirmation-dialog';
    const title = document.createElement('h2');
    title.id = 'confirmation-title';
    title.textContent = 'Confirmar eliminación';
    const description = document.createElement('p');
    description.textContent = message;
    const actions = document.createElement('div');
    actions.className = 'actions';
    const cancel = document.createElement('button');
    cancel.type = 'button';
    cancel.className = 'secondary';
    cancel.textContent = 'Cancelar';
    const confirm = document.createElement('button');
    confirm.type = 'button';
    confirm.textContent = 'Eliminar';
    actions.append(cancel, confirm);
    dialog.append(title, description, actions);
    dialog.setAttribute('aria-labelledby', title.id);
    const previousFocus = document.activeElement;
    function finish(accepted) {
      dialog.close();
      dialog.remove();
      previousFocus?.focus({ preventScroll: true });
      resolve(accepted);
    }
    cancel.addEventListener('click', () => finish(false));
    confirm.addEventListener('click', () => finish(true));
    dialog.addEventListener('cancel', (event) => { event.preventDefault(); finish(false); });
    document.body.append(dialog);
    dialog.showModal();
    cancel.focus({ preventScroll: true });
  });
}
function isStoreUrl(url) {
  return document.body.classList.contains('pagina-tienda') && url.origin === location.origin &&
    url.pathname === location.pathname && storeModules.has(url.searchParams.get('module'));
}
async function loadStore(url, options = {}, pushHistory = true) {
  if (storeLoading) return;
  storeLoading = true;
  const main = document.querySelector('main');
  const position = { x: window.scrollX, y: window.scrollY };
  const oldHeight = main.getBoundingClientRect().height;
  const sidebar = document.querySelector('aside');
  const sidebarScroll = sidebar.scrollTop;
  main.setAttribute('aria-busy', 'true');
  try {
    const response = await fetch(url, { credentials: 'same-origin', ...options });
    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
    const nextMain = page.querySelector('main');
    if (!response.ok || !nextMain || !page.body.classList.contains('pagina-tienda')) {
      if (response.redirected) { location.assign(response.url); return; }
      throw new Error('No se pudo cargar la sección.');
    }
    nextMain.style.minHeight = `${oldHeight}px`;
    main.replaceWith(nextMain);
    initializePromotionForms();
    const nextNav = page.querySelector('aside nav');
    if (nextNav) sidebar.querySelector('nav').replaceWith(nextNav);
    sidebar.scrollTop = sidebarScroll;
    document.title = page.title;
    if (pushHistory) history.pushState(null, '', response.url);
    window.scrollTo({ left: position.x, top: position.y, behavior: 'instant' });
  } catch (error) {
    const notice = document.createElement('p');
    notice.className = 'notice error';
    notice.setAttribute('role', 'alert');
    notice.textContent = 'No se pudo actualizar la pantalla. Revisa la conexión y vuelve a intentarlo.';
    main.prepend(notice);
  } finally {
    main.removeAttribute('aria-busy');
    storeLoading = false;
  }
}
document.addEventListener('click', (event) => {
  const link = event.target.closest('a[href]');
  if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey ||
      event.shiftKey || event.altKey || link.target || link.hasAttribute('download')) return;
  const url = new URL(link.href);
  if (!isStoreUrl(url)) return;
  event.preventDefault();
  loadStore(url);
});
document.addEventListener('submit', async (event) => {
  const form = event.target;
  if (form.dataset.confirm && !form.dataset.confirmed) {
    event.preventDefault();
    if (form.dataset.confirming) return;
    form.dataset.confirming = 'true';
    const submitter = event.submitter;
    const accepted = await confirmAction(form.dataset.confirm);
    delete form.dataset.confirming;
    if (accepted && form.isConnected) {
      form.dataset.confirmed = 'true';
      try { form.requestSubmit(submitter || undefined); }
      finally { delete form.dataset.confirmed; }
    }
    return;
  }
  const url = new URL(form.action);
  const method = form.method.toLowerCase();
  const data = new FormData(form);
  if (method === 'get') url.search = new URLSearchParams(data).toString();
  if (isStoreUrl(url)) {
    event.preventDefault();
    if (storeLoading) return;
    const button = event.submitter || form.querySelector('button');
    if (button) button.disabled = true;
    try {
      await loadStore(url, method === 'post' ? { method: 'POST', body: data } : {});
    } finally {
      if (button) button.disabled = false;
    }
    return;
  }
  if (method === 'post') {
    const button = event.submitter;
    if (button) button.textContent = 'Procesando…';
  }
});
window.addEventListener('popstate', () => {
  const url = new URL(location.href);
  if (isStoreUrl(url)) loadStore(url, {}, false);
  else location.reload();
});

// Abre la configuración del plan elegido desde la tabla comparativa.
document.addEventListener('click', event => {
    const link = event.target.closest('a[href^="#configurar-membresia-"]');
    if (!link) return;
    const section = document.getElementById(link.getAttribute('href').slice(1));
    const details = section?.querySelector('details');
    if (details) details.open = true;
});

document.addEventListener('change', event => {
    const toggle = event.target.closest('[data-membership-toggle]');
    if (!toggle) return;
    const row = toggle.closest('[data-membership-rule]');
    row.classList.toggle('rule-selected', toggle.checked);
    const value = row.querySelector('[data-membership-value]');
    if (value) {
        value.disabled = !toggle.checked;
        value.required = toggle.checked;
    }
});
document.addEventListener('input', event => {
    const form = event.target.closest('[data-membership-editor]');
    if (!form) return;
    form.querySelector('[data-membership-save-status]').textContent = 'Tienes cambios sin guardar.';
});
