(() => {
    const carousel = document.querySelector('[data-testimonial-carousel]');

    if (!carousel) {
        return;
    }

    const slides = [...carousel.querySelectorAll('[data-testimonial-slide]')];
    const dots = [...carousel.querySelectorAll('[data-carousel-dot]')];
    const previous = carousel.querySelector('[data-carousel-prev]');
    const next = carousel.querySelector('[data-carousel-next]');
    const current = carousel.querySelector('[data-carousel-current]');
    const progress = carousel.querySelector('[data-carousel-bar]');
    const rotationTime = 4000;
    let activeIndex = 0;
    let rotationTimer = null;

    const show = (requestedIndex) => {
        activeIndex = (requestedIndex + slides.length) % slides.length;

        slides.forEach((slide, index) => {
            const isActive = index === activeIndex;
            slide.classList.toggle('is-active', isActive);
            slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });

        dots.forEach((dot, index) => {
            dot.setAttribute('aria-current', index === activeIndex ? 'true' : 'false');
        });

        current.textContent = String(activeIndex + 1).padStart(2, '0');
        progress.style.width = `${((activeIndex + 1) / slides.length) * 100}%`;
    };

    const stopRotation = () => {
        window.clearInterval(rotationTimer);
        rotationTimer = null;
    };

    const startRotation = () => {
        stopRotation();
        if (slides.length > 1 && !document.hidden) {
            rotationTimer = window.setInterval(() => show(activeIndex + 1), rotationTime);
        }
    };

    const selectManually = (index) => {
        show(index);
        startRotation();
    };

    previous.addEventListener('click', () => selectManually(activeIndex - 1));
    next.addEventListener('click', () => selectManually(activeIndex + 1));
    dots.forEach((dot) => dot.addEventListener('click', () => selectManually(Number(dot.dataset.carouselDot))));

    carousel.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') {
            selectManually(activeIndex - 1);
        }

        if (event.key === 'ArrowRight') {
            selectManually(activeIndex + 1);
        }
    });

    let touchStart = null;
    carousel.addEventListener('touchstart', (event) => {
        touchStart = event.changedTouches[0]?.clientX ?? null;
    }, { passive: true });
    carousel.addEventListener('touchend', (event) => {
        if (touchStart === null) {
            return;
        }

        const distance = (event.changedTouches[0]?.clientX ?? touchStart) - touchStart;
        if (Math.abs(distance) > 55) {
            selectManually(activeIndex + (distance < 0 ? 1 : -1));
        }
        touchStart = null;
    }, { passive: true });

    carousel.addEventListener('mouseenter', stopRotation);
    carousel.addEventListener('mouseleave', startRotation);
    carousel.addEventListener('focusin', stopRotation);
    carousel.addEventListener('focusout', (event) => {
        if (!carousel.contains(event.relatedTarget)) {
            startRotation();
        }
    });
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopRotation();
        } else {
            startRotation();
        }
    });

    show(0);
    startRotation();
})();
