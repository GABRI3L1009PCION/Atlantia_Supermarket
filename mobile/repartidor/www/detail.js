/*
 * Detalle de entrega y vista de mapa "Entrega en curso".
 * Usa app.js (money, escapeHtml, mapsUrl, cleanPhone, formatTimelineTime, Geolocation),
 * flows.js (deliveryStep, needsCollection, collectionInstruction, paymentLabel, openSheet)
 * y orders.js (svgIcon, orderStatus, relativeTime). El mapa usa Leaflet con OpenStreetMap y rutas OSRM:
 * la ruta se traza desde la ubicacion en vivo del repartidor hasta el destino.
 */

state.detailMode = 'detail';
state.deliveryMap = null;

function deliveryPhase(order) {
  const step = deliveryStep(order);
  const t = order.timeline || {};
  const status = String(order.status || '').toLowerCase();
  const pickedUp = step.action === 'arrived-customer' || step.action === 'deliver';
  return {
    step,
    pickedUp,
    arrivedPickup: Boolean(t.arrived_pickup_at) || ['arrived_pickup', 'pickup_not_ready'].includes(status) || step.index >= 2,
    arrivedCustomer: step.action === 'deliver',
    codeVerified: Boolean(t.code_verified_at),
    notReady: status === 'pickup_not_ready',
  };
}

function deliveryActions(order) {
  const phase = deliveryPhase(order);
  const actions = [];

  if (!phase.pickedUp) {
    actions.push({ id: 'map', label: 'Dirigirme al comercio', icon: 'send' });
    actions.push({ action: 'arrived-pickup', label: 'Llegu&eacute; al comercio', icon: 'pin', done: phase.arrivedPickup });
    if (phase.arrivedPickup && !phase.notReady) {
      actions.push({ action: 'pickup-not-ready', label: 'El pedido no est&aacute; listo', icon: 'clock' });
    }
    actions.push({ action: 'pickup', label: 'Marcar como recogido', icon: 'bag' });
  } else {
    actions.push({ id: 'map', label: 'Dirigirme al cliente', icon: 'send' });
    actions.push({ action: 'arrived-customer', label: 'Llegu&eacute; al cliente', icon: 'pin', done: phase.arrivedCustomer });
    actions.push({ action: 'verify-code', label: 'Ingresar c&oacute;digo de entrega', icon: 'qr', done: phase.codeVerified });
    if (order.source === 'external' && Number(order.cashToCollect) > 0) {
      actions.push({ action: 'cash-issue', label: 'Cliente no pudo pagar', icon: 'alert' });
    }
    actions.push({ action: 'deliver', label: 'Marcar como entregado', icon: 'flag' });
  }

  let primary = phase.step.action;
  if (primary === 'arrived-pickup' || primary === 'arrived-customer') primary = 'map';
  if (primary === 'deliver' && !phase.codeVerified) primary = 'verify-code';

  return actions.map((item) => ({ ...item, primary: (item.action || item.id) === primary }));
}

function renderOrderDetailHtml(order) {
  return state.detailMode === 'map' ? mapViewHtml(order) : detailViewHtml(order);
}

function detailHero(title, subtitle, backAttr) {
  return `
    <header class="ar-hero ar-hero--page ar-hero--compact">
      <div class="ar-brand-row">
        <div class="ar-brand">${svgIcon('scooter', 'ar-brand-mark')}<span>ATLANTIA<br>REPARTIDOR</span></div>
        <button type="button" class="ar-bell" data-bell aria-label="Pedidos entrantes">${svgIcon('bell')}<i class="ar-bell-dot" aria-hidden="true"></i></button>
      </div>
      <div class="ar-title-row">
        <button type="button" class="ar-round" ${backAttr} aria-label="Volver">${svgIcon('back')}</button>
        <div><h1>${title}</h1><p>${subtitle}</p></div>
      </div>
    </header>
  `;
}

