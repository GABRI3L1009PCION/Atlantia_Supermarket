/*
 * Pantalla de Soporte: accesos rapidos, emergencia, horario y "Mis reportes"
 * con conversacion y respuesta.
 */

state.support = null;

const SUPPORT_TYPES = [
  ['payment_problem', 'Problema de cobro', 'card'],
  ['wrong_address', 'Dirección incorrecta', 'pin'],
  ['customer_problem', 'Problema con el cliente', 'user'],
  ['store_problem', 'Problema con el comercio', 'store'],
  ['closed_business', 'Comercio cerrado', 'store'],
  ['damaged_order', 'Pedido dañado', 'box'],
  ['incomplete_order', 'Pedido incompleto', 'box'],
  ['forgotten_item', 'Artículo olvidado', 'bag'],
  ['app_issue', 'Falla en la aplicación', 'smartphone'],
  ['accident', 'Accidente', 'alert'],
  ['support_chat', 'Consulta general', 'message'],
];
const TICKET_STATUS = {
  open: { label: 'Abierto', tone: 'red', icon: 'alert' },
  in_progress: { label: 'En proceso', tone: 'amber', icon: 'clock' },
  resolved: { label: 'Resuelto', tone: 'green', icon: 'check' },
  closed: { label: 'Resuelto', tone: 'green', icon: 'check' },
};

function supportType(type) {
  const found = SUPPORT_TYPES.find(([value]) => value === type);
  if (found) return { label: found[1], icon: found[2] };
  return type === 'emergency' ? { label: 'Emergencia', icon: 'siren' } : { label: 'Soporte', icon: 'message' };
}

async function loadSupport() {
  try {
    const response = await api('/support');
    state.support = response.data || {};
    renderSupportCenter();
    renderTicketList();
  } catch (error) {
    const list = document.querySelector('[data-ticket-list]');
    if (list) list.innerHTML = `<p class="sheet-error ar-loading">${escapeHtml(error.message)}</p>`;
  }
}

function renderSupportCenter() {
  const center = state.support?.center || {};
  const phone = String(center.phone || '').trim();
  const whatsapp = String(center.whatsapp || '').replace(/[^\d]/g, '');

  const call = document.querySelector('[data-support-call]');
  if (call) {
    call.setAttribute('href', phone ? `tel:${phone}` : '#');
    call.classList.toggle('is-disabled', !phone);
    document.querySelector('[data-support-call-text]').textContent = phone
      ? 'Comunícate directamente con nuestro equipo.'
      : 'Teléfono de soporte no configurado.';
  }

  const chat = document.querySelector('[data-support-whatsapp]');
  if (chat) {
    chat.setAttribute('href', whatsapp ? `https://wa.me/${whatsapp}` : '#');
    chat.classList.toggle('is-disabled', !whatsapp);
    document.querySelector('[data-support-whatsapp-text]').textContent = whatsapp
      ? 'Escríbenos y recibe asistencia rápida.'
      : 'WhatsApp de soporte no configurado.';
  }

  const hours = String(center.hours || '').trim();
  const [days, time] = hours.includes(',') ? hours.split(/,(.+)/).map((part) => part.trim()) : ['Todos los días', hours];
  document.querySelector('[data-support-days]').textContent = days || 'Todos los días';
  document.querySelector('[data-support-hours]').textContent = time || 'Consultar';
}

