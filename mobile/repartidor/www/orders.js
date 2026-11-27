/*
 * Pantallas de Inicio y Pedidos: resumen, estado del repartidor, lista de pedidos
 * asignados, historial y detalle. Usa funciones globales de app.js y flows.js.
 */

state.assignedOrders = [];
state.selectedOrderId = null;
state.ordersSegment = 'assigned';

const ORDER_STATUS = {
  pickup: { label: 'Por recoger', tone: 'amber' },
  route: { label: 'En ruta', tone: 'blue' },
  deliver: { label: 'Por entregar', tone: 'green' },
};

function svgIcon(name, className = 'ar-icon') {
  return `<svg class="${className}" aria-hidden="true"><use href="#i-${name}"/></svg>`;
}

/* ---------- Datos ---------- */

function collectAssignedOrders(data) {
  const orders = [];
  const seen = new Set();
  const push = (order, source) => {
    if (!order || !order.id || seen.has(order.id)) return;
    seen.add(order.id);
    orders.push(normalizeActiveDelivery(order, source));
  };

  push(data.current_order, 'internal');
  (data.external_active || []).forEach((order) => push(order, 'external'));
  (data.upcoming_orders || []).forEach((entry) => push(entry?.order, 'internal'));

  return orders;
}

function orderStatus(order) {
  const step = deliveryStep(order);
  if (step.action === 'arrived-customer') return ORDER_STATUS.route;
  if (step.action === 'deliver') return ORDER_STATUS.deliver;
  return ORDER_STATUS.pickup;
}

function relativeTime(value) {
  if (!value) return '';
  const minutes = Math.max(0, Math.round((Date.now() - new Date(value).getTime()) / 60000));
  if (!Number.isFinite(minutes)) return '';
  if (minutes < 1) return 'hace un momento';
  if (minutes < 60) return `hace ${minutes} min`;
  const hours = Math.round(minutes / 60);
  return hours < 24 ? `hace ${hours} h` : `hace ${Math.round(hours / 24)} d`;
}

/* ---------- Render principal (llamado desde renderDashboard) ---------- */

function renderOrdersUi(data, activeDelivery) {
  state.assignedOrders = collectAssignedOrders(data);
  renderHomeSummary(data, activeDelivery);
  renderAssignedList();

  if (state.selectedOrderId && !state.assignedOrders.some((order) => order.id === state.selectedOrderId)) {
    state.selectedOrderId = null;
  }
  renderOrderDetail();
}

function renderHomeSummary(data, activeDelivery) {
  const offers = (data.offers || []).length;
  document.querySelectorAll('[data-offers-count]').forEach((element) => {
    element.textContent = offers === 1 ? '1 pedido disponible' : `${offers} pedidos disponibles`;
  });

  const summary = activeDelivery
    ? `Pedido #${activeDelivery.number} · ${orderStatus(activeDelivery).label}`
    : 'Sin entrega activa';
  document.querySelectorAll('[data-current-summary]').forEach((element) => {
    element.textContent = summary;
  });
}

function renderAssignedList() {
  const list = document.querySelector('[data-assigned-list]');
  const label = document.querySelector('[data-assigned-label]');
  if (!list) return;

  const orders = state.assignedOrders;
  if (label) label.textContent = `Asignados (${orders.length})`;

  if (!orders.length) {
    list.innerHTML = emptyOrdersHtml('Sin pedidos asignados', 'Actualmente no tienes pedidos para entregar.');
    list.querySelector('[data-orders-refresh]')?.addEventListener('click', refreshOrders);
    return;
  }

  list.innerHTML = orders.map((order) => {
    const status = orderStatus(order);
    const assignedAt = order.timeline?.accepted_at || order.timeline?.assigned_at || order.timeline?.requested_at;
    return `
      <button type="button" class="ar-order-card" data-open-order="${escapeHtml(order.id)}">
        <span class="ar-order-icon">${svgIcon('box')}</span>
        <span class="ar-order-body">
          <span class="ar-order-head">
            <strong>Pedido #${escapeHtml(order.number)}</strong>
            <span class="ar-pill ar-pill--${status.tone}"><i aria-hidden="true"></i>${escapeHtml(status.label)}</span>
          </span>
          <span class="ar-order-line ar-order-store">${svgIcon('store')}${escapeHtml(order.storeName)}</span>
          <span class="ar-order-line">${svgIcon('pin')}Zona: ${escapeHtml(order.zone || 'Sin zona')}</span>
          ${assignedAt ? `<span class="ar-order-line">${svgIcon('clock')}Asignado ${escapeHtml(relativeTime(assignedAt))}</span>` : ''}
        </span>
        ${svgIcon('chevron', 'ar-icon ar-order-chevron')}
      </button>
    `;
  }).join('');

  list.querySelectorAll('[data-open-order]').forEach((card) => {
    card.addEventListener('click', () => {
      state.selectedOrderId = card.dataset.openOrder;
      state.detailMode = 'detail';
      renderOrderDetail(true);
      window.scrollTo({ top: 0 });
    });
  });
}

function renderOrderDetail(force = false) {
  const view = document.querySelector('[data-orders-view]');
  const detail = document.querySelector('[data-order-detail]');
  if (!view || !detail) return;

  const order = state.assignedOrders.find((item) => item.id === state.selectedOrderId) || null;
  view.classList.toggle('hidden', Boolean(order));
  detail.classList.toggle('hidden', !order);
  document.body.classList.toggle('viewing-order', Boolean(order));

  if (!order) {
    state.detailSignature = null;
    destroyDeliveryMap();
    return;
  }

  /* El panel se actualiza cada 15 s: solo se repinta si cambio el pedido o su paso. */
  const signature = JSON.stringify([order.id, state.detailMode, order.status, order.routeStatus, order.timeline, order.paymentStatus]);
  if (!force && signature === state.detailSignature) return;
  state.detailSignature = signature;

  renderCurrentOrder(order);
  afterDetailRender(order, detail);
}

