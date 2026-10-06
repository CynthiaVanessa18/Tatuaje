'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const planner = document.querySelector('[data-appointment-planner]');
  const form = document.querySelector('[data-appointment-form]');

  if (!planner || !form) {
    return;
  }

  const startInput = form.querySelector('[data-appointment-start]');
  const durationInput = form.querySelector('[data-appointment-duration]');
  const submitButton = form.querySelector('[data-appointment-submit]');
  const feedback = planner.querySelector('[data-slot-feedback]');
  const scheduleNode = planner.querySelector('[data-artist-schedule]');
  const unavailableNode = planner.querySelector('[data-artist-unavailable]');
  const dayNames = ['', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];

  if (!startInput || !durationInput || !submitButton || !feedback || !scheduleNode || !unavailableNode) {
    return;
  }

  let schedule = [];
  let unavailable = [];

  try {
    schedule = JSON.parse(scheduleNode.textContent || '[]');
    unavailable = JSON.parse(unavailableNode.textContent || '[]');
  } catch (error) {
    showResult(false, 'No fue posible leer la disponibilidad', 'Recarga la página antes de crear la cita.');
    return;
  }

  function parseDateTime(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::(\d{2}))?$/.exec(value || '');

    if (!match) {
      return null;
    }

    const date = new Date(
      Number(match[1]),
      Number(match[2]) - 1,
      Number(match[3]),
      Number(match[4]),
      Number(match[5]),
      Number(match[6] || 0),
      0
    );

    if (Number.isNaN(date.getTime())) {
      return null;
    }

    return date;
  }

  function timeToMinutes(value) {
    const parts = String(value || '').split(':');
    return Number(parts[0]) * 60 + Number(parts[1]);
  }

  function appointmentDay(date) {
    return date.getDay() === 0 ? 7 : date.getDay();
  }

  function formatTime(date) {
    return new Intl.DateTimeFormat('es-CR', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    }).format(date);
  }

  function formatDate(date) {
    return new Intl.DateTimeFormat('es-CR', {
      weekday: 'long',
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    }).format(date);
  }

  function showResult(valid, title, message) {
    feedback.classList.toggle('is-valid', valid === true);
    feedback.classList.toggle('is-invalid', valid === false);
    feedback.querySelector('span').textContent = valid === true ? '✓' : valid === false ? '!' : '◷';
    feedback.querySelector('b').textContent = title;
    feedback.querySelector('p').textContent = message;
  }

  function validateSlot() {
    const start = parseDateTime(startInput.value);
    const durationHours = Number(String(durationInput.value).replace(',', '.'));
    startInput.setCustomValidity('');

    if (!start || !Number.isFinite(durationHours) || durationHours < 0.25 || durationHours > 16) {
      submitButton.disabled = true;
      showResult(null, 'Selecciona el inicio y la duración', 'Aquí verás la hora de finalización y si el espacio está disponible.');
      return false;
    }

    const end = new Date(start.getTime() + Math.round(durationHours * 60) * 60 * 1000);
    const day = appointmentDay(start);
    const startMinutes = start.getHours() * 60 + start.getMinutes();
    const endMinutes = end.getHours() * 60 + end.getMinutes();
    const intervals = schedule.filter((item) => Number(item.dia_semana) === day);
    let error = '';

    if (start.toDateString() !== end.toDateString()) {
      error = 'La cita debe comenzar y terminar el mismo día.';
    } else if (intervals.length === 0) {
      const availableDays = [...new Set(schedule.map((item) => dayNames[Number(item.dia_semana)]))].join(', ');
      error = `El ${dayNames[day]} no forma parte del horario activo. Días disponibles: ${availableDays || 'ninguno'}.`;
    } else {
      const fittingInterval = intervals.find((item) => (
        startMinutes >= timeToMinutes(item.hora_inicio)
        && endMinutes <= timeToMinutes(item.hora_fin)
      ));

      if (!fittingInterval) {
        const hours = intervals
          .map((item) => `${String(item.hora_inicio).slice(0, 5)}–${String(item.hora_fin).slice(0, 5)}`)
          .join(', ');
        error = `La cita terminaría a las ${formatTime(end)}. El horario del ${dayNames[day]} es ${hours}.`;
      }
    }

    if (error === '') {
      const conflict = unavailable.find((period) => {
        const blockedStart = parseDateTime(period.fecha_hora_inicio);
        const blockedEnd = parseDateTime(period.fecha_hora_fin);
        return blockedStart && blockedEnd && start < blockedEnd && end > blockedStart;
      });

      if (conflict) {
        const blockedStart = parseDateTime(conflict.fecha_hora_inicio);
        const blockedEnd = parseDateTime(conflict.fecha_hora_fin);
        error = `Ese espacio coincide con ${String(conflict.descripcion).toLowerCase()} (${formatTime(blockedStart)}–${formatTime(blockedEnd)}).`;
      }
    }

    if (error !== '') {
      startInput.setCustomValidity(error);
      submitButton.disabled = true;
      showResult(false, `${formatTime(start)}–${formatTime(end)} no está disponible`, error);
      return false;
    }

    submitButton.disabled = false;
    showResult(
      true,
      `${formatTime(start)}–${formatTime(end)} disponible`,
      `${formatDate(start)} · La base de datos volverá a comprobar el espacio al guardarlo.`
    );
    return true;
  }

  startInput.addEventListener('input', validateSlot);
  startInput.addEventListener('change', validateSlot);
  durationInput.addEventListener('input', validateSlot);
  durationInput.addEventListener('change', validateSlot);
  form.addEventListener('submit', (event) => {
    if (!validateSlot()) {
      event.preventDefault();
      startInput.reportValidity();
    }
  });

  validateSlot();
});