function detailViewHtml(order) {
  const phase = deliveryPhase(order);
  const paid = !needsCollection(order);
  const methodNames = { efectivo: 'Efectivo', tarjeta: 'Tarjeta (POS)', transferencia: 'Transferencia' };
  const method = paid
    ? 'Pagado en l&iacute;nea'
    : escapeHtml(methodNames[String(order.paymentMethod || '').toLowerCase()] || 'Efectivo');
  const items = order.items.map((item) => `
    <div class="ar-item">
      <span class="ar-item-qty">${Number(item.quantity || 1)}x</span>
      <span class="ar-item-name">${escapeHtml(item.name)}</span>
      <span class="ar-item-price">${money(item.subtotal)}</span>
    </div>`).join('');

  return `
    ${detailHero('Detalle de entrega', `Pedido #${escapeHtml(order.number)}`, 'data-detail-back')}
    <div class="ar-sheet ar-detail">
      <article class="ar-card ar-parties">
        <div class="ar-party">
          <span class="ar-party-icon">${svgIcon('store')}</span>
          <div class="ar-card-text"><small>Comercio</small><strong>${escapeHtml(order.storeName)}</strong></div>
          ${phase.pickedUp
            ? `<span class="ar-pill ar-pill--green ar-pill--icon">${svgIcon('check')}Pedido recogido</span>`
            : `<span class="ar-pill ar-pill--amber"><i aria-hidden="true"></i>${phase.arrivedPickup ? 'En comercio' : 'Por recoger'}</span>`}
        </div>
        <button type="button" class="ar-party ar-party--button" data-customer-info>
          <span class="ar-party-icon">${svgIcon('house')}</span>
          <div class="ar-card-text"><small>Cliente</small><strong>${escapeHtml(order.customerName)}</strong><span>${escapeHtml(order.customerAddress)}</span></div>
          ${svgIcon('chevron', 'ar-icon ar-row-chevron')}
        </button>
      </article>

      <h3 class="ar-block-title">Pago del pedido</h3>
      <article class="ar-card ar-pay-row ${paid ? 'is-paid' : ''}">
        <div class="ar-pay-line">
          <span class="ar-pay-icon">${svgIcon(paid ? 'check' : 'cash')}</span>
          <strong class="ar-pay-method">${method}</strong>
          <strong class="ar-pay-amount">${money(paid ? order.total : (order.cashToCollect || order.total))}</strong>
        </div>
        ${paid ? '<small>No cobres al cliente.</small>' : `<small>${escapeHtml(collectionInstruction(order))}</small>`}
      </article>

      <h3 class="ar-block-title">Acciones de entrega</h3>
      <article class="ar-card ar-actions">
        ${deliveryActions(order).map((item) => `
          <button type="button"
            class="ar-action ${item.primary ? 'is-primary' : ''} ${item.done ? 'is-done' : ''}"
            ${item.done ? 'disabled' : ''}
            ${item.id === 'map' ? 'data-open-map' : `data-order-id="${escapeHtml(order.id)}" data-order-source="${escapeHtml(order.source)}" data-order-action="${item.action}"`}>
            ${svgIcon(item.done ? 'check' : item.icon)}<span>${item.label}</span>
          </button>`).join('')}
      </article>

      <details class="ar-card ar-items-card">
        <summary><span>${svgIcon('bag')}Resumen del pedido</span><small>${order.itemsCount || order.items.length} productos</small></summary>
        <div class="ar-items">${items}</div>
        <div class="ar-totals">
          <div><span>Subtotal</span><span>${money(order.subtotal)}</span></div>
          <div><span>Env&iacute;o</span><span>${money(order.deliveryFee)}</span></div>
          ${order.discount > 0 ? `<div><span>Descuento</span><span>- ${money(order.discount)}</span></div>` : ''}
          <div class="ar-total"><span>Total</span><strong>${money(order.total)}</strong></div>
        </div>
      </details>
    </div>
  `;
}

function mapSteps(order) {
  const phase = deliveryPhase(order);
  if (!phase.pickedUp) {
    const done = [true, true, phase.arrivedPickup, false];
    return [
      ['Aceptado', 'check'], ['En camino', 'send'], ['Llegada', 'pin'], ['Recogido', 'bag'],
    ].map(([label, icon], index) => ({ label, icon, state: stepState(done, index) }));
  }
  const done = [true, true, phase.arrivedCustomer, phase.codeVerified, false];
  return [
    ['Recogido', 'check'], ['En ruta', 'send'], ['Llegada', 'pin'], ['C&oacute;digo', 'qr'], ['Entregado', 'check'],
  ].map(([label, icon], index) => ({ label, icon, state: stepState(done, index) }));
}

function stepState(done, index) {
  if (done[index] && done[index + 1]) return 'done';
  if (done[index] && !done[index + 1]) return index === done.length - 1 ? 'done' : 'current';
  return 'pending';
}

function mapTarget(order) {
  const phase = deliveryPhase(order);
  return phase.pickedUp
    ? { kind: 'Cliente', name: order.customerName, phone: cleanPhone(order.customerPhone), address: order.customerAddress, lat: order.customerLatitude, lng: order.customerLongitude }
    : { kind: 'Comercio', name: order.storeName, phone: cleanPhone(order.storePhone), address: order.storeAddress, lat: order.storeLatitude, lng: order.storeLongitude };
}

function navigationUrl(target) {
  const lat = Number(target.lat);
  const lng = Number(target.lng);
  const destination = Number.isFinite(lat) && Number.isFinite(lng) && (lat || lng)
    ? `${lat},${lng}`
    : (target.address || '');
  return `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(destination)}&travelmode=driving`;
}

function mapViewHtml(order) {
  const phase = deliveryPhase(order);
  const target = mapTarget(order);
  const steps = mapSteps(order);

  return `
    ${detailHero('Entrega en curso', phase.pickedUp ? 'Dir&iacute;gete al cliente' : 'Dir&iacute;gete al comercio', 'data-map-back')}
    <div class="ar-sheet ar-map-sheet">
      <ol class="ar-map-steps" style="--steps:${steps.length}">
        ${steps.map((item) => `
          <li class="is-${item.state}">
            <span class="ar-map-step-dot">${svgIcon(item.state === 'done' ? 'check' : item.icon)}</span>
            <small>${item.label}</small>
          </li>`).join('')}
      </ol>

      <div class="ar-map-wrap">
        <div class="ar-map" data-map></div>
        <div class="ar-turn" data-map-nav hidden>
          <span class="ar-turn-arrow" data-nav-arrow aria-hidden="true">&uarr;</span>
          <div><strong data-nav-dist></strong><span data-nav-text></span></div>
        </div>
        <div class="ar-map-tools">
          <button type="button" data-map-center aria-label="Centrar en mi ubicaci&oacute;n">${svgIcon('target')}</button>
        </div>
      </div>

      <article class="ar-card ar-map-info">
        <div class="ar-map-contact">
          <div class="ar-card-text"><small>${target.kind}</small><strong>${escapeHtml(target.name)}</strong></div>
          ${target.phone ? `<a class="ar-square" href="tel:${escapeHtml(target.phone)}" aria-label="Llamar">${svgIcon('phone')}</a>` : ''}
          ${target.phone ? `<a class="ar-square is-filled" href="sms:${escapeHtml(target.phone)}" aria-label="Mensaje">${svgIcon('message')}</a>` : ''}
        </div>
        <div class="ar-map-metrics">
          <div>${svgIcon('pin')}<span><small>Distancia</small><strong data-map-distance>${order.distanceKm ? `${formatDistance(order.distanceKm)} km` : '--'}</strong></span></div>
          <div>${svgIcon('clock')}<span><small>Tiempo estimado</small><strong data-map-eta>${order.estimatedTime ? `${Math.round(order.estimatedTime)} min` : '--'}</strong></span></div>
          <a class="ar-map-go" href="${navigationUrl(target)}" target="_blank" rel="noopener" aria-label="Navegar">${svgIcon('navigation')}</a>
        </div>
      </article>

      <a class="ar-btn ar-btn--primary" href="${navigationUrl(target)}" target="_blank" rel="noopener">${svgIcon('send')}Iniciar navegaci&oacute;n</a>
    </div>
  `;
}

/* ---------- Comportamiento despues de pintar ---------- */

function afterDetailRender(order, container) {
  container.querySelectorAll('[data-detail-back]').forEach((button) => button.addEventListener('click', () => {
    state.selectedOrderId = null;
    state.detailMode = 'detail';
    renderOrderDetail();
    window.scrollTo({ top: 0 });
  }));
  container.querySelectorAll('[data-map-back]').forEach((button) => button.addEventListener('click', () => {
    state.detailMode = 'detail';
    renderOrderDetail(true);
    window.scrollTo({ top: 0 });
  }));
  container.querySelectorAll('[data-open-map]').forEach((button) => button.addEventListener('click', () => {
    state.detailMode = 'map';
    renderOrderDetail(true);
    window.scrollTo({ top: 0 });
  }));
  container.querySelectorAll('[data-bell]').forEach((button) => button.addEventListener('click', () => {
    state.selectedOrderId = null;
    state.detailMode = 'detail';
    renderOrderDetail();
  }));
  container.querySelector('[data-customer-info]')?.addEventListener('click', () => openCustomerSheet(order));

  destroyDeliveryMap();
  if (state.detailMode === 'map') {
    initDeliveryMap(order, container.querySelector('[data-map]'));
  }
}

function openCustomerSheet(order) {
  const phone = cleanPhone(order.customerPhone);
  openSheet(`
    <h3>${escapeHtml(order.customerName)}</h3>
    <div class="ar-card-text ar-sheet-text">
      <small>Direcci&oacute;n</small><strong>${escapeHtml(order.customerAddress)}</strong>
      ${order.customerReference ? `<span>${escapeHtml(order.customerReference)}</span>` : ''}
      ${phone ? `<small>Tel&eacute;fono</small><strong>${escapeHtml(phone)}</strong>` : '<span>Sin tel&eacute;fono registrado.</span>'}
    </div>
    <div class="sheet-actions">
      ${phone ? `<a class="sheet-secondary" href="tel:${escapeHtml(phone)}">Llamar</a><a class="sheet-secondary" href="sms:${escapeHtml(phone)}">Mensaje</a>` : ''}
      <a class="sheet-primary" href="${navigationUrl({ lat: order.customerLatitude, lng: order.customerLongitude, address: order.customerAddress })}" target="_blank" rel="noopener">C&oacute;mo llegar</a>
    </div>
  `);
}

/* ---------- Mapa ---------- */

const ROUTE_COLOR = '#8b1538';
const ROUTE_TIME_FACTOR = 1.2; // OSRM calcula para auto; en moto y calles del puerto se ajusta un poco.
const GPS_SHARE_MS = 10000;
const REROUTE_MIN_MS = 12000;
const OFF_ROUTE_METERS = 60;
const ARRIVED_METERS = 40;

function destroyDeliveryMap() {
  const view = state.deliveryMap;
  if (view?.watchId != null) {
    if (view.watchNative && Geolocation?.clearWatch) {
      Promise.resolve(view.watchId).then((id) => Geolocation.clearWatch({ id })).catch(() => {});
    } else if (navigator.geolocation) {
      navigator.geolocation.clearWatch(view.watchId);
    }
  }
  if (view?.map) {
    view.map.remove();
  }
  state.deliveryMap = null;
}

function validPoint(lat, lng) {
  const a = Number(lat);
  const b = Number(lng);
  return Number.isFinite(a) && Number.isFinite(b) && (a !== 0 || b !== 0) ? [a, b] : null;
}

function mapPin(kind, label) {
  const icon = kind === 'home' ? 'house' : (kind === 'courier' ? 'scooter' : 'store');
  return L.divIcon({
    className: 'ar-pin-wrap',
    html: `<div class="ar-pin ar-pin--${kind}">${svgIcon(icon, 'ar-pin-icon')}</div>${label ? `<div class="ar-pin-label">${label}</div>` : ''}`,
    iconSize: [46, 56],
    iconAnchor: kind === 'courier' ? [23, 23] : [23, 52],
  });
}

async function currentPosition() {
  try {
    if (Geolocation) {
      const position = await Geolocation.getCurrentPosition({ enableHighAccuracy: true, timeout: 6000 });
      return validPoint(position.coords.latitude, position.coords.longitude);
    }
    if (navigator.geolocation) {
      const position = await new Promise((resolve, reject) => navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 6000 }));
      return validPoint(position.coords.latitude, position.coords.longitude);
    }
  } catch (_) {
    // Sin permiso o sin senal: se muestran solo los puntos del pedido.
  }
  return null;
}

function watchCourierPosition(view, onPoint) {
  const handle = (position) => {
    const point = position && validPoint(position.coords.latitude, position.coords.longitude);
    if (point && state.deliveryMap === view) onPoint(point, position);
  };
  const options = { enableHighAccuracy: true, timeout: 15000, maximumAge: 3000 };

  if (Geolocation?.watchPosition) {
    view.watchNative = true;
    view.watchId = Geolocation.watchPosition(options, (position, error) => { if (!error) handle(position); });
    return;
  }
  if (navigator.geolocation) {
    view.watchId = navigator.geolocation.watchPosition(handle, () => {}, options);
  }
}

function mapUnavailable(element, message) {
  element.classList.add('is-empty');
  element.innerHTML = `<div class="ar-map-empty">${svgIcon('pin')}<p>${message}</p></div>`;
}

/* Indicaciones en espanol a partir de los pasos de OSRM. */
const TURN_SIDE = {
  left: 'a la izquierda', right: 'a la derecha', 'slight left': 'ligeramente a la izquierda', 'slight right': 'ligeramente a la derecha',
  straight: 'recto', 'sharp left': 'a la izquierda', 'sharp right': 'a la derecha', uturn: 'en U',
};
const TURN_ARROW = {
  left: '&#8624;', right: '&#8625;', 'slight left': '&#8598;', 'slight right': '&#8599;',
  straight: '&uarr;', 'sharp left': '&#8624;', 'sharp right': '&#8625;', uturn: '&#8630;',
};

function maneuverText(step, kind) {
  const street = step.name ? ` por ${escapeHtml(step.name)}` : '';
  const side = TURN_SIDE[step.modifier] || '';
  switch (step.type) {
    case 'arrive': return { arrow: '&#127937;', text: kind === 'Cliente' ? 'Llegas donde el cliente' : 'Llegas al comercio' };
    case 'roundabout':
    case 'rotary': return { arrow: '&#10227;', text: `En la rotonda sigue ${side || 'recto'}${street}` };
    case 'exit roundabout':
    case 'exit rotary': return { arrow: '&uarr;', text: `Sal de la rotonda${street}` };
    case 'end of road': return { arrow: TURN_ARROW[step.modifier] || '&uarr;', text: `Al final de la calle gira ${side}${street}` };
    case 'continue':
    case 'new name': return { arrow: TURN_ARROW[step.modifier] || '&uarr;', text: `Contin&uacute;a ${side === 'recto' ? 'recto' : side}${street}` };
    default: return { arrow: TURN_ARROW[step.modifier] || '&uarr;', text: side === 'recto' ? `Sigue recto${street}` : `Gira ${side}${street}` };
  }
}

function formatMeters(meters) {
  return meters >= 1000 ? `${(meters / 1000).toFixed(1)} km` : `${Math.max(10, Math.round(meters / 10) * 10)} m`;
}

async function fetchRoute(origin, destination) {
  const base = String(state.dashboard?.map?.routing_url || 'https://router.project-osrm.org').replace(/\/+$/, '');
  const url = `${base}/route/v1/driving/${origin[1]},${origin[0]};${destination[1]},${destination[0]}?overview=full&geometries=geojson&steps=true`;
  const response = await fetch(url, { cache: 'no-store' });
  if (!response.ok) throw new Error('Ruta no disponible');
  const data = await response.json();
  const route = data.routes?.[0];
  if (!route?.geometry?.coordinates?.length) throw new Error('Ruta no disponible');

  const path = route.geometry.coordinates.map(([lng, lat]) => [lat, lng]);
  const cumulative = [0];
  for (let i = 1; i < path.length; i += 1) {
    cumulative.push(cumulative[i - 1] + L.latLng(path[i - 1]).distanceTo(path[i]));
  }
  const nearestIndex = (point) => {
    let best = 0;
    let bestDistance = Infinity;
    path.forEach((p, i) => {
      const d = L.latLng(p).distanceTo(point);
      if (d < bestDistance) { bestDistance = d; best = i; }
    });
    return best;
  };
  const steps = (route.legs?.[0]?.steps || [])
    .filter((step) => step.maneuver?.type !== 'depart')
    .map((step) => ({
      type: step.maneuver.type,
      modifier: step.maneuver.modifier,
      name: step.name,
      index: nearestIndex([step.maneuver.location[1], step.maneuver.location[0]]),
    }));

  return { path, cumulative, total: cumulative[cumulative.length - 1], duration: route.duration * ROUTE_TIME_FACTOR, steps, nearestIndex };
}

/* Comparte la ubicacion con el cliente solo cuando el pedido ya va hacia su direccion. */
function shareCourierLocation(view, point, position) {
  const now = Date.now();
  if (!view.shareGps || now - (view.lastShare || 0) < GPS_SHARE_MS || typeof api !== 'function') return;
  view.lastShare = now;
  const body = {
    latitude: point[0],
    longitude: point[1],
    accuracy_meters: position?.coords?.accuracy ?? null,
    timestamp_gps: new Date(position?.timestamp || now).toISOString(),
    estado: 'en_ruta',
  };
  body[view.orderSource === 'external' ? 'external_order_uuid' : 'pedido_uuid'] = view.orderId;
  api('/gps', { method: 'POST', body: JSON.stringify(body) }).catch(() => {});
}

async function initDeliveryMap(order, element) {
  if (!element) return;
  const phase = deliveryPhase(order);
  const target = mapTarget(order);
  const store = validPoint(order.storeLatitude, order.storeLongitude);
  const customer = validPoint(order.customerLatitude, order.customerLongitude);
  const destination = phase.pickedUp ? customer : store;

  if (!window.L) {
    mapUnavailable(element, 'Mapa no disponible. Usa &laquo;Iniciar navegaci&oacute;n&raquo; para abrir la ruta.');
    return;
  }
  if (!destination) {
    mapUnavailable(element, 'Este pedido no tiene coordenadas. Usa la direcci&oacute;n para navegar.');
    return;
  }

  const mapConfig = state.dashboard?.map || {};
  const map = L.map(element, { zoomControl: false, attributionControl: true }).setView(destination, 15);
  L.tileLayer(mapConfig.tiles_url || 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: mapConfig.tiles_attribution || '&copy; OpenStreetMap',
  }).addTo(map);

  const travelled = L.polyline([], { color: '#94a3b8', weight: 5, opacity: 0.7, dashArray: '2 9', lineCap: 'round' }).addTo(map);
  const pending = L.polyline([], { color: ROUTE_COLOR, weight: 6, opacity: 0.95, lineCap: 'round', lineJoin: 'round' }).addTo(map);
  const wrap = element.closest('.ar-map-wrap');
  const view = {
    map, travelled, pending, destination, route: null, courier: null, courierMarker: null, follow: true,
    orderId: order.id, orderSource: order.source, shareGps: phase.pickedUp, lastRouteAt: 0, routing: false,
  };
  state.deliveryMap = view;

  const short = (text) => escapeHtml(String(text || '').split(',')[0]);
  L.marker(destination, {
    icon: phase.pickedUp
      ? mapPin('home', `<strong>Cliente</strong>${short(order.customerAddress)}`)
      : mapPin('store', `<strong>Comercio</strong>${short(order.storeName)}`),
  }).addTo(map);

  const fitAll = () => {
    const bounds = L.latLngBounds([destination]);
    if (view.courier) bounds.extend(view.courier);
    if (view.route) view.route.path.forEach((p) => bounds.extend(p));
    map.fitBounds(bounds, { padding: [56, 56], maxZoom: 17 });
  };
  map.on('dragstart', () => { view.follow = false; });
  wrap?.querySelector('[data-map-center]')?.addEventListener('click', () => {
    view.follow = true;
    if (view.courier) map.setView(view.courier, 17); else fitAll();
  });
  window.setTimeout(() => map.invalidateSize(), 150);

  const nav = wrap?.querySelector('[data-map-nav]');
  const setText = (selector, html) => {
    const node = document.querySelector(selector);
    if (node) node.innerHTML = html;
  };

  const updateProgress = () => {
    if (!view.route || !view.courier) return;
    const { path, cumulative, total, duration, steps } = view.route;
    const index = view.route.nearestIndex(view.courier);
    const remaining = Math.max(0, total - cumulative[index]);
    const arrived = remaining < ARRIVED_METERS || L.latLng(view.courier).distanceTo(destination) < ARRIVED_METERS;

    travelled.setLatLngs(path.slice(0, index + 1));
    pending.setLatLngs(path.slice(index));

    setText('[data-map-distance]', arrived ? 'En el destino' : formatMeters(remaining));
    setText('[data-map-eta]', arrived ? 'Llegaste' : `${Math.max(1, Math.round((duration * (remaining / Math.max(total, 1))) / 60))} min`);

    if (nav) {
      const next = steps.find((step) => step.index > index) || steps[steps.length - 1];
      nav.hidden = false;
      nav.classList.toggle('is-arrived', arrived);
      if (arrived || !next) {
        nav.querySelector('[data-nav-arrow]').innerHTML = '&#127937;';
        nav.querySelector('[data-nav-dist]').innerHTML = 'Llegaste';
        nav.querySelector('[data-nav-text]').innerHTML = escapeHtml(target.address || target.name || '');
      } else {
        const text = maneuverText(next, target.kind);
        nav.querySelector('[data-nav-arrow]').innerHTML = text.arrow;
        nav.querySelector('[data-nav-dist]').innerHTML = formatMeters(Math.max(0, cumulative[next.index] - cumulative[index]));
        nav.querySelector('[data-nav-text]').innerHTML = text.text;
      }
    }
  };

  const reroute = async (force = false) => {
    if (!view.courier || view.routing) return;
    if (!force && Date.now() - view.lastRouteAt < REROUTE_MIN_MS) return;
    view.routing = true;
    view.lastRouteAt = Date.now();
    try {
      const route = await fetchRoute(view.courier, destination);
      if (state.deliveryMap !== view) return;
      const first = !view.route;
      view.route = route;
      updateProgress();
      if (first) fitAll();
    } catch (_) {
      if (state.deliveryMap === view && !view.route) {
        pending.setLatLngs([view.courier, destination]);
      }
    } finally {
      view.routing = false;
    }
  };

  const onPoint = (point, position) => {
    const firstFix = !view.courier;
    view.courier = point;
    if (!view.courierMarker) {
      view.courierMarker = L.marker(point, { icon: mapPin('courier', ''), zIndexOffset: 1000 }).addTo(map);
    } else {
      view.courierMarker.setLatLng(point);
    }
    shareCourierLocation(view, point, position);

    if (firstFix || !view.route) {
      reroute(true);
      return;
    }
    const nearest = view.route.path[view.route.nearestIndex(point)];
    if (L.latLng(point).distanceTo(nearest) > OFF_ROUTE_METERS) reroute();
    updateProgress();
    if (view.follow) map.panTo(point, { animate: true, duration: 0.4 });
  };

  fitAll();
  const first = await currentPosition();
  if (state.deliveryMap !== view) return;
  if (first) onPoint(first, null);
  watchCourierPosition(view, onPoint);
}
