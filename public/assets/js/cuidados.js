(() => {
    const timeline = document.querySelector('[data-care-timeline]');
    if (!timeline) return;

    const steps = [...timeline.querySelectorAll('[data-care-step]')];
    const current = timeline.querySelector('[data-care-current]');
    const progress = timeline.querySelector('[data-care-progress]');

    const activate = (step) => {
        const index = steps.indexOf(step);
        steps.forEach((candidate) => candidate.classList.toggle('is-current', candidate === step));
        current.textContent = String(index + 1).padStart(2, '0');
        progress.style.height = `${((index + 1) / steps.length) * 100}%`;
    };

    const observer = new IntersectionObserver((entries) => {
        const visible = entries
            .filter((entry) => entry.isIntersecting)
            .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
        if (visible) activate(visible.target);
    }, { rootMargin: '-18% 0px -45% 0px', threshold: [0.15, 0.35, 0.6] });

    steps.forEach((step) => observer.observe(step));
    activate(steps[0]);
})();