function renderTicketList() {
  const list = document.querySelector('[data-ticket-list]');
  if (!list) return;
  const tickets = state.support?.tickets || [];

  if (!tickets.length) {
    list.innerHTML = `
      <div class="ar-empty">
        <h3>Sin reportes</h3>
        <p>Cuando reportes un problema podras darle seguimiento aqui.</p>
      </div>`;
    return;
  }

  list.innerHTML = tickets.map((ticket) => {
    const type = supportType(ticket.type);
    const status = TICKET_STATUS[ticket.status] || TICKET_STATUS.open;
    return `
      <button type="button" class="ar-order-card ar-ticket" data-ticket="${escapeHtml(ticket.id)}">
        <span class="ar-order-icon">${svgIcon(type.icon)}</span>
        <span class="ar-order-body">
          <span class="ar-order-head">
            <strong>${escapeHtml(type.label)}</strong>
            <span class="ar-pill ar-pill--${status.tone} ar-pill--icon">${svgIcon(status.icon)}${escapeHtml(status.label)}</span>
          </span>
          <span class="ar-ticket-text">${escapeHtml(ticket.message)}</span>
          <span class="ar-order-line">${svgIcon('calendar')}${escapeHtml(formatDate(ticket.created_at))}</span>
        </span>
        ${svgIcon('chevron', 'ar-icon ar-order-chevron')}
      </button>`;
  }).join('');

  list.querySelectorAll('[data-ticket]').forEach((card) => {
    card.addEventListener('click', () => openTicket(card.dataset.ticket));
  });
}

function openTicket(id) {
  const ticket = (state.support?.tickets || []).find((item) => item.id === id);
  if (!ticket) return;
  const type = supportType(ticket.type);
  const status = TICKET_STATUS[ticket.status] || TICKET_STATUS.open;
  const messages = (ticket.messages || []).filter((message) => !message.is_internal);
  const closed = ['resolved', 'closed'].includes(ticket.status);

  const dialog = openSheet(`
    <div class="ar-ticket-head">
      <h3>${escapeHtml(type.label)}</h3>
      <span class="ar-pill ar-pill--${status.tone} ar-pill--icon">${svgIcon(status.icon)}${escapeHtml(status.label)}</span>
    </div>
    <div class="ar-thread">
      ${messages.length ? '' : `<div class="ar-bubble is-me"><p>${escapeHtml(ticket.message)}</p><small>${escapeHtml(formatDate(ticket.created_at))}</small></div>`}
      ${messages.map((message) => `
        <div class="ar-bubble ${message.sender_type === 'courier' ? 'is-me' : ''}">
          ${message.sender_type === 'courier' ? '' : `<strong>${escapeHtml(message.sender_name || 'Soporte')}</strong>`}
          <p>${escapeHtml(message.message)}</p><small>${escapeHtml(formatDate(message.created_at))}</small>
        </div>`).join('')}
      ${ticket.support_response && !messages.length ? `<div class="ar-bubble"><strong>Soporte</strong><p>${escapeHtml(ticket.support_response)}</p></div>` : ''}
    </div>
    ${closed ? '<p class="muted">Este reporte esta resuelto.</p>' : `
      <form class="sheet-form" data-reply-form>
        <textarea class="sheet-input" name="message" rows="3" maxlength="1500" required placeholder="Escribe una respuesta"></textarea>
        <p class="sheet-error" data-sheet-error></p>
        <div class="sheet-actions">
          <button type="button" class="sheet-secondary" data-sheet-close>Cerrar</button>
          <button type="submit" class="sheet-primary">Responder</button>
        </div>
      </form>`}
    ${closed ? '<div class="sheet-actions"><button type="button" class="sheet-secondary" data-sheet-close>Cerrar</button></div>' : ''}
  `);

  dialog.querySelector('[data-reply-form]')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const message = String(new FormData(event.currentTarget).get('message') || '').trim();
    try {
      await api(`/support/tickets/${encodeURIComponent(id)}/reply`, { method: 'POST', body: JSON.stringify({ message }) });
      closeSheet();
      showToast('Respuesta enviada.');
      loadSupport();
    } catch (error) {
      dialog.querySelector('[data-sheet-error]').textContent = error.message;
    }
  });
}

function setSupportView(view) {
  const reports = view === 'reports';
  document.querySelector('[data-support-main]')?.classList.toggle('hidden', reports);
  document.querySelector('[data-support-reports-view]')?.classList.toggle('hidden', !reports);
  document.querySelector('[data-support-back]')?.classList.toggle('hidden', !reports);
  if (reports) setSupportSegment('list');
  window.scrollTo({ top: 0 });
}

