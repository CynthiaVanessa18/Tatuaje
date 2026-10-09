document.querySelectorAll('[data-renewal-options]').forEach(options => {
    const form = options.closest('form');
    const consent = form.querySelector('[name="accept_renewal_terms"]');
    const update = () => {
        consent.required = options.querySelector('[value="1"]').checked;
    };
    options.addEventListener('change', update);
    update();
});
document.querySelectorAll('[data-membership-comparison]').forEach(form => {
    const mode = form.querySelector('[name="modalidad"]');
    const summary = form.querySelector('[data-membership-summary]');
    const update = () => {
        const plan = form.querySelector('[name="plan"]:checked');
        if (!plan) return;
        [...mode.options].forEach(option => {
            option.disabled = plan.dataset.mode !== 'ambas' && option.value !== plan.dataset.mode;
        });
        if (mode.selectedOptions[0].disabled) mode.value = plan.dataset.mode;
        form.querySelectorAll('[data-plan-column]').forEach(cell => {
            cell.classList.toggle('membership-selected', cell.dataset.planColumn === plan.value);
        });
        const monthly = mode.value === 'mensual';
        summary.textContent = `${plan.dataset.name} · ${monthly ? plan.dataset.monthly : plan.dataset.yearly} / ${monthly ? 'mes' : 'año'}`;
    };
    form.addEventListener('change', update);
    update();
});
