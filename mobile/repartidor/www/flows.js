/*
 * Flujos operativos del repartidor: pasos de entrega, cobro y cierre,
 * billetera (historial, retiros, cuenta bancaria, liquidacion de efectivo) y soporte.
 * Usa las funciones globales de app.js (api, state, money, escapeHtml, refreshDashboard).
 */

const PAID_STATES = ['pagado', 'aprobado'];

/* ---------- Pasos de la entrega ---------- */

function deliveryStep(order) {
  const t = order.timeline || {};
  const status = String(order.status || '').toLowerCase();
  const arrivedPickup = Boolean(t.arrived_pickup_at) || ['arrived_pickup', 'pickup_not_ready'].includes(status);
  const pickedUp = Boolean(t.picked_up_at) || ['picked_up', 'arrived_customer'].includes(status);
  const arrivedCustomer = Boolean(t.arrived_customer_at) || status === 'arrived_customer';

  if (!arrivedPickup) {
    return {
      index: 1,
      action: 'arrived-pickup',
      label: 'Llegue al comercio',
      caption: 'Marca tu llegada',
      hint: 'Dirigete al comercio para recoger el pedido.',
      navLabel: 'Ir al comercio',
      navUrl: mapsUrl(order.storeAddress, order.storeLatitude, order.storeLongitude),
      secondary: [],
    };
  }

  if (!pickedUp) {
    return {
      index: 2,
      action: 'pickup',
      label: 'Pedido recogido',
      caption: 'Ya lo tienes contigo',
      hint: status === 'pickup_not_ready'
        ? 'Reportaste que el pedido no estaba listo. Marca recogido cuando te lo entreguen.'
        : 'Revisa que el pedido este completo antes de salir.',
      navLabel: 'Ir al comercio',
      navUrl: mapsUrl(order.storeAddress, order.storeLatitude, order.storeLongitude),
      secondary: status === 'pickup_not_ready' ? [] : [{ action: 'pickup-not-ready', label: 'Aun no esta listo' }],
    };
  }

  if (!arrivedCustomer) {
    return {
      index: 3,
      action: 'arrived-customer',
      label: 'Llegue con el cliente',
      caption: 'Marca tu llegada',
      hint: 'Lleva el pedido a la direccion del cliente.',
      navLabel: 'Ir al cliente',
      navUrl: mapsUrl(order.customerAddress, order.customerLatitude, order.customerLongitude),
      secondary: [],
    };
  }

  return {
    index: 3,
    action: 'deliver',
    label: 'Entregar pedido',
    caption: needsCollection(order) ? `Cobra ${money(order.total)}` : 'Pide el codigo al cliente',
    hint: needsCollection(order)
      ? `${collectionInstruction(order)} Luego pide el codigo de 4 digitos al cliente.`
      : 'Pide al cliente su codigo de entrega de 4 digitos.',
    navLabel: 'Ver direccion',
    navUrl: mapsUrl(order.customerAddress, order.customerLatitude, order.customerLongitude),
    secondary: order.source === 'external' && Number(order.cashToCollect) > 0
      ? [{ action: 'cash-issue', label: 'Cliente no pudo pagar' }]
      : [],
  };
}

function needsCollection(order) {
  if (PAID_STATES.includes(String(order.paymentStatus || '').toLowerCase())) return false;
  if (order.source === 'external') return Number(order.cashToCollect) > 0;
  return ['efectivo', 'tarjeta', 'transferencia'].includes(String(order.paymentMethod || '').toLowerCase());
}

function collectionInstruction(order) {
  const method = String(order.paymentMethod || '').toLowerCase();
  if (method === 'tarjeta') return `Cobra ${money(order.total)} con la terminal POS.`;
  if (method === 'transferencia') return `Confirma la transferencia de ${money(order.total)}.`;
  const change = Number(order.changeRequired || 0);
  return `Cobra ${money(order.cashToCollect || order.total)} en efectivo${change > 0 ? ` y entrega ${money(change)} de cambio` : ''}.`;
}

function paymentLabel(method, status) {
  const value = String(method || '').toLowerCase();
  if (PAID_STATES.includes(String(status || '').toLowerCase())) return 'Pagado en linea';
  if (value.includes('efectivo')) return 'Pago en efectivo';
  if (value.includes('transferencia')) return 'Transferencia al entregar';
  if (value.includes('tarjeta')) return 'Tarjeta con POS';
  return 'Pago digital';
}