function setSupportSegment(segment) {
  document.querySelectorAll('[data-support-segment]').forEach((button) => {
    button.classList.toggle('active', button.dataset.supportSegment === segment);
  });
  document.querySelector('[data-ticket-list]')?.classList.toggle('hidden', segment !== 'list');
  document.querySelector('[data-ticket-form]')?.classList.toggle('hidden', segment !== 'new');

  if (segment === 'new') {
    const active = state.activeDelivery;
    document.querySelector('[data-ticket-order]').textContent = active ? `Se adjuntara el pedido #${active.number}.` : '';
    document.querySelector('[data-ticket-error]').textContent = '';
  }
}

async function submitTicket(event) {
  event.preventDefault();
  const form = event.currentTarget;
  const data = Object.fromEntries(new FormData(form).entries());
  const active = state.activeDelivery;
  if (active) {
    data[active.source === 'external' ? 'external_delivery_order_uuid' : 'pedido_uuid'] = active.id;
  }
  const submit = form.querySelector('[type="submit"]');
  submit.disabled = true;
  try {
    await api('/support/tickets', { method: 'POST', body: JSON.stringify({ ...data, channel: 'app' }) });
    form.reset();
    showToast('Reporte enviado a soporte.');
    await loadSupport();
    setSupportSegment('list');
  } catch (error) {
    document.querySelector('[data-ticket-error]').textContent = error.message;
  } finally {
    submit.disabled = false;
  }
}

async function sendEmergency() {
  const message = await askText('Confirmas la alerta de emergencia? Describe brevemente (opcional)', 'Ej. accidente en ruta', false);
  if (message === null) return;

  const body = { message: message || 'Alerta de emergencia desde la app.' };
  const active = state.activeDelivery;
  if (active) {
    body[active.source === 'external' ? 'external_delivery_order_uuid' : 'pedido_uuid'] = active.id;
  }

  try {
    const position = Geolocation
      ? await Geolocation.getCurrentPosition({ enableHighAccuracy: true, timeout: 8000 })
      : await new Promise((resolve) => navigator.geolocation
        ? navigator.geolocation.getCurrentPosition(resolve, () => resolve(null), { timeout: 8000 })
        : resolve(null));
    if (position?.coords) {
      body.latitude = position.coords.latitude;
      body.longitude = position.coords.longitude;
    }
  } catch (_) {
    // La alerta se envia aun sin ubicacion.
  }

  try {
    await api('/support/emergency', { method: 'POST', body: JSON.stringify(body) });
    showToast('Alerta enviada. Operaciones te contactara.');
    loadSupport();
  } catch (error) {
    showToast(error.message, true);
  }
}

function wireSupport() {
  const typeSelect = document.querySelector('[data-ticket-type]');
  if (typeSelect) {
    typeSelect.innerHTML = SUPPORT_TYPES.map(([value, label]) => `<option value="${value}">${label}</option>`).join('');
  }
  document.querySelector('[data-support-reports]')?.addEventListener('click', () => setSupportView('reports'));
  document.querySelector('[data-support-back]')?.addEventListener('click', () => setSupportView('main'));
  document.querySelectorAll('[data-support-segment]').forEach((button) => {
    button.addEventListener('click', () => setSupportSegment(button.dataset.supportSegment));
  });
  document.querySelector('[data-ticket-form]')?.addEventListener('submit', submitTicket);
  document.querySelector('[data-emergency]')?.addEventListener('click', sendEmergency);
  document.querySelectorAll('[data-support-call], [data-support-whatsapp]').forEach((link) => {
    link.addEventListener('click', (event) => {
      if (link.classList.contains('is-disabled')) {
        event.preventDefault();
        showToast('Este canal aun no esta configurado.', true);
      }
    });
  });
}

wireSupport();
