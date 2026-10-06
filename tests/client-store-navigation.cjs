const vm = require('node:vm');
const fs = require('node:fs');
const assert = require('node:assert/strict');

// Exercise the actual submit handler with the shared menu and no old sidebar.
const handlers = {};
let fetches = 0, replacements = 0, fail = false, hasMenu = true;
let pageIsCart = false, cartLayout = false;
let lastScroll;
const nextNav = {scrollLeft: 0};
const nav = {scrollLeft: 37, replaceWith(value) { assert.equal(value, nextNav); }};
const navigation = {querySelector() { return nav; }};
const makeMain = () => ({style: {}, getBoundingClientRect() {return {height: 400};},
  setAttribute() {}, removeAttribute() {}, querySelector() {return null;}, append() {},
  replaceWith(value) {main = value; replacements++;}});
let main = makeMain();
const context = {
  URL, URLSearchParams,
  location: {origin: 'http://localhost', pathname: '/index.php', href: 'http://localhost/index.php?section=tienda'},
  history: {pushState() {}},
  window: {scrollX: 0, scrollY: 200, scrollTo(position) {lastScroll = position;}, addEventListener() {}},
  document: {
    body: {classList: {toggle(name, active) {assert.equal(name, 'pagina-carrito'); cartLayout = active;}}},
    querySelector(s) {return s === 'main' ? main : s === '.client-navigation, aside' && hasMenu ? navigation : null;},
    querySelectorAll() {return [];}, addEventListener(type, fn) {handlers[type] = fn;},
    createElement() {return {dataset: {}, setAttribute() {}};}
  },
  FormData: class {constructor(form) {this.product = form.product; this.action = form.cartAction || 'add';} get(key) {return key === 'cart_action' ? this.action : null;}},
  fetch: async (url, options) => {
    fetches++;
    if (fail) throw new Error('offline');
    if (options.method) {
      assert.equal(options.method, 'POST');
      assert(['add', 'checkout'].includes(options.body.action));
      assert.equal(options.body.product, 7);
    }
    return {ok: true, url: String(url), text: async () => '<html></html>'};
  },
  DOMParser: class {parseFromString() {return {title: 'Tienda', body: {classList: {contains(name) {return name === 'tienda-cliente' || name === 'pagina-carrito' && pageIsCart;}}},
    querySelector(s) {return s === 'main' ? makeMain() : s === '.client-navigation nav, aside nav' ? nextNav : null;}};}}
};
vm.runInNewContext(fs.readFileSync('public/assets/js/cliente-tienda.js', 'utf8'), context);
async function add() {
  const button = {disabled: false}; let prevented = false;
  await handlers.submit({target: {action: 'http://localhost/index.php?section=tienda', method: 'post', product: 7},
    submitter: button, preventDefault() {prevented = true;}});
  assert(prevented); assert.equal(button.disabled, false);
}
(async () => {
  await add(); await add();
  assert.equal(lastScroll.top, 200);
  assert.equal(main.style.minHeight, undefined);
  assert.equal(fetches, 2); assert.equal(replacements, 2); assert.equal(nextNav.scrollLeft, 37);
  hasMenu = false; await add(); assert.equal(replacements, 3);
  fail = true; await add(); fail = false; await add();
  assert.equal(fetches, 5); assert.equal(replacements, 4);
  assert.equal(cartLayout, false);
  pageIsCart = true; await add(); assert.equal(cartLayout, true);
  pageIsCart = false; await add(); assert.equal(cartLayout, false);
  await context.updateClientStore(new URL('http://localhost/index.php?section=carrito&paso=pago'));
  assert.equal(lastScroll.top, 0);
  await context.updateClientStore(new URL('http://localhost/index.php?section=tienda&page=2'));
  assert.equal(lastScroll.top, 0); assert.equal(main.style.minHeight, undefined);
  await handlers.submit({target: {action: 'http://localhost/index.php?section=carrito', method: 'post', product: 7, cartAction: 'checkout'}, submitter: {disabled: false}, preventDefault() {}});
  assert.equal(lastScroll.top, 0);
  console.log('OK: añadir desde catálogo o detalle, repetir, menú compartido/ausente y recuperación de error.');
})().catch(error => {console.error(error); process.exitCode = 1;});
