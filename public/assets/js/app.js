'use strict';
document.addEventListener('submit', (event) => {
  const form = event.target;
  if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
    event.preventDefault();
    return;
  }
  if (form.method.toLowerCase() === 'post') {
    const button = form.querySelector('button');
    if (button) { button.disabled = true; button.textContent = 'Procesando…'; }
  }
});
