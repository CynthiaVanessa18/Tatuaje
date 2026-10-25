(() => {
    const dialog = document.getElementById('artist-rating-detail');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    dialog.removeAttribute('open');
    dialog.showModal();
    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        window.location.href = dialog.querySelector('[aria-label="Cerrar detalle del artista"]').href;
    });
})();