async function runOrderAction(order, action) {
  if (action === 'deliver') {
    openDeliveryDialog(order);
    return;
  }

  const body = {};
  if (action === 'pickup-not-ready') {
    const reason = await askText('Que paso con el pedido?', 'Ej. aun lo estan preparando', false);
    if (reason === null) return;
    body[order.source === 'external' ? 'issue_reason' : 'pickup_issue_reason'] = reason || 'Pedido no listo';
  }
  if (action === 'verify-code') {
    const code = await askText('Codigo de entrega del cliente (4 digitos)', '0000', true, 'numeric');
    if (code === null) return;
    if (!/^\d{4}$/.test(code)) {
      showToast('El codigo debe tener 4 digitos.', true);
      return;
    }
    body.confirmation_code = code;
  }
  if (action === 'cash-issue') {
    const notes = await askText('Describe el problema de cobro', 'Ej. el cliente no tenia efectivo', true);
    if (notes === null) return;
    body.cash_notes = notes;
  }

  await sendOrderAction(order, action, body);
}

async function sendOrderAction(order, action, body) {
  const base = order.source === 'external' ? 'external-orders' : 'orders';
  try {
    await api(`/${base}/${encodeURIComponent(order.id)}/${action}`, {
      method: 'PATCH',
      body: JSON.stringify(body || {}),
    });
    await refreshDashboard();
  } catch (error) {
    showToast(error.message, true);
  }
}

/* ---------- Dialogo de entrega ---------- */

function openDeliveryDialog(order) {
  const method = String(order.paymentMethod || '').toLowerCase();
  const collect = needsCollection(order);
  const askReference = collect && order.source !== 'external' && ['tarjeta', 'transferencia'].includes(method);
  const codeVerified = Boolean(order.timeline?.code_verified_at);

  const dialog = openSheet(`
    <h3>Entregar pedido ${escapeHtml(order.number)}</h3>
    <form data-deliver-form class="sheet-form">
      ${codeVerified ? '<p class="sheet-ok">Codigo del cliente ya verificado.</p>' : `
        <label class="sheet-field">
          <span>Codigo de entrega del cliente</span>
          <input name="confirmation_code" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" autocomplete="one-time-code" required placeholder="0000">
        </label>`}
      ${collect ? `
        <div class="sheet-collect">
          <strong>${escapeHtml(paymentLabel(order.paymentMethod, order.paymentStatus))}</strong>
          <span>${escapeHtml(collectionInstruction(order))}</span>
        </div>
        ${askReference ? `
          <label class="sheet-field">
            <span>${method === 'tarjeta' ? 'No. de autorizacion del voucher POS' : 'Referencia de la transferencia'}</span>
            <input name="collection_reference" maxlength="80" required>
          </label>` : ''}
        ${order.source === 'external' ? '' : `
          <label class="sheet-check">
            <input type="checkbox" name="collection_confirmed" value="1" required>
            <span>Recibi el pago de ${money(order.total)}</span>
          </label>`}` : ''}
      <label class="sheet-field">
        <span>Foto de entrega (opcional)</span>
        <input type="file" name="photo" accept="image/*" capture="environment">
      </label>
      <p class="sheet-error" data-sheet-error></p>
      <div class="sheet-actions">
        <button type="button" class="sheet-secondary" data-sheet-close>Cancelar</button>
        <button type="submit" class="sheet-primary">Confirmar entrega</button>
      </div>
    </form>
  `);

  dialog.querySelector('[data-deliver-form]').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const submit = form.querySelector('[type="submit"]');
    const error = dialog.querySelector('[data-sheet-error]');
    const values = new FormData(form);
    const payload = new FormData();
    payload.append('_method', 'PATCH');

    const code = String(values.get('confirmation_code') || '').trim();
    if (code) payload.append('confirmation_code', code);
    if (values.get('collection_confirmed')) payload.append('collection_confirmed', '1');
    const reference = String(values.get('collection_reference') || '').trim();
    if (reference) payload.append('collection_reference', reference);
    const photo = values.get('photo');
    if (photo && photo.size > 0) {
      if (photo.size > 4 * 1024 * 1024) {
        error.textContent = 'La foto supera 4 MB. Toma otra con menor resolucion.';
        return;
      }
      payload.append(order.source === 'external' ? 'proof_photo' : 'foto_entrega', photo);
    }

    submit.disabled = true;
    error.textContent = '';
    try {
      const base = order.source === 'external' ? 'external-orders' : 'orders';
      await api(`/${base}/${encodeURIComponent(order.id)}/deliver`, { method: 'POST', body: payload });
      closeSheet();
      await notifyLocal('Entrega completada', `Pedido ${order.number} entregado.`).catch(() => {});
      await refreshDashboard();
    } catch (requestError) {
      error.textContent = requestError.message;
      submit.disabled = false;
    }
  });
}

