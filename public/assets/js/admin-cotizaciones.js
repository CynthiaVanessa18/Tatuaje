'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const planner = document.querySelector('[data-appointment-planner]');
  const form = document.querySelector('[data-appointment-form]');
  if (!planner || !form) return;

  const rows = [...form.querySelectorAll('[data-appointment-row]')];
  const submit = form.querySelector('[data-appointment-submit]');
  let slots = {};

  try {
    slots = JSON.parse(planner.querySelector('[data-available-slots]')?.textContent || '{}');
  } catch (_) {
    if (submit) submit.disabled = true;
    return;
  }

  const asDate = (date, time) => {
    const value = `${date || ''}T${time || ''}`;
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  };
  const formatTime = (date) => new Intl.DateTimeFormat('es-CR', {
    hour: '2-digit', minute: '2-digit', hour12: false,
  }).format(date);

  function show(row, valid, title, message) {
    const feedback = row.querySelector('[data-row-feedback]');
    if (!feedback) return;
    feedback.classList.toggle('is-valid', valid === true);
    feedback.classList.toggle('is-invalid', valid === false);
    feedback.querySelector('span').textContent = valid === true ? '✓' : valid === false ? '!' : '◷';
    feedback.querySelector('b').textContent = title;
    feedback.querySelector('p').textContent = message;
  }

  function populateTimes(row) {
    const dateSelect = row.querySelector('[data-session-date]');
    const timeSelect = row.querySelector('[data-session-time]');
    if (!dateSelect || !timeSelect) return;
    const selected = timeSelect.dataset.selectedTime || timeSelect.value;
    timeSelect.replaceChildren(new Option(dateSelect.value ? 'Selecciona una hora' : 'Selecciona primero la fecha', ''));
    (slots[dateSelect.value] || []).forEach((time) => {
      const option = new Option(time, time);
      option.selected = time === selected;
      timeSelect.add(option);
    });
    timeSelect.disabled = !dateSelect.value;
    timeSelect.dataset.selectedTime = '';
  }

  function values(row) {
    const dateSelect = row.querySelector('[data-session-date]');
    const timeSelect = row.querySelector('[data-session-time]');
    const durationInput = row.querySelector('[data-appointment-duration]');
    const start = asDate(dateSelect?.value, timeSelect?.value);
    const hours = Number(durationInput?.value || 0);
    return {
      dateSelect,
      timeSelect,
      start,
      end: start ? new Date(start.getTime() + Math.round(hours * 60) * 60000) : null,
    };
  }

  function validateAll() {
    const valuesByRow = rows.map((row) => ({ row, ...values(row) }));
    let previousStart = null;
    let valid = true;

    valuesByRow.forEach((current) => {
      current.dateSelect?.setCustomValidity('');
      current.timeSelect?.setCustomValidity('');
      let error = '';

      if (!current.start || !current.end) {
        error = 'Selecciona una fecha y una hora disponibles.';
      } else if (!(slots[current.dateSelect.value] || []).includes(current.timeSelect.value)) {
        error = 'La hora elegida ya no está disponible.';
      } else if (previousStart && current.start <= previousStart) {
        error = 'Esta sesión debe ocurrir después de la sesión anterior.';
      } else {
        const overlap = valuesByRow.some((other) => other.row !== current.row && other.start && other.end
          && current.start < other.end && current.end > other.start);
        if (overlap) error = 'Esta sesión se cruza con otra que estás creando.';
      }

      if (error) {
        valid = false;
        current.dateSelect?.setCustomValidity(error);
        show(current.row, false, 'Horario no válido', error);
      } else {
        show(current.row, true, `${formatTime(current.start)}–${formatTime(current.end)} disponible`, 'La base de datos volverá a comprobar el espacio al guardar.');
        previousStart = current.start;
      }
    });

    if (submit) submit.disabled = !valid;
    return valid;
  }

  rows.forEach((row) => {
    populateTimes(row);
    row.querySelector('[data-session-date]')?.addEventListener('change', () => {
      populateTimes(row);
      validateAll();
    });
    row.querySelector('[data-session-time]')?.addEventListener('change', validateAll);
  });
  form.addEventListener('submit', (event) => {
    if (!validateAll()) event.preventDefault();
  });
  validateAll();
});