async function renderHistoryList() {
  const list = document.querySelector('[data-history-list]');
  if (!list) return;
  list.innerHTML = '<p class="muted ar-loading">Cargando historial...</p>';

  try {
    const response = await api('/history');
    const entries = response.data || [];
    if (!entries.length) {
      list.innerHTML = emptyOrdersHtml('Sin entregas todavia', 'Aqui veras las entregas que completes.');
      list.querySelector('[data-orders-refresh]')?.addEventListener('click', renderHistoryList);
      return;
    }

    list.innerHTML = entries.map((entry) => {
      const order = entry.order || {};
      const store = order.vendor?.name || order.store?.name || 'Comercio';
      const earning = Number(order.earning?.estimated ?? order.courier_earning ?? 0)
        + Number(order.earning?.tip ?? order.tip_amount ?? 0);
      const cancelled = ['cancelada', 'cancelled'].includes(String(entry.route?.status || order.status || '').toLowerCase());
      return `
        <div class="ar-order-card is-static">
          <span class="ar-order-icon">${svgIcon('box')}</span>
          <span class="ar-order-body">
            <span class="ar-order-head">
              <strong>Pedido #${escapeHtml(order.number || order.external_reference || '')}</strong>
              <span class="ar-pill ar-pill--${cancelled ? 'grey' : 'green'}"><i aria-hidden="true"></i>${cancelled ? 'Cancelado' : 'Entregado'}</span>
            </span>
            <span class="ar-order-line ar-order-store">${svgIcon('store')}${escapeHtml(store)}</span>
            <span class="ar-order-line">${svgIcon('clock')}${escapeHtml(formatDate(entry.completed_at))}</span>
          </span>
          <strong class="ar-order-amount">${money(earning)}</strong>
        </div>
      `;
    }).join('');
  } catch (error) {
    list.innerHTML = `<p class="sheet-error ar-loading">${escapeHtml(error.message)}</p>`;
  }
}

function emptyOrdersHtml(title, text) {
  return `
    <div class="ar-empty">
      <svg class="ar-empty-art" viewBox="0 0 220 190" aria-hidden="true">
        <circle cx="105" cy="104" r="78" fill="#fbe9ef"/>
        <path d="M148 22l8-16M163 34l15-8M168 52l17 1" stroke="#99043a" stroke-width="6" stroke-linecap="round"/>
        <path d="M105 62l52 24-52 24-52-24z" fill="#f4c7d3"/>
        <path d="M53 86l52 24v54l-52-26z" fill="#e8a8b9"/>
        <path d="M157 86l-52 24v54l52-26z" fill="#dc94a8"/>
        <path d="M74 72l52 24v22l-14 7V102L60 78z" fill="#99043a"/>
      </svg>
      <h3>${escapeHtml(title)}</h3>
      <p>${escapeHtml(text)}</p>
      <button type="button" class="ar-outline" data-orders-refresh>${svgIcon('refresh')}Actualizar</button>
    </div>
  `;
}

async function refreshOrders(event) {
  const button = event?.currentTarget;
  button?.classList.add('is-spinning');
  try {
    await refreshDashboard();
  } catch (_) {
    // refreshDashboard ya muestra el mensaje.
  } finally {
    button?.classList.remove('is-spinning');
  }
}

/* ---------- Eventos de la interfaz ---------- */

function setOrdersSegment(segment) {
  state.ordersSegment = segment;
  document.querySelectorAll('[data-orders-segment]').forEach((button) => {
    button.classList.toggle('active', button.dataset.ordersSegment === segment);
  });
  document.querySelector('[data-assigned-list]')?.classList.toggle('hidden', segment !== 'assigned');
  document.querySelector('[data-history-list]')?.classList.toggle('hidden', segment !== 'history');
  if (segment === 'history') renderHistoryList();
}

function toggleStatusMenu(force) {
  const menu = document.querySelector('[data-status-menu]');
  const toggle = document.querySelector('[data-status-toggle]');
  if (!menu || !toggle) return;
  const open = typeof force === 'boolean' ? force : menu.classList.contains('hidden');
  menu.classList.toggle('hidden', !open);
  toggle.setAttribute('aria-expanded', String(open));
}

function wireOrdersUi() {
  document.querySelectorAll('[data-bell], [data-open-orders]').forEach((button) => {
    button.addEventListener('click', () => {
      state.selectedOrderId = null;
      setActiveTab('offers');
      setOrdersSegment('assigned');
      renderOrderDetail();
    });
  });
  document.querySelectorAll('[data-orders-segment]').forEach((button) => {
    button.addEventListener('click', () => setOrdersSegment(button.dataset.ordersSegment));
  });
  document.querySelector('[data-status-toggle]')?.addEventListener('click', () => toggleStatusMenu());
  document.querySelectorAll('[data-status-menu] button').forEach((button) => {
    button.addEventListener('click', () => toggleStatusMenu(false));
  });
  document.addEventListener('click', (event) => {
    if (!event.target.closest('.ar-status-card')) toggleStatusMenu(false);
  });
}

wireOrdersUi();

function setGpsButton(running) {
  const button = document.querySelector('[data-toggle-gps]');
  if (button) button.textContent = running ? 'Detener GPS' : 'Iniciar GPS';
  document.body.classList.toggle('gps-running', Boolean(running));
}
