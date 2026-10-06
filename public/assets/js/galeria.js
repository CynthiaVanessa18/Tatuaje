(() => {
    const dialog = document.querySelector('#galleryLightbox');

    if (!(dialog instanceof HTMLDialogElement)) {
        return;
    }

    const image = dialog.querySelector('[data-lightbox-image]');
    const title = dialog.querySelector('[data-lightbox-title]');
    const category = dialog.querySelector('[data-lightbox-category]');
    const description = dialog.querySelector('[data-lightbox-description]');
    const artist = dialog.querySelector('[data-lightbox-artist]');
    const date = dialog.querySelector('[data-lightbox-date]');
    const closeButton = dialog.querySelector('[data-gallery-close]');

    document.querySelectorAll('[data-gallery-open]').forEach((button) => {
        button.addEventListener('click', () => {
            if (image instanceof HTMLImageElement) {
                image.src = button.dataset.image || '';
                image.alt = button.dataset.title || 'Obra de la galería';
            }

            if (title) title.textContent = button.dataset.title || '';
            if (category) category.textContent = button.dataset.category || '';
            if (description) description.textContent = button.dataset.description || '';
            if (artist) artist.textContent = button.dataset.artist || '';
            if (date) date.textContent = button.dataset.date || '';

            dialog.showModal();
            document.body.classList.add('has-dialog');
        });
    });

    const close = () => {
        dialog.close();
        document.body.classList.remove('has-dialog');

        if (image instanceof HTMLImageElement) {
            image.removeAttribute('src');
        }
    };

    closeButton?.addEventListener('click', close);

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            close();
        }
    });

    dialog.addEventListener('close', () => {
        document.body.classList.remove('has-dialog');
    });
})();
