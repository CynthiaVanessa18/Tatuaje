'use strict';
function preservePosition(change) {
  const x = window.scrollX, y = window.scrollY;
  const main = document.querySelector('main');
  main.style.minHeight = `${main.getBoundingClientRect().height}px`;
  change();
  window.scrollTo({ left: x, top: y, behavior: 'instant' });
}
function initializeClientStore() {
const continuePayment = document.querySelector('[data-continue-payment]');
if (continuePayment) {
  const panel = document.querySelector('[data-payment-panel]');
  const back = document.querySelector('[data-back-cart]');
  const setStep = (paying) => {
    panel.hidden = !paying;
    continuePayment.hidden = paying;
    back.hidden = !paying;
    document.querySelectorAll('[data-cart-step]').forEach((step) => {
      if (step.dataset.cartStep === (paying ? '2' : '1')) step.setAttribute('aria-current', 'step');
      else step.removeAttribute('aria-current');
    });
  };
  setStep(false);
  continuePayment.addEventListener('click', () => preservePosition(() => setStep(true)));
  back.addEventListener('click', () => preservePosition(() => setStep(false)));
}
document.querySelectorAll('[data-quantity-change]').forEach((button) => {
  button.addEventListener('click', () => {
    const form = button.closest('form');
    const input = form.querySelector('[name=quantity]');
    const quantity = Number(input.value) + Number(button.dataset.quantityChange);
    if (quantity < Number(input.min) || quantity > Number(input.max)) return;
    input.value = quantity;
    form.requestSubmit(form.querySelector('[data-update-quantity]'));
  });
});
document.querySelectorAll('[data-update-quantity]').forEach((button) => {
  const input = button.form.querySelector('[name=quantity]');
  button.hidden = true;
  input.addEventListener('change', () => {
    if (input.checkValidity()) button.form.requestSubmit(button);
  });
});
const payment = document.querySelector('[data-payment-method]');
if (payment) {
  const update = () => {
    document.querySelector('[data-demo-card]').hidden = payment.value !== 'pasarela';
    const messages = {
      efectivo: 'Paga en efectivo al retirar tus artículos en el estudio.',
      transferencia: 'Coordina los datos de transferencia con el estudio. El pedido quedará pendiente de verificar el pago.',
      pasarela: 'Pago simulado: no se realizará ningún cobro real.'
    };
    document.querySelector('[data-payment-help]').textContent = messages[payment.value] || 'Selecciona cómo deseas pagar.';
  };
  payment.addEventListener('change', () => preservePosition(update));
  update();
}
const choices = document.querySelectorAll('[data-payment-choice]');
if (choices.length) {
  const updateChoice = () => {
    const selected = document.querySelector('[data-payment-choice]:checked');
    document.querySelector('[data-demo-card]').hidden = selected?.value !== 'pasarela';
    const messages = { pasarela: 'Demostración: el pago se simula y no se realiza ningún cobro real.', efectivo: 'Tu pedido quedará reservado. Paga en efectivo al retirar en el estudio.', transferencia: 'Coordina la transferencia con el estudio; el pago quedará pendiente de verificación.' };
    document.querySelector('[data-payment-help]').textContent = messages[selected?.value] || 'Selecciona una opción para continuar.';
  };
  choices.forEach((choice) => choice.addEventListener('change', () => preservePosition(updateChoice)));
  updateChoice();
}
}
initializeClientStore();

let clientLoading = false;
function clientStoreUrl(url) {
  return url.origin === location.origin && url.pathname === location.pathname &&
    ['tienda', 'carrito'].includes(url.searchParams.get('section'));
}
async function updateClientStore(url, options = {}, push = true) {
  if (clientLoading) return;
  clientLoading = true;
  const main = document.querySelector('main');
  const position = { x: window.scrollX, y: window.scrollY };
  const height = main.getBoundingClientRect().height;
  const sidebar = document.querySelector('aside');
  const sidebarScroll = sidebar.scrollTop;
  main.setAttribute('aria-busy', 'true');
  try {
    const response = await fetch(url, { credentials: 'same-origin', ...options });
    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
    const nextMain = page.querySelector('main');
    if (!response.ok || !nextMain || !page.body.classList.contains('tienda-cliente')) {
      if (response.redirected) { location.assign(response.url); return; }
      throw new Error('No se pudo actualizar la tienda.');
    }
    nextMain.style.minHeight = `${height}px`;
    main.replaceWith(nextMain);
    const nav = page.querySelector('aside nav');
    if (nav) sidebar.querySelector('nav').replaceWith(nav);
    sidebar.scrollTop = sidebarScroll;
    document.title = page.title;
    initializeClientStore();
    if (push) history.pushState(null, '', response.url);
    window.scrollTo({ left: position.x, top: position.y, behavior: 'instant' });
  } catch (error) {
    let notice = main.querySelector('[data-client-network-error]');
    if (!notice) {
      notice = document.createElement('p');
      notice.dataset.clientNetworkError = 'true';
      notice.className = 'notice error';
      notice.setAttribute('role', 'alert');
      main.append(notice);
    }
    notice.textContent = 'No se pudo actualizar. Revisa la conexión y vuelve a intentarlo.';
  } finally {
    main.removeAttribute('aria-busy');
    clientLoading = false;
  }
}
document.addEventListener('click', (event) => {
  const link = event.target.closest('a[href]');
  if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey ||
      event.shiftKey || event.altKey || link.target || link.hasAttribute('download')) return;
  const url = new URL(link.href);
  if (!clientStoreUrl(url)) return;
  event.preventDefault();
  updateClientStore(url);
});
document.addEventListener('submit', async (event) => {
  const form = event.target;
  const url = new URL(form.action);
  if (!clientStoreUrl(url)) return;
  event.preventDefault();
  if (clientLoading) return;
  // Include the clicked button: Actualizar and Quitar use different actions.
  const data = event.submitter ? new FormData(form, event.submitter) : new FormData(form);
  const post = form.method.toLowerCase() === 'post';
  if (!post) url.search = new URLSearchParams(data).toString();
  const button = event.submitter;
  if (button) button.disabled = true;
  try { await updateClientStore(url, post ? { method: 'POST', body: data } : {}); }
  finally { if (button) button.disabled = false; }
});
window.addEventListener('popstate', () => {
  const url = new URL(location.href);
  if (clientStoreUrl(url)) updateClientStore(url, {}, false);
  else location.reload();
});