/* ---------- Cierre de entrega ---------- */

function renderCompletion(recent) {
  if (!recent || !recent.order || state.completionShownFor === recent.order.id) return;
  state.completionShownFor = recent.order.id;
  const order = recent.order;
  const earning = Number(order.earning?.estimated ?? order.courier_earning ?? 0)
    + Number(order.earning?.tip ?? order.tip_amount ?? 0);

  const dialog = openSheet(`
    <div class="sheet-done">
      <span aria-hidden="true">&#10003;</span>
      <h3>Entrega completada</h3>
      <p>Pedido ${escapeHtml(order.number || order.external_reference || '')}</p>
      <strong>${money(earning)}</strong>
      <small>Ganancia de esta entrega</small>
    </div>
    <div class="sheet-actions">
      <button type="button" class="sheet-primary" data-ack>Continuar</button>
    </div>
  `, false);

  dialog.querySelector('[data-ack]').addEventListener('click', async () => {
    const base = recent.type === 'external' ? 'external-orders' : 'orders';
    try {
      await api(`/${base}/${encodeURIComponent(order.id)}/ack-completion`, { method: 'PATCH', body: '{}' });
    } catch (_) {
      // Si falla, el cierre se vuelve a mostrar en la siguiente actualizacion.
      state.completionShownFor = null;
    }
    closeSheet();
    refreshDashboard().catch(() => {});
  });
}

/* ---------- Utilidades de interfaz ---------- */

function openSheet(html, dismissable = true) {
  closeSheet();
  const backdrop = document.createElement('div');
  backdrop.className = 'sheet-backdrop';
  backdrop.dataset.sheet = 'true';
  backdrop.innerHTML = `<div class="sheet" role="dialog" aria-modal="true">${html}</div>`;
  document.body.appendChild(backdrop);
  backdrop.querySelectorAll('[data-sheet-close]').forEach((button) => button.addEventListener('click', closeSheet));
  if (dismissable) {
    backdrop.addEventListener('click', (event) => {
      if (event.target === backdrop) closeSheet();
    });
  }
  backdrop.querySelector('input, textarea, select')?.focus();
  return backdrop;
}

function closeSheet() {
  document.querySelectorAll('[data-sheet]').forEach((sheet) => sheet.remove());
}

function askText(title, placeholder, required, inputMode = 'text') {
  return new Promise((resolve) => {
    const dialog = openSheet(`
      <h3>${escapeHtml(title)}</h3>
      <form class="sheet-form" data-ask-form>
        <input class="sheet-input" name="value" inputmode="${inputMode}" placeholder="${escapeHtml(placeholder)}" ${required ? 'required' : ''}>
        <div class="sheet-actions">
          <button type="button" class="sheet-secondary" data-ask-cancel>Cancelar</button>
          <button type="submit" class="sheet-primary">Aceptar</button>
        </div>
      </form>
    `, false);
    dialog.querySelector('[data-ask-cancel]').addEventListener('click', () => {
      closeSheet();
      resolve(null);
    });
    dialog.querySelector('[data-ask-form]').addEventListener('submit', (event) => {
      event.preventDefault();
      const value = String(new FormData(event.currentTarget).get('value') || '').trim();
      closeSheet();
      resolve(value);
    });
  });
}

function showToast(message, isError = false) {
  const toast = document.createElement('div');
  toast.className = `flow-toast${isError ? ' is-error' : ''}`;
  toast.textContent = message;
  document.body.appendChild(toast);
  window.setTimeout(() => toast.remove(), 4000);
}

function formatDate(value) {
  if (!value) return '';
  const date = new Date(value);
  return Number.isNaN(date.getTime())
    ? String(value)
    : date.toLocaleString([], { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
}
