'use strict';
let checkoutBarObserver;
function fitCheckoutBar() {
  checkoutBarObserver?.disconnect();
  const bar = document.querySelector('.barra-pago-carrito');
  if (!bar) return;
  const fit = () => document.documentElement.style.setProperty('--checkout-footer-height', `${Math.ceil(bar.getBoundingClientRect().height)}px`);
  fit();
  if (typeof ResizeObserver !== 'undefined') {
    checkoutBarObserver = new ResizeObserver(fit);
    checkoutBarObserver.observe(bar);
  }
}
function preservePosition(change) {
  const x = window.scrollX, y = window.scrollY;
  change();
  window.scrollTo({ left: x, top: y, behavior: 'instant' });
}
function initializeClientStore() {
fitCheckoutBar();
const dialog = document.querySelector('[data-product-dialog]');
if (dialog) {
  let previousFocus;
  const openProduct = (card) => {
    const content = dialog.querySelector('[data-product-content]');
    content.replaceChildren();
    const image = card.querySelector(':scope > img, :scope > .sin-foto-cliente')?.cloneNode(true);
    if (image) {
      if (image.tagName === 'IMG') {
        image.loading = 'eager';
        image.title = 'Selecciona la imagen para ampliar';
        image.addEventListener('click', () => image.classList.toggle('imagen-ampliada'));
      }
      content.append(image);
    }
    const info = card.querySelector('.producto-cliente-info').cloneNode(true);
    const title = info.querySelector('h2');
    title.textContent = card.querySelector('[data-product-open]').textContent;
    title.id = 'producto-detalle-titulo';
    const description = info.querySelector('details');
    if (description) description.open = true;
    content.append(info);
    const related = dialog.querySelector('[data-product-related]');
    related.replaceChildren();
    document.querySelectorAll('.catalogo-cliente [data-product-card]').forEach((other) => {
      if (other === card) return;
      const button = document.createElement('button');
      button.type = 'button';
      const thumb = other.querySelector(':scope > img')?.cloneNode(true);
      if (thumb) button.append(thumb);
      const name = document.createElement('span');
      name.textContent = other.querySelector('[data-product-open]').textContent;
      const price = document.createElement('strong');
      price.textContent = other.querySelector('.precio-cliente').textContent;
      button.append(name, price);
      button.addEventListener('click', () => { openProduct(other); dialog.scrollTop = 0; });
      related.append(button);
    });
    if (!dialog.open) { previousFocus = document.activeElement; dialog.showModal(); }
  };
  document.querySelectorAll('.catalogo-cliente [data-product-card]').forEach((card) => {
    card.querySelector('[data-product-open]').addEventListener('click', () => openProduct(card));
    const image = card.querySelector(':scope > img, :scope > .sin-foto-cliente');
    if (image) {
      image.style.cursor = 'zoom-in';
      image.addEventListener('click', () => openProduct(card));
    }
  });
  dialog.querySelector('[data-product-close]').addEventListener('click', () => dialog.close());
  dialog.addEventListener('close', () => previousFocus?.focus({ preventScroll: true }));
}
document.querySelector('[data-dismiss-cart-notice]')?.addEventListener('click', (event) => {
  event.target.closest('[data-cart-notice]').remove();
});
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
async function updateClientStore(url, options = {}, push = true, keepPosition = false) {
  if (clientLoading) return;
  clientLoading = true;
  const main = document.querySelector('main');
  const position = { x: window.scrollX, y: window.scrollY };
  const navigation = document.querySelector('.client-navigation, aside');
  const nav = navigation?.querySelector('nav');
  const navScroll = nav?.scrollLeft ?? 0;
  main.setAttribute('aria-busy', 'true');
  try {
    const response = await fetch(url, { credentials: 'same-origin', ...options });
    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
    const nextMain = page.querySelector('main');
    if (!response.ok || !nextMain || !page.body.classList.contains('tienda-cliente')) {
      if (response.redirected) { location.assign(response.url); return; }
      throw new Error('No se pudo actualizar la tienda.');
    }
    main.replaceWith(nextMain);
    document.body.classList.toggle('pagina-carrito', page.body.classList.contains('pagina-carrito'));
    const nextNav = page.querySelector('.client-navigation nav, aside nav');
    if (nav && nextNav) {
      nav.replaceWith(nextNav);
      nextNav.scrollLeft = navScroll;
    }
    document.title = page.title;
    initializeClientStore();
    if (push) history.pushState(null, '', response.url);
    window.scrollTo({ left: keepPosition ? position.x : 0, top: keepPosition ? position.y : 0, behavior: 'instant' });
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
    document.querySelector('main')?.removeAttribute('aria-busy');
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
  const keepPosition = post && data.get('cart_action') !== 'checkout';
  try { await updateClientStore(url, post ? { method: 'POST', body: data } : {}, true, keepPosition); }
  finally { if (button) button.disabled = false; }
});
window.addEventListener('popstate', () => {
  const url = new URL(location.href);
  if (clientStoreUrl(url)) updateClientStore(url, {}, false);
  else location.reload();
});
