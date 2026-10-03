const cap = window.Capacitor;
const plugins = cap?.Plugins ?? {};
const Preferences = plugins.Preferences;
const Geolocation = plugins.Geolocation;
const PushNotifications = plugins.PushNotifications;
const LocalNotifications = plugins.LocalNotifications;
const Device = plugins.Device;
const Network = plugins.Network;
const Haptics = plugins.Haptics;
const NativeTracking = plugins.AtlantiaCourierTracking;
const ENABLE_PUSH_NOTIFICATIONS = false;
const REMEMBER_LOGIN_SESSION = false;
const API_PATH_PREFIX = '/api/repartidor';
const DEFAULT_API_BASE_URL = '';
const EMULATOR_API_BASE_URL = 'http://10.0.2.2:8000/api/repartidor';
const COMMON_LOCAL_HOSTS = [
  'http://10.0.2.2:8000/api/repartidor',
  'http://atlantia.local:8000/api/repartidor',
  'http://atlantia.test:8000/api/repartidor',
  'http://localhost:8000/api/repartidor',
  'http://127.0.0.1:8000/api/repartidor',
];
const COMMON_LOCAL_SUBNETS = [
  '192.168.0',
  '192.168.1',
  '192.168.10',
  '192.168.18',
  '192.168.50',
  '192.168.100',
  '10.0.0',
  '10.0.1',
  '10.10.0',
  '172.16.0',
  '172.20.10',
];
const DISCOVERY_PING_TIMEOUT_MS = 1400;
const SPLASH_DURATION_MS = 3000;

const state = {
  apiBaseUrl: DEFAULT_API_BASE_URL,
  token: null,
  user: null,
  dashboard: null,
  gpsTimer: null,
  gpsRunning: false,
  offerCountdownTimer: null,
  dashboardPollTimer: null,
};

const $ = (selector) => document.querySelector(selector);
const money = (value) => `Q ${Number(value || 0).toFixed(2)}`;

window.addEventListener('error', (event) => {
  showRuntimeError(event.error?.message || event.message);
});

window.addEventListener('unhandledrejection', (event) => {
  showRuntimeError(event.reason?.message || 'Ocurrio un error inesperado.');
});

const storage = {
  async get(key) {
    if (Preferences) {
      const result = await Preferences.get({ key });
      if (result.value !== null && result.value !== undefined) {
        return result.value;
      }
    }
    return localStorage.getItem(key);
  },
  async set(key, value) {
    if (Preferences) {
      await Preferences.set({ key, value });
    }
    localStorage.setItem(key, value);
  },
  async remove(key) {
    if (Preferences) {
      await Preferences.remove({ key });
    }
    localStorage.removeItem(key);
  }
};

async function clearSavedSession() {
  await storage.remove('token');
}

async function api(path, options = {}) {
  const headers = {
    Accept: 'application/json',
    ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
    ...(state.token ? { Authorization: `Bearer ${state.token}` } : {}),
    ...(options.headers ?? {}),
  };
  let response;

  try {
    response = await fetch(`${state.apiBaseUrl}${path}`, {
      ...options,
      headers,
    });
  } catch (error) {
    throw new Error(networkHelpMessage(state.apiBaseUrl));
  }

  const data = await response.json().catch(() => ({ message: 'Respuesta no valida del servidor.' }));
  if (!response.ok) {
    throw new Error(data.message || 'No se pudo completar la solicitud.');
  }
  return data;
}

async function boot() {
  window.setTimeout(hideLaunch, SPLASH_DURATION_MS);
  const savedApiBaseUrl = await storage.get('apiBaseUrl');
  state.apiBaseUrl = normalizeApiBaseUrl(savedApiBaseUrl) || DEFAULT_API_BASE_URL;
  state.token = null;
  state.user = null;
  $('[name="api_base_url"]').value = '';

  wireEvents();
  await clearSavedSession();
  showLogin();
}

function wireEvents() {
  $('[data-login-form]').addEventListener('submit', login);
  $('[data-toggle-password]').addEventListener('click', togglePasswordVisibility);
  $('[data-logout]').addEventListener('click', logout);
  document.querySelectorAll('[data-refresh]').forEach((button) => {
    button.addEventListener('click', refreshDashboard);
  });
  $('[data-go-online]').addEventListener('click', () => updateAvailability('available'));
  $('[data-go-offline]').addEventListener('click', () => updateAvailability('offline'));
  $('[data-toggle-gps]').addEventListener('click', toggleGps);
  document.querySelectorAll('[data-tab]').forEach((button) => {
    button.addEventListener('click', () => setActiveTab(button.dataset.tab));
  });
}

async function login(event) {
  event.preventDefault();
  const form = new FormData(event.currentTarget);
  const message = $('[data-login-message]');
  message.textContent = '';
  setLoginLoading(true);
  const manualApiBaseUrl = normalizeApiBaseUrl(form.get('api_base_url'));

  try {
    setLoginLoading(true, 'Buscando servidor');
    state.apiBaseUrl = await resolveApiBaseUrl(manualApiBaseUrl);
    await storage.set('apiBaseUrl', state.apiBaseUrl);

    setLoginLoading(true, 'Ingresando');
    const result = await api('/login', {
      method: 'POST',
      body: JSON.stringify({
        email: form.get('email'),
        password: form.get('password'),
        device_name: 'Atlantia Repartidor Android'
      }),
    });
    state.token = result.data.access_token;
    state.user = result.data.user;
    if (REMEMBER_LOGIN_SESSION) {
      await storage.set('token', state.token);
    } else {
      await storage.remove('token');
    }
    state.dashboard = normalizeDashboard();
    showHome();
    refreshDashboard().catch((error) => {
      showHomeMessage(error.message);
    });
    registerDevice().catch(() => {});
    registerPush().catch(() => {});
  } catch (error) {
    message.textContent = error.message;
  } finally {
    setLoginLoading(false);
  }
}

async function logout() {
  try {
    await api('/logout', { method: 'POST' });
  } catch (_) {
    // Logout local even if the token is already invalid.
  }
  stopGps();
  stopOfferCountdowns();
  stopDashboardPolling();
  await clearSavedSession();
  state.token = null;
  state.user = null;
  showLogin();
}

function showLogin() {
  showHomeMessage('');
  stopOfferCountdowns();
  stopDashboardPolling();
  document.body.classList.remove('home-ready');
  document.body.classList.remove('has-offer');
  const loginScreen = $('[data-screen="login"]');
  const homeScreen = $('[data-screen="home"]');
  const bottomNav = $('[data-bottom-nav]');

  loginScreen.classList.remove('hidden');
  homeScreen.classList.add('hidden');
  bottomNav.classList.add('hidden');
  loginScreen.style.display = '';
  homeScreen.style.display = '';
  bottomNav.style.display = '';
}

function showHome() {
  state.dashboard = normalizeDashboard(state.dashboard);
  $('[data-screen="login"]').classList.add('hidden');
  $('[data-screen="home"]').classList.remove('hidden');
  $('[data-bottom-nav]').classList.remove('hidden');
  document.body.classList.add('home-ready');
  document.body.dataset.availability = state.dashboard?.profile?.availability_status || 'offline';
  const driverName = state.user?.name || 'Repartidor';
  $('[data-driver-name]').textContent = driverName;
  $('[data-driver-initials]').textContent = initialsFromName(driverName);
  setActiveTab('home');
  renderDashboard();
  applyHomeFallbackStyles();
  startDashboardPolling();
  syncNativeTrackingState();
}

function setActiveTab(tab) {
  document.body.dataset.activeTab = tab;
  document.querySelectorAll('[data-tab]').forEach((button) => {
    button.classList.toggle('active', button.dataset.tab === tab);
  });
  document.querySelectorAll('[data-tab-panel]').forEach((panel) => {
    panel.classList.toggle('hidden', panel.dataset.tabPanel !== tab);
  });
  if (document.body.classList.contains('home-ready')) {
    applyHomeFallbackStyles();
  }
}

async function registerDevice(pushToken = null) {
  const info = Device ? await Device.getInfo() : {};
  const id = Device ? await Device.getId() : { identifier: navigator.userAgent };
  await api('/device', {
    method: 'POST',
    body: JSON.stringify({
      platform: 'android',
      device_uuid: id.identifier || navigator.userAgent,
      device_name: info.model || 'Android',
      app_version: '0.1.0',
      push_provider: pushToken ? 'fcm' : 'none',
      push_token: pushToken,
      notifications_enabled: Boolean(pushToken),
    }),
  });
}

async function registerPush() {
  if (!ENABLE_PUSH_NOTIFICATIONS || !PushNotifications) return;

  const permission = await PushNotifications.requestPermissions();
  if (permission.receive !== 'granted') return;

  await PushNotifications.register();
  PushNotifications.addListener('registration', async (token) => {
    await registerDevice(token.value);
  });
  PushNotifications.addListener('pushNotificationReceived', async (notification) => {
    await notifyLocal(notification.title || 'Nuevo pedido', notification.body || 'Tienes una nueva solicitud.');
    await refreshDashboard();
  });
  PushNotifications.addListener('pushNotificationActionPerformed', refreshDashboard);
}

async function notifyLocal(title, body) {
  if (LocalNotifications) {
    await LocalNotifications.schedule({
      notifications: [{
        id: Date.now() % 100000,
        title,
        body,
        sound: 'pedido.wav',
      }],
    });
  }
  if (Haptics) {
    await Haptics.impact({ style: 'HEAVY' }).catch(() => {});
  }
}

async function refreshDashboard() {
  document.body.classList.add('is-refreshing');
  try {
    const [dashboard, offers] = await Promise.all([
      api('/dashboard'),
      api('/offers'),
    ]);
    state.dashboard = normalizeDashboard(dashboard.data);
    state.dashboard.offers = offers.data;
    renderDashboard();
    showHomeMessage('');
  } catch (error) {
    state.dashboard = normalizeDashboard(state.dashboard);
    renderDashboard();
    showHomeMessage(error.message);
    throw error;
  } finally {
    document.body.classList.remove('is-refreshing');
  }
}

function renderDashboard() {
  const data = normalizeDashboard(state.dashboard);
  const wallet = data.wallet || {};
  const availability = data.profile?.availability_status || 'offline';
  const activeDelivery = activeDeliveryFromDashboard(data);
  document.body.dataset.availability = availability;
  $('[data-availability]').textContent = availabilityLabel(availability);
  $('[data-connection-label]').textContent = availability === 'offline' ? 'Desconectado' : 'Conectado';
  $('[data-today-earnings]').textContent = money(wallet.today_earnings);
  $('[data-today-orders]').textContent = `${wallet.today_order_count || 0} pedidos`;
  $('[data-reward-level]').textContent = data.reward?.current || 'Aprendiz';
  $('[data-rating]').textContent = `${Number(data.profile?.rating || 5).toFixed(1)} calificacion`;
  $('[data-wallet-available]').textContent = money(wallet.wallet?.available_balance);
  $('[data-week-earnings]').textContent = money(wallet.current_week_earnings);
  $('[data-tip-earnings]').textContent = money(wallet.tip_earnings);
  $('[data-bonus-earnings]').textContent = money(wallet.bonus_earnings);
  document.body.classList.toggle('has-active-delivery', Boolean(activeDelivery));
  renderOffers(data.offers || []);
  renderCurrentOrder(activeDelivery);
  if ((data.offers || []).length > 0) {
    setActiveTab('offers');
  } else if (activeDelivery && document.body.dataset.activeTab === 'home') {
    setActiveTab('offers');
  }
  applyHomeFallbackStyles();
}

function applyHomeFallbackStyles() {
  const loginScreen = $('[data-screen="login"]');
  const homeScreen = $('[data-screen="home"]');
  const bottomNav = $('[data-bottom-nav]');
  const appShell = $('.app-shell');

  if (loginScreen) {
    loginScreen.style.display = 'none';
  }

  if (appShell) {
    Object.assign(appShell.style, {
      minHeight: '100vh',
      padding: '24px 18px 118px',
      background: 'linear-gradient(180deg, #fff 0%, #fff9fc 62%, #fff1f7 100%)',
    });
  }

  if (homeScreen) {
    Object.assign(homeScreen.style, {
      display: 'block',
      visibility: 'visible',
      opacity: '1',
      color: '#17141a',
    });
  }

  applyStyles('.topbar', {
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: '12px',
    margin: '14px 0 22px',
  });
  applyStyles('.eyebrow', {
    margin: '0 0 6px',
    color: '#99043a',
    fontSize: '13px',
    fontWeight: '900',
    textTransform: 'uppercase',
  });
  applyStyles('[data-driver-name]', {
    display: 'block',
    margin: '0',
    color: '#17141a',
    fontSize: '34px',
    fontWeight: '900',
    lineHeight: '1.08',
  });
  applyStyles('.connection-pill', {
    display: 'inline-flex',
    alignItems: 'center',
    gap: '8px',
    marginTop: '10px',
    padding: '7px 12px',
    border: '1px solid #ead7df',
    borderRadius: '999px',
    background: '#fff',
    color: '#17141a',
    fontWeight: '800',
  });
  applyStyles('.driver-actions', {
    display: 'flex',
    alignItems: 'center',
    gap: '10px',
  });
  applyStyles('.driver-avatar', {
    display: 'grid',
    width: '58px',
    height: '58px',
    placeItems: 'center',
    borderRadius: '50%',
    background: '#99043a',
    color: '#fff',
    fontWeight: '900',
  });
  applyStyles('.logout-button', {
    minHeight: '52px',
    padding: '0 14px',
    border: '1px solid #ead7df',
    borderRadius: '16px',
    background: '#fff',
    color: '#99043a',
    fontWeight: '900',
  });
  applyStyles('.status-card, .gps-card, .metrics-grid article, .empty-state, .current-card, .wallet-panel article, .support-panel article', {
    display: 'block',
    marginBottom: '16px',
    padding: '20px',
    border: '1px solid #ead7df',
    borderRadius: '24px',
    background: '#fff',
    boxShadow: '0 14px 30px rgba(83, 9, 34, .08)',
  });
  applyStyles('.status-main', {
    display: 'flex',
    alignItems: 'center',
    gap: '18px',
  });
  applyStyles('.status-orb', {
    display: 'grid',
    width: '78px',
    height: '78px',
    flex: '0 0 78px',
    placeItems: 'center',
    borderRadius: '50%',
    background: '#f8e4ed',
    color: '#99043a',
  });
  applyStyles('.status-card p, .metrics-grid span, .metrics-grid small, .wallet-panel span, .gps-card p, .meta', {
    color: '#706775',
    fontWeight: '800',
  });
  applyStyles('[data-availability], [data-today-earnings], [data-reward-level], .wallet-panel strong', {
    display: 'block',
    margin: '4px 0',
    color: '#17141a',
    fontSize: '30px',
    fontWeight: '900',
    lineHeight: '1.1',
  });
  applyStyles('.status-actions, .metrics-grid, .button-row', {
    display: 'grid',
    gridTemplateColumns: '1fr 1fr',
    gap: '12px',
    marginTop: '16px',
  });
  applyStyles('.status-option, .gps-card button, .button-row button, .current-actions button, [data-refresh]', {
    minHeight: '52px',
    borderRadius: '16px',
    fontWeight: '900',
  });
  applyStyles('.online-option, .button-row button:first-child, .current-actions button', {
    background: '#99043a',
    color: '#fff',
  });
  applyStyles('.offline-option, .gps-card button, .button-row button:last-child, [data-refresh]', {
    border: '1px solid #99043a',
    background: '#fff',
    color: '#99043a',
  });
  applyStyles('.gps-card', {
    display: 'grid',
    gridTemplateColumns: '70px 1fr',
    gap: '14px',
    alignItems: 'center',
  });
  applyStyles('.gps-card button', {
    gridColumn: '1 / -1',
  });
  applyStyles('.section-title', {
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: '12px',
    margin: '24px 0 14px',
  });
  applyStyles('.section-title h3, .gps-card h3', {
    margin: '0',
    color: '#17141a',
    fontSize: '22px',
    fontWeight: '900',
  });
  applyStyles('.offers-list', {
    display: 'grid',
    gap: '14px',
  });
  applyStyles('.offers-list--incoming', {
    display: 'block',
  });
  applyStyles('.incoming-order-screen', {
    display: 'grid',
    gap: '18px',
    paddingBottom: '6px',
  });
  applyStyles('.incoming-hero', {
    display: 'grid',
    gridTemplateColumns: '1fr',
    gap: '14px',
  });
  applyStyles('.incoming-eyebrow', {
    margin: '0 0 10px',
    color: '#99043a',
    fontSize: '16px',
    fontWeight: '900',
    textTransform: 'uppercase',
  });
  applyStyles('.incoming-hero h1', {
    margin: '0',
    color: '#17141a',
    fontSize: '38px',
    fontWeight: '900',
    lineHeight: '1.04',
  });
  applyStyles('.incoming-hero p:not(.incoming-eyebrow)', {
    margin: '10px 0 0',
    color: '#706775',
    fontSize: '19px',
    lineHeight: '1.3',
  });
  applyStyles('.incoming-countdown', {
    display: 'inline-flex',
    alignItems: 'center',
    justifySelf: 'start',
    gap: '10px',
    minHeight: '66px',
    padding: '0 18px',
    borderRadius: '20px',
    background: '#f8e4ed',
    color: '#99043a',
  });
  applyStyles('.incoming-countdown strong', {
    color: '#99043a',
    fontSize: '30px',
    fontWeight: '900',
  });
  applyStyles('.incoming-card, .incoming-note', {
    overflow: 'hidden',
    border: '1px solid #ead7df',
    borderRadius: '24px',
    background: '#fff',
    boxShadow: '0 14px 30px rgba(83, 9, 34, .08)',
  });
  applyStyles('.incoming-store', {
    display: 'grid',
    gridTemplateColumns: '68px 1fr',
    gap: '16px',
    alignItems: 'center',
    padding: '20px',
    borderBottom: '1px solid #ead7df',
  });
  applyStyles('.incoming-store-icon', {
    display: 'grid',
    width: '58px',
    height: '58px',
    placeItems: 'center',
    borderRadius: '50%',
    background: '#99043a',
    color: '#fff',
  });
  applyStyles('.incoming-store h2', {
    margin: '0',
    color: '#17141a',
    fontSize: '24px',
    fontWeight: '900',
    lineHeight: '1.1',
  });
  applyStyles('.incoming-store span:not(.incoming-store-icon)', {
    display: 'inline-flex',
    marginTop: '8px',
    padding: '6px 10px',
    borderRadius: '8px',
    background: '#f8e4ed',
    color: '#99043a',
    fontSize: '14px',
    fontWeight: '900',
  });
  applyStyles('.incoming-client', {
    display: 'grid',
    gridTemplateColumns: '1fr',
    gap: '14px',
    padding: '20px',
    borderBottom: '1px dashed #ecd2dc',
  });
  applyStyles('.incoming-client span, .route-stop p, .incoming-stats p', {
    color: '#706775',
    fontWeight: '900',
  });
  applyStyles('.incoming-client strong', {
    display: 'block',
    marginTop: '8px',
    color: '#17141a',
    fontSize: '25px',
    fontWeight: '900',
    lineHeight: '1.14',
  });
  applyStyles('.incoming-contact', {
    display: 'flex',
    gap: '12px',
  });
  applyStyles('.incoming-contact a', {
    display: 'grid',
    width: '58px',
    height: '58px',
    placeItems: 'center',
    border: '1px solid #ead7df',
    borderRadius: '18px',
    color: '#99043a',
    background: '#fff',
  });
  applyStyles('.incoming-route', {
    position: 'relative',
    display: 'grid',
    gap: '24px',
    padding: '20px',
  });
  applyStyles('.route-stop', {
    position: 'relative',
    display: 'grid',
    gridTemplateColumns: '28px 1fr',
    gap: '14px',
  });
  applyStyles('.route-stop strong', {
    display: 'block',
    marginBottom: '8px',
    color: '#17141a',
    fontSize: '23px',
    fontWeight: '900',
    lineHeight: '1.12',
  });
  applyStyles('.route-stop div > span', {
    display: 'block',
    color: '#706775',
    fontSize: '17px',
    lineHeight: '1.35',
  });
  applyStyles('.route-stop em', {
    gridColumn: '2',
    justifySelf: 'start',
    padding: '9px 12px',
    borderRadius: '10px',
    background: '#fff0f5',
    color: '#99043a',
    fontStyle: 'normal',
    fontWeight: '900',
  });
  applyStyles('.incoming-map', {
    position: 'relative',
    height: '136px',
    margin: '0 20px 20px',
    overflow: 'hidden',
    border: '1px solid #ead7df',
    borderRadius: '18px',
    background: '#fbfafb',
  });
  applyStyles('.incoming-stats', {
    display: 'grid',
    gridTemplateColumns: '1fr',
    borderTop: '1px solid #ead7df',
  });
  applyStyles('.incoming-stats > div', {
    display: 'grid',
    gridTemplateColumns: '54px 1fr',
    gap: '12px',
    alignItems: 'center',
    padding: '18px',
    borderTop: '1px dashed #ecd2dc',
  });
  applyStyles('.incoming-stats strong', {
    color: '#17141a',
    fontSize: '22px',
    fontWeight: '900',
  });
  applyStyles('.incoming-note', {
    display: 'grid',
    gridTemplateColumns: '54px 1fr',
    alignItems: 'center',
    gap: '14px',
    padding: '18px',
  });
  applyStyles('.incoming-note strong', {
    color: '#17141a',
    fontSize: '18px',
    fontWeight: '900',
  });
  applyStyles('.incoming-note p, .incoming-auto', {
    margin: '0',
    color: '#706775',
    fontSize: '16px',
    lineHeight: '1.35',
  });
  applyStyles('.incoming-actions', {
    display: 'grid',
    gridTemplateColumns: '1fr',
    gap: '14px',
  });
  applyStyles('.incoming-actions button', {
    minHeight: '70px',
    borderRadius: '20px',
    fontSize: '22px',
    fontWeight: '900',
  });
  applyStyles('.incoming-reject', {
    border: '1px solid #ead7df',
    background: '#fff',
    color: '#99043a',
  });
  applyStyles('.incoming-accept', {
    display: 'inline-flex',
    alignItems: 'center',
    justifyContent: 'center',
    gap: '12px',
    border: '0',
    background: '#99043a',
    color: '#fff',
  });
  applyStyles('.incoming-auto', {
    textAlign: 'center',
  });
  applyStyles('.empty-state', {
    display: 'flex',
    alignItems: 'center',
    gap: '16px',
  });
  applyStyles('.empty-state strong', {
    display: 'block',
    color: '#17141a',
    fontSize: '18px',
    fontWeight: '900',
  });
  applyStyles('.empty-state p', {
    margin: '6px 0 0',
    color: '#706775',
    fontSize: '14px',
    fontWeight: '700',
  });
  applyStyles('.bottom-nav', {
    position: 'fixed',
    right: '18px',
    bottom: '10px',
    left: '18px',
    zIndex: '12',
    display: 'grid',
    gridTemplateColumns: 'repeat(4, 1fr)',
    padding: '8px',
    border: '1px solid #ead7df',
    borderRadius: '22px',
    background: '#fff',
    boxShadow: '0 12px 34px rgba(83, 9, 34, .11)',
  });
  applyStyles('.bottom-nav button', {
    minHeight: '64px',
    border: '0',
    borderRadius: '16px',
    background: 'transparent',
    color: '#706775',
    fontWeight: '900',
  });
  applyStyles('.bottom-nav button.active', {
    background: '#f8e4ed',
    color: '#99043a',
  });

  if (bottomNav) {
    bottomNav.style.display = 'grid';
  }

  const showingIncoming = document.body.dataset.activeTab === 'offers' && document.body.classList.contains('has-offer');
  const showingAccepted = document.body.dataset.activeTab === 'offers'
    && document.body.classList.contains('has-active-delivery')
    && !document.body.classList.contains('has-offer');
  applyStyles('.topbar', {
    display: (showingIncoming || showingAccepted) ? 'none' : 'flex',
  });
  applyStyles('[data-tab-panel="offers"] .section-title', {
    display: (showingIncoming || showingAccepted) ? 'none' : 'flex',
  });
  applyStyles('[data-tab-panel="offers"] .offers-section', {
    display: showingAccepted ? 'none' : 'block',
  });
  applyStyles('[data-tab-panel="offers"] .current-section', {
    marginTop: showingAccepted ? '0' : '26px',
  });
}

function applyStyles(selector, styles) {
  document.querySelectorAll(selector).forEach((element) => {
    Object.assign(element.style, styles);
  });
}

function normalizeDashboard(data = {}) {
  const base = {
    profile: {
      availability_status: 'offline',
      rating: 5,
    },
    wallet: {
      today_earnings: 0,
      today_order_count: 0,
      current_week_earnings: 0,
      tip_earnings: 0,
      bonus_earnings: 0,
      wallet: {
        available_balance: 0,
      },
    },
    reward: {
      current: 'Aprendiz',
    },
    offers: [],
    current_order: null,
    external_active: [],
  };

  return {
    ...base,
    ...data,
    profile: {
      ...base.profile,
      ...(data.profile || {}),
    },
    wallet: {
      ...base.wallet,
      ...(data.wallet || {}),
      wallet: {
        ...base.wallet.wallet,
        ...(data.wallet?.wallet || {}),
      },
    },
    reward: {
      ...base.reward,
      ...(data.reward || {}),
    },
    offers: Array.isArray(data.offers) ? data.offers : base.offers,
    current_order: data.current_order || null,
    external_active: Array.isArray(data.external_active) ? data.external_active : base.external_active,
  };
}

function availabilityLabel(status) {
  return {
    offline: 'Desconectado',
    available: 'En linea',
    busy: 'En entrega',
    paused: 'Pausado',
    emergency: 'Emergencia',
  }[status] || 'Desconectado';
}

function renderOffers(offers) {
  const lists = document.querySelectorAll('[data-offers-list]');
  document.body.classList.toggle('has-offer', offers.length > 0);
  startOfferCountdowns(offers);

  const compactHtml = offers.length ? offers.map((offer) => renderOfferPreview(offer)).join('') : `
    <div class="empty-state">
      <span class="empty-icon icon-bag" aria-hidden="true"></span>
      <div>
        <strong>Sin pedidos entrantes.</strong>
        <p>Te notificaremos cuando tengas nuevos pedidos.</p>
      </div>
    </div>
  `;

  lists.forEach((list) => {
    const tabPanel = list.closest('[data-tab-panel]');
    const isOffersTab = tabPanel?.dataset.tabPanel === 'offers';
    list.classList.toggle('offers-list--incoming', isOffersTab && offers.length > 0);
    list.innerHTML = offers.length && isOffersTab ? renderIncomingOffer(offers[0]) : compactHtml;
    list.querySelectorAll('[data-accept-offer]').forEach((button) => {
      button.addEventListener('click', () => handleOffer(button.dataset.acceptOffer, true));
    });
    list.querySelectorAll('[data-reject-offer]').forEach((button) => {
      button.addEventListener('click', () => handleOffer(button.dataset.rejectOffer, false));
    });
  });

  updateOfferCountdowns();
}

function renderOfferPreview(offer) {
  return `
    <article>
      <div class="offer-top">
        <div>
          <h4>${escapeHtml(offer.business_name || 'Comercio')}</h4>
          <p class="meta">${escapeHtml(offer.pickup_address || 'Punto de recogida')}<br>${escapeHtml(offer.delivery_zone || 'Zona de entrega')}</p>
        </div>
        <span class="price">${money(offer.estimated_gain)}</span>
      </div>
      <p class="meta">${Number(offer.total_distance_km || 0).toFixed(1)} km - ${escapeHtml(offer.payment_method || 'digital')}</p>
      <div class="button-row">
        <button type="button" data-accept-offer="${offer.id}">Aceptar</button>
        <button type="button" data-reject-offer="${offer.id}">Rechazar</button>
      </div>
    </article>
  `;
}

function renderIncomingOffer(offer) {
  const details = offerDetails(offer);
  return `
    <section class="incoming-order-screen">
      <header class="incoming-hero">
        <div>
          <p class="incoming-eyebrow">Atlantia Repartidor</p>
          <h1>Pedido entrante</h1>
          <p>Tienes un nuevo pedido. Revisa los detalles.</p>
        </div>
        <div class="incoming-countdown" data-offer-expires="${escapeHtml(offer.expires_at || '')}">
          <span class="countdown-icon" aria-hidden="true"></span>
          <strong data-offer-countdown>${escapeHtml(countdownText(offer))}</strong>
        </div>
      </header>

      <article class="incoming-card">
        <div class="incoming-store">
          <span class="incoming-store-icon icon-bag" aria-hidden="true"></span>
          <div>
            <h2>${escapeHtml(details.storeName)}</h2>
            <span>${escapeHtml(details.category)}</span>
          </div>
        </div>

        <div class="incoming-client">
          <div>
            <span>Cliente</span>
            <strong>${escapeHtml(details.customerName)}</strong>
          </div>
          <div class="incoming-contact">
            <a href="${details.customerPhone ? `tel:${escapeHtml(details.customerPhone)}` : '#'}" aria-label="Llamar al cliente">
              <span class="phone-icon" aria-hidden="true"></span>
            </a>
            <a href="${details.customerPhone ? `sms:${escapeHtml(details.customerPhone)}` : '#'}" aria-label="Enviar mensaje al cliente">
              <span class="chat-icon" aria-hidden="true"></span>
            </a>
          </div>
        </div>

        <div class="incoming-route">
          <div class="route-line" aria-hidden="true"></div>
          <div class="route-stop pickup">
            <span class="route-dot" aria-hidden="true"></span>
            <div>
              <p>Recoger en</p>
              <strong>${escapeHtml(details.pickupName)}</strong>
              <span>${escapeHtml(details.pickupAddress)}</span>
            </div>
            <em>${escapeHtml(details.pickupDistance)}</em>
          </div>
          <div class="route-stop dropoff">
            <span class="route-dot" aria-hidden="true"></span>
            <div>
              <p>Entregar en</p>
              <strong>${escapeHtml(details.deliveryName)}</strong>
              <span>${escapeHtml(details.deliveryAddress)}</span>
            </div>
            <em>${escapeHtml(details.totalDistance)}</em>
          </div>
        </div>

        <div class="incoming-map" aria-label="Ruta aproximada">
          <span class="map-pin pickup-pin icon-bag" aria-hidden="true"></span>
          <span class="map-pin dropoff-pin" aria-hidden="true"></span>
          <span class="map-zone pickup-zone">${escapeHtml(details.pickupZone)}</span>
          <span class="map-zone dropoff-zone">${escapeHtml(details.deliveryZone)}</span>
          <svg viewBox="0 0 320 90" role="img" aria-hidden="true">
            <path d="M28 48 C64 20, 94 70, 128 42 S184 50, 218 38 S262 74, 294 30" />
          </svg>
        </div>

        <div class="incoming-stats">
          <div>
            <span class="stat-orb clock-icon" aria-hidden="true"></span>
            <p>Tiempo estimado</p>
            <strong>${escapeHtml(details.estimatedTime)}</strong>
          </div>
          <div>
            <span class="stat-orb distance-icon" aria-hidden="true"></span>
            <p>Distancia total</p>
            <strong>${escapeHtml(details.totalDistance)}</strong>
          </div>
          <div>
            <span class="stat-orb wallet-icon" aria-hidden="true"></span>
            <p>Ganancia estimada</p>
            <strong>${escapeHtml(details.earning)}</strong>
            <small>${escapeHtml(details.tipLabel)}</small>
          </div>
        </div>
      </article>

      <aside class="incoming-note">
        <span class="shield-icon" aria-hidden="true"></span>
        <div>
          <strong>Acepta pedidos con responsabilidad</strong>
          <p>Manten una excelente atencion y puntualidad.</p>
        </div>
      </aside>

      <div class="incoming-actions">
        <button class="incoming-reject" type="button" data-reject-offer="${offer.id}">Rechazar</button>
        <button class="incoming-accept" type="button" data-accept-offer="${offer.id}">
          <span class="check-icon" aria-hidden="true"></span>
          Aceptar pedido
        </button>
      </div>

      <p class="incoming-auto">La solicitud se rechazara automaticamente al finalizar el tiempo.</p>
    </section>
  `;
}

function offerDetails(offer) {
  const internal = offer.internal_order || {};
  const external = offer.external_order || {};
  const vendor = internal.vendor || external.store || {};
  const customer = internal.customer || external.customer || {};
  const route = internal.route || {};
  const totalDistance = numberOrFallback(
    offer.total_distance_km,
    external.estimated_distance_km,
    route.distance_km,
    offer.delivery_distance_km,
    4.7
  );
  const pickupDistance = numberOrFallback(offer.pickup_distance_km, 2.4);
  const estimatedTime = numberOrFallback(external.estimated_time_min, route.estimated_time_min, Math.max(12, Math.round(totalDistance * 5)));
  const tipAmount = numberOrFallback(external.tip_amount, internal.earning?.tip, 0);
  const deliveryName = customer.address ? firstAddressLine(customer.address) : (offer.delivery_zone || 'Direccion de entrega');

  return {
    storeName: vendor.name || offer.business_name || 'Comercio',
    category: offer.source_type === 'internal' ? 'Supermercado' : (offer.source_type === 'external' ? 'Comercio aliado' : 'Restaurante'),
    customerName: customer.name || 'Cliente',
    customerPhone: customer.phone || '',
    pickupName: vendor.name || offer.business_name || 'Comercio',
    pickupAddress: vendor.address || offer.pickup_address || 'Direccion de recogida',
    pickupDistance: `${formatDistance(pickupDistance)} km`,
    deliveryName,
    deliveryAddress: customer.address || offer.delivery_zone || 'Direccion de entrega',
    totalDistance: `${formatDistance(totalDistance)} km`,
    pickupZone: extractZone(vendor.address || offer.pickup_address || '', 'ORIGEN'),
    deliveryZone: extractZone(customer.address || offer.delivery_zone || '', 'DESTINO'),
    estimatedTime: `${Math.max(1, Math.round(estimatedTime))} min`,
    earning: money(offer.estimated_gain || external.courier_earning || internal.earning?.estimated || 0),
    tipLabel: tipAmount > 0 ? 'Incluye propina' : paymentLabel(offer.payment_method || external.payment_method || internal.payment_method),
  };
}

function activeDeliveryFromDashboard(data) {
  if (data.current_order) {
    return normalizeActiveDelivery(data.current_order, 'internal');
  }

  const external = Array.isArray(data.external_active) ? data.external_active[0] : null;
  return external ? normalizeActiveDelivery(external, 'external') : null;
}

function normalizeActiveDelivery(order, source) {
  const isExternal = source === 'external' || order.source_type === 'external' || Boolean(order.store);
  const store = isExternal ? (order.store || {}) : (order.vendor || {});
  const customer = order.customer || {};
  const cash = order.cash || {};
  const route = order.route || {};
  const timeline = isExternal ? (order.timeline || {}) : (route.timeline || {});
  const rawItems = Array.isArray(order.items) && order.items.length
    ? order.items
    : [{
        quantity: 1,
        name: order.external_reference ? `Entrega ${order.external_reference}` : 'Servicio de entrega',
        subtotal: numberOrFallback(cash.to_collect, order.total, order.delivery_fee, 0),
      }];
  const items = rawItems.map((item) => ({
    quantity: Number(item.quantity || item.cantidad || 1),
    name: item.name || item.producto_nombre_snapshot || 'Producto',
    subtotal: Number(item.subtotal || item.total || 0),
  }));
  const itemsSubtotal = items.reduce((sum, item) => sum + Number(item.subtotal || 0), 0);
  const deliveryFee = Number(order.delivery_fee || 0);
  const discount = Number(order.discount || 0);
  const subtotal = numberOrFallback(order.subtotal, itemsSubtotal, Number(cash.to_collect || 0) - deliveryFee + discount, 0);
  const total = numberOrFallback(cash.to_collect, order.total, subtotal + deliveryFee - discount, 0);
  const distanceKm = numberOrFallback(order.estimated_distance_km, route.distance_km, 0);
  const estimatedTime = numberOrFallback(order.estimated_time_min, route.estimated_time_min, Math.max(12, Math.round(distanceKm * 5)));

  return {
    id: order.id,
    source: isExternal ? 'external' : 'internal',
    status: order.status || route.status || 'accepted',
    routeStatus: route.status || '',
    number: order.number || order.external_reference || '#ATL-98231',
    paymentMethod: order.payment_method || 'efectivo',
    storeName: store.name || 'Comercio',
    storeAddress: store.address || 'Direccion del comercio',
    storePhone: store.phone || '',
    storeLatitude: store.latitude,
    storeLongitude: store.longitude,
    customerName: customer.name || 'Cliente',
    customerPhone: customer.phone || '',
    customerAddress: customer.address || 'Direccion de entrega',
    customerReference: customer.reference || customer.notes || '',
    customerLatitude: customer.latitude,
    customerLongitude: customer.longitude,
    items,
    itemsCount: items.reduce((sum, item) => sum + Number(item.quantity || 0), 0),
    subtotal,
    deliveryFee,
    discount,
    total,
    earning: numberOrFallback(order.earning?.estimated, order.courier_earning, 0),
    distanceKm,
    estimatedTime,
    timeline,
  };
}

function renderAcceptedOrder(order) {
  const driverName = state.user?.name || 'Henry Diaz';
  const firstName = String(driverName).trim().split(/\s+/)[0] || 'Henry';
  const status = acceptedDeliveryStatus(order);
  const pickupAction = nextPickupAction(order);
  const mapUrl = mapsUrl(order.storeAddress, order.storeLatitude, order.storeLongitude);
  const customerPhone = cleanPhone(order.customerPhone);
  const storePhone = cleanPhone(order.storePhone);
  const callTarget = customerPhone || storePhone;
  const itemRows = order.items.map((item) => `
    <div class="accepted-summary-row">
      <span>${Number(item.quantity || 1)}</span>
      <strong>${escapeHtml(item.name)}</strong>
      <em>${money(item.subtotal)}</em>
    </div>
  `).join('');

  return `
    <section class="accepted-order-screen">
      <header class="accepted-hero">
        <div>
          <p class="accepted-eyebrow">Atlantia Repartidor</p>
          <h1>Hola, ${escapeHtml(firstName)}</h1>
          <span class="accepted-status-pill"><i></i>${escapeHtml(status.label)}</span>
        </div>
        <div class="accepted-profile">
          <span class="accepted-avatar">${escapeHtml(initialsFromName(driverName))}</span>
          <button type="button" data-logout class="accepted-logout"><span aria-hidden="true">-&gt;</span>Salir</button>
        </div>
      </header>

      ${renderAcceptedProgress(order)}

      <article class="accepted-store-card">
        <span class="accepted-store-logo">${escapeHtml(storeMark(order.storeName))}</span>
        <div>
          <h2>${escapeHtml(order.storeName)}</h2>
          <p>${escapeHtml(order.storeAddress)}</p>
        </div>
        <a class="accepted-outline-action" href="${storePhone ? `tel:${escapeHtml(storePhone)}` : '#'}">
          <span class="phone-icon" aria-hidden="true"></span>
          Llamar
        </a>
      </article>

      <article class="accepted-info-card">
        <div>
          <span class="accepted-info-icon accepted-user-icon" aria-hidden="true"></span>
          <p>Cliente</p>
          <strong>${escapeHtml(order.customerName)}</strong>
          <small>${customerPhone ? `Tel. ${escapeHtml(customerPhone)}` : 'Sin telefono'}</small>
        </div>
        <div>
          <span class="accepted-info-icon accepted-card-icon" aria-hidden="true"></span>
          <p>M&eacute;todo de pago</p>
          <strong>${escapeHtml(paymentLabel(order.paymentMethod).replace('Pago ', ''))}</strong>
        </div>
        <div>
          <span class="accepted-info-icon accepted-number-icon" aria-hidden="true">#</span>
          <p>Pedido #</p>
          <strong>${escapeHtml(order.number)}</strong>
          <small>${escapeHtml(formatTimelineTime(order.timeline.accepted_at || order.timeline.assigned_at || order.timeline.requested_at))}</small>
        </div>
      </article>

      <article class="accepted-summary-card">
        <div class="accepted-card-title">
          <h3><span class="accepted-mini-icon icon-bag" aria-hidden="true"></span>Resumen del pedido</h3>
          <span>${order.itemsCount || order.items.length} productos</span>
        </div>
        <div class="accepted-summary-list">${itemRows}</div>
        <div class="accepted-totals">
          <div><span>Subtotal</span><strong>${money(order.subtotal)}</strong></div>
          <div><span>Tarifa de entrega</span><strong>${money(order.deliveryFee)}</strong></div>
          ${order.discount > 0 ? `<div><span>Descuento</span><strong>- ${money(order.discount)}</strong></div>` : ''}
          <div class="accepted-total"><span>Total a cobrar</span><strong>${money(order.total)}</strong></div>
        </div>
      </article>

      <article class="accepted-address-card">
        <span class="accepted-address-icon" aria-hidden="true"></span>
        <div>
          <p>Direcci&oacute;n de entrega</p>
          <strong>${escapeHtml(order.customerAddress)}</strong>
          ${order.customerReference ? `<small>${escapeHtml(order.customerReference)}</small>` : ''}
        </div>
        <div class="accepted-mini-map" aria-hidden="true">
          <span></span>
          <svg viewBox="0 0 190 82">
            <path d="M18 60 L58 28 L88 56 L125 22 L170 42" />
            <circle cx="170" cy="42" r="5" />
          </svg>
          <a href="${mapsUrl(order.customerAddress, order.customerLatitude, order.customerLongitude)}" target="_blank" rel="noopener">Ver en mapa</a>
        </div>
      </article>

      <aside class="accepted-warning">
        <span aria-hidden="true">!</span>
        Dir&iacute;gete al comercio para recoger el pedido.
      </aside>

      <div class="accepted-actions">
        <a class="accepted-call" href="${callTarget ? `tel:${escapeHtml(callTarget)}` : '#'}">
          <span class="phone-icon" aria-hidden="true"></span>
          Llamar
        </a>
        <a class="accepted-nav-action" href="${mapUrl}" target="_blank" rel="noopener">
          <span class="accepted-nav-arrow" aria-hidden="true"></span>
          <strong>Ir al comercio</strong>
          <small>Iniciar navegaci&oacute;n</small>
        </a>
        <button type="button" class="accepted-pickup-action" data-order-id="${escapeHtml(order.id)}" data-order-source="${escapeHtml(order.source)}" data-order-action="${escapeHtml(pickupAction.action)}">
          <span class="accepted-checkmark" aria-hidden="true"></span>
          <strong>${escapeHtml(pickupAction.label)}</strong>
          <small>${escapeHtml(pickupAction.caption)}</small>
        </button>
      </div>
    </section>
  `;
}

function renderAcceptedProgress(order) {
  const activeIndex = acceptedStepIndex(order);
  const nowTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  const steps = [
    { label: 'Aceptado', caption: formatTimelineTime(order.timeline.accepted_at || order.timeline.assigned_at, nowTime), icon: 'check' },
    { label: 'En camino al comercio', caption: formatTimelineTime(order.timeline.accepted_at || order.timeline.assigned_at, nowTime), icon: 'bike' },
    { label: 'Recoger pedido', caption: formatTimelineTime(order.timeline.picked_up_at), icon: 'bag' },
    { label: 'Entregar', caption: formatTimelineTime(order.timeline.arrived_customer_at || order.timeline.completed_at || order.timeline.delivered_at), icon: 'box' },
  ];

  return `
    <ol class="accepted-progress">
      ${steps.map((step, index) => `
        <li class="accepted-step ${index < activeIndex ? 'is-done' : ''} ${index === activeIndex ? 'is-active' : ''}">
          <span class="accepted-step-dot accepted-step-dot--${step.icon}" aria-hidden="true"></span>
          <strong>${escapeHtml(step.label)}</strong>
          <small>${escapeHtml(step.caption)}</small>
        </li>
      `).join('')}
    </ol>
  `;
}

function acceptedDeliveryStatus(order) {
  const raw = `${order.status} ${order.routeStatus}`.toLowerCase();
  if (raw.includes('offline')) return { label: 'Desconectado' };
  if (raw.includes('delivered') || raw.includes('entregado')) return { label: 'Completado' };
  if (raw.includes('picked') || raw.includes('iniciada') || raw.includes('en_ruta')) return { label: 'En ruta' };
  return { label: 'En linea' };
}

function acceptedStepIndex(order) {
  const raw = `${order.status} ${order.routeStatus}`.toLowerCase();
  if (raw.includes('delivered') || raw.includes('entregado') || raw.includes('completed') || raw.includes('completada')) return 3;
  if (raw.includes('arrived_customer')) return 3;
  if (raw.includes('picked') || raw.includes('iniciada') || raw.includes('en_ruta')) return 3;
  if (raw.includes('arrived_pickup') || raw.includes('pickup_not_ready')) return 2;
  return 1;
}

function nextPickupAction(order) {
  const raw = `${order.status} ${order.routeStatus}`.toLowerCase();
  const canPickup = raw.includes('arrived_pickup')
    || raw.includes('pickup_not_ready')
    || raw.includes('listo')
    || raw.includes('preparado');

  return canPickup
    ? { action: 'pickup', label: 'Marcar como recogido', caption: 'En el comercio' }
    : { action: 'arrived-pickup', label: 'Marcar llegada', caption: 'En el comercio' };
}

function formatTimelineTime(value, fallback = '') {
  if (!value) return fallback;
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return String(value);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function storeMark(name) {
  const parts = String(name || 'Atlantia')
    .trim()
    .split(/\s+/)
    .filter(Boolean);
  return (parts[0]?.[0] || 'A') + (parts[1]?.[0] || '');
}

function cleanPhone(phone) {
  return String(phone || '').trim();
}

function mapsUrl(address, latitude, longitude) {
  if (Number.isFinite(Number(latitude)) && Number.isFinite(Number(longitude))) {
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${latitude},${longitude}`)}`;
  }

  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address || 'Atlantia')}`;
}

function numberOrFallback(...values) {
  const found = values.find((value) => Number.isFinite(Number(value)) && Number(value) > 0);
  return Number(found || 0);
}

function formatDistance(value) {
  const number = Number(value || 0);
  return number >= 10 ? number.toFixed(0) : number.toFixed(1);
}

function firstAddressLine(value) {
  return String(value || '').split(',')[0].trim() || 'Direccion de entrega';
}

function extractZone(value, fallback) {
  const text = String(value || '');
  const zone = text.match(/zona\s+\d+/i)?.[0];
  if (zone) return zone.toUpperCase();

  const parts = text.split(',').map((part) => part.trim()).filter(Boolean);
  return (parts[parts.length - 1] || fallback).toUpperCase();
}

function paymentLabel(method) {
  return String(method || '').toLowerCase().includes('efectivo') ? 'Pago en efectivo' : 'Pago digital';
}

function countdownText(offer) {
  if (!offer.expires_at) return '00:28';

  return formatCountdown(new Date(offer.expires_at).getTime() - Date.now());
}

function formatCountdown(milliseconds) {
  const totalSeconds = Math.max(0, Math.ceil(Number(milliseconds || 0) / 1000));
  const minutes = Math.floor(totalSeconds / 60);
  const seconds = totalSeconds % 60;
  return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

function startOfferCountdowns(offers) {
  stopOfferCountdowns();
  if (!offers.length) return;

  state.offerCountdownTimer = window.setInterval(updateOfferCountdowns, 1000);
}

function stopOfferCountdowns() {
  if (!state.offerCountdownTimer) return;

  window.clearInterval(state.offerCountdownTimer);
  state.offerCountdownTimer = null;
}

function startDashboardPolling() {
  stopDashboardPolling();
  state.dashboardPollTimer = window.setInterval(() => {
    refreshDashboard().catch(() => {});
  }, 15000);
}

function stopDashboardPolling() {
  if (!state.dashboardPollTimer) return;

  window.clearInterval(state.dashboardPollTimer);
  state.dashboardPollTimer = null;
}

function updateOfferCountdowns() {
  let expired = false;
  document.querySelectorAll('[data-offer-expires]').forEach((element) => {
    const expiresAt = element.getAttribute('data-offer-expires');
    const countdown = element.querySelector('[data-offer-countdown]');
    if (!countdown || !expiresAt) return;

    const remaining = new Date(expiresAt).getTime() - Date.now();
    countdown.textContent = formatCountdown(remaining);
    if (remaining <= 0) {
      expired = true;
    }
  });

  if (expired) {
    stopOfferCountdowns();
    refreshDashboard().catch(() => {});
  }
}

function renderCurrentOrder(order) {
  const containers = document.querySelectorAll('[data-current-order]');

  const emptyHtml = `
    <span class="empty-icon icon-route" aria-hidden="true"></span>
    <div>
      <strong>Sin entrega activa.</strong>
      <p>Acepta un pedido para ver los detalles aqui.</p>
    </div>
  `;

  containers.forEach((container) => {
    const tabPanel = container.closest('[data-tab-panel]');
    const isOrdersTab = tabPanel?.dataset.tabPanel === 'offers';

    if (!order) {
      container.className = 'empty-state route-empty';
      container.innerHTML = emptyHtml;
    } else if (isOrdersTab) {
      container.className = 'accepted-order-wrap';
      container.innerHTML = renderAcceptedOrder(order);
    } else {
      container.className = 'current-card';
      container.innerHTML = `
        <div class="current-top">
          <div>
            <h4>${escapeHtml(order.storeName)}</h4>
            <p class="meta">${escapeHtml(order.storeAddress)}<br>${escapeHtml(order.customerAddress)}</p>
          </div>
          <span class="price">${money(order.earning || order.total)}</span>
        </div>
        <p class="meta">Estado: ${escapeHtml(order.status)} - Pago: ${escapeHtml(paymentLabel(order.paymentMethod))}</p>
        <div class="current-actions">
          <button type="button" data-order-action="arrived-pickup" data-order-source="${escapeHtml(order.source)}" data-order-id="${escapeHtml(order.id)}">Llegue al comercio</button>
          <button type="button" data-order-action="pickup" data-order-source="${escapeHtml(order.source)}" data-order-id="${escapeHtml(order.id)}">Pedido recogido</button>
          <button type="button" data-order-action="arrived-customer" data-order-source="${escapeHtml(order.source)}" data-order-id="${escapeHtml(order.id)}">Llegue al cliente</button>
          <button type="button" data-order-action="deliver" data-order-source="${escapeHtml(order.source)}" data-order-id="${escapeHtml(order.id)}">Entregar con codigo</button>
        </div>
      `;
    }

    container.querySelectorAll('[data-logout]').forEach((button) => {
      button.addEventListener('click', logout);
    });
    container.querySelectorAll('[data-order-action]').forEach((button) => {
      button.addEventListener('click', () => handleOrderAction(
        button.dataset.orderId,
        button.dataset.orderAction,
        button.dataset.orderSource || 'internal'
      ));
    });
  });
}

async function handleOffer(id, accept) {
  try {
    await api(`/offers/${id}/${accept ? 'accept' : 'reject'}`, {
      method: 'PATCH',
      body: JSON.stringify(accept ? {} : { reason: 'No disponible' }),
    });
    await notifyLocal(accept ? 'Pedido aceptado' : 'Pedido rechazado', accept ? 'Dirigete al comercio.' : 'Oferta cerrada.');
    await refreshDashboard();
  } catch (error) {
    alert(error.message);
  }
}

async function handleOrderAction(id, action, source = 'internal') {
  const body = {};
  if (action === 'deliver') {
    const code = prompt('Codigo de entrega de 4 digitos');
    if (!code) return;
    body.confirmation_code = code;
  }
  try {
    await api(`/${source === 'external' ? 'external-orders' : 'orders'}/${id}/${action}`, {
      method: 'PATCH',
      body: JSON.stringify(body),
    });
    await refreshDashboard();
  } catch (error) {
    alert(error.message);
  }
}

async function updateAvailability(status) {
  try {
    await api('/availability', {
      method: 'PATCH',
      body: JSON.stringify({ availability_status: status, service_scope: 'both' }),
    });
    await refreshDashboard();
  } catch (error) {
    alert(error.message);
  }
}

async function toggleGps() {
  if (state.gpsRunning) {
    stopGps();
    return;
  }
  await startGps();
}

async function startGps() {
  if (NativeTracking) {
    try {
      const estado = state.dashboard?.profile?.availability_status === 'available' ? 'disponible' : 'en_ruta';
      await NativeTracking.start({
        apiBaseUrl: state.apiBaseUrl,
        token: state.token,
        intervalMs: 15000,
        estado,
      });
      state.gpsRunning = true;
      $('[data-toggle-gps]').textContent = 'Detener GPS';
      $('[data-gps-status]').textContent = 'Seguimiento nativo activo, incluso con pantalla bloqueada.';
    } catch (error) {
      $('[data-gps-status]').textContent = `No se pudo iniciar GPS nativo: ${error.message}`;
    }
    return;
  }

  if (!Geolocation) {
    $('[data-gps-status]').textContent = 'GPS nativo no disponible en navegador.';
    return;
  }
  const permission = await Geolocation.requestPermissions();
  if (permission.location !== 'granted' && permission.coarseLocation !== 'granted') {
    $('[data-gps-status]').textContent = 'Permiso GPS denegado.';
    return;
  }
  state.gpsRunning = true;
  $('[data-toggle-gps]').textContent = 'Detener GPS';
  await sendGps();
  state.gpsTimer = setInterval(sendGps, 15000);
}

function stopGps() {
  state.gpsRunning = false;
  if (state.gpsTimer) clearInterval(state.gpsTimer);
  state.gpsTimer = null;
  if (NativeTracking) {
    NativeTracking.stop().catch(() => {});
  }
  $('[data-toggle-gps]').textContent = 'Iniciar GPS';
  $('[data-gps-status]').textContent = 'Seguimiento detenido.';
}

async function syncNativeTrackingState() {
  if (!NativeTracking) return;

  try {
    const result = await NativeTracking.isRunning();
    state.gpsRunning = Boolean(result.running);
    $('[data-toggle-gps]').textContent = state.gpsRunning ? 'Detener GPS' : 'Iniciar GPS';
    $('[data-gps-status]').textContent = state.gpsRunning
      ? 'Seguimiento nativo activo, incluso con pantalla bloqueada.'
      : 'Listo para iniciar seguimiento.';
  } catch (_) {
    state.gpsRunning = false;
  }
}

async function sendGps() {
  try {
    const network = Network ? await Network.getStatus() : { connected: true };
    const position = await Geolocation.getCurrentPosition({ enableHighAccuracy: true, timeout: 10000 });
    await api('/gps', {
      method: 'POST',
      body: JSON.stringify({
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
        accuracy_meters: position.coords.accuracy,
        timestamp_gps: new Date(position.timestamp).toISOString(),
        estado: state.dashboard?.profile?.availability_status === 'available' ? 'disponible' : 'en_ruta',
      }),
    });
    $('[data-gps-status]').textContent = `GPS enviado ${new Date().toLocaleTimeString()} - ${network.connected ? 'online' : 'sin conexion'}`;
  } catch (error) {
    $('[data-gps-status]').textContent = `GPS pendiente: ${error.message}`;
  }
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function initialsFromName(name) {
  const parts = String(name || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean);

  return (parts[0]?.[0] || 'A') + (parts[1]?.[0] || 'R');
}

async function resolveApiBaseUrl(preferredApiBaseUrl = '') {
  const candidates = discoveryCandidates(preferredApiBaseUrl);
  const found = await firstReachableApiBaseUrl(candidates);

  if (!found) {
    throw new Error('No encontre el servidor Atlantia en esta red. Confirma que Laravel este encendido con php artisan serve --host=0.0.0.0 --port=8000 y que el celular este en la misma Wi-Fi.');
  }

  return found;
}

function discoveryCandidates(preferredApiBaseUrl = '') {
  const preferred = normalizeApiBaseUrl(preferredApiBaseUrl);
  const saved = normalizeApiBaseUrl(state.apiBaseUrl);
  const candidates = [
    preferred,
    saved,
    DEFAULT_API_BASE_URL,
    ...COMMON_LOCAL_HOSTS,
  ];

  const subnets = new Set([
    subnetFromApiBaseUrl(preferred),
    subnetFromApiBaseUrl(saved),
    ...COMMON_LOCAL_SUBNETS,
  ].filter(Boolean));

  subnets.forEach((subnet) => {
    for (let host = 1; host <= 254; host += 1) {
      candidates.push(`http://${subnet}.${host}:8000/api/repartidor`);
    }
  });

  return [...new Set(candidates.map(normalizeApiBaseUrl).filter(Boolean))];
}

async function firstReachableApiBaseUrl(candidates) {
  const batchSize = 18;

  for (let index = 0; index < candidates.length; index += batchSize) {
    const batch = candidates.slice(index, index + batchSize);
    const results = await Promise.all(batch.map((candidate) => pingApiBaseUrl(candidate)));
    const found = results.find(Boolean);

    if (found) {
      return found;
    }
  }

  return null;
}

async function pingApiBaseUrl(apiBaseUrl) {
  const normalized = normalizeApiBaseUrl(apiBaseUrl);
  if (!normalized) return null;

  try {
    const response = await fetchWithTimeout(`${normalized}/ping`, DISCOVERY_PING_TIMEOUT_MS);
    if (!response.ok) return null;

    const payload = await response.json().catch(() => null);
    return payload?.data?.service === 'atlantia-repartidor-api' ? normalized : null;
  } catch (_) {
    return null;
  }
}

async function fetchWithTimeout(url, timeoutMs) {
  const controller = new AbortController();
  const timeout = window.setTimeout(() => controller.abort(), timeoutMs);

  try {
    return await fetch(url, {
      method: 'GET',
      headers: { Accept: 'application/json' },
      cache: 'no-store',
      signal: controller.signal,
    });
  } finally {
    window.clearTimeout(timeout);
  }
}

function normalizeApiBaseUrl(value) {
  let raw = String(value || '').trim();
  if (!raw) return '';

  if (!/^https?:\/\//i.test(raw)) {
    raw = `http://${raw}`;
  }

  const withoutTrailingSlash = raw.replace(/\/+$/, '');
  const pingIndex = withoutTrailingSlash.indexOf(`${API_PATH_PREFIX}/ping`);
  if (pingIndex >= 0) {
    return withoutTrailingSlash.slice(0, pingIndex + API_PATH_PREFIX.length);
  }

  const apiIndex = withoutTrailingSlash.indexOf(API_PATH_PREFIX);
  if (apiIndex >= 0) {
    return withoutTrailingSlash.slice(0, apiIndex + API_PATH_PREFIX.length);
  }

  if (withoutTrailingSlash.endsWith('/api')) {
    return `${withoutTrailingSlash}/repartidor`;
  }

  try {
    const parsed = new URL(withoutTrailingSlash);
    if (!parsed.port && isLocalNetworkHost(parsed.hostname)) {
      parsed.port = '8000';
    }

    return `${parsed.origin}${parsed.pathname === '/' ? '' : parsed.pathname}${API_PATH_PREFIX}`
      .replace(`${API_PATH_PREFIX}${API_PATH_PREFIX}`, API_PATH_PREFIX);
  } catch (_) {
    return '';
  }
}

function subnetFromApiBaseUrl(apiBaseUrl) {
  const normalized = normalizeApiBaseUrl(apiBaseUrl);
  if (!normalized) return null;

  try {
    const origin = normalized.slice(0, -API_PATH_PREFIX.length);
    const host = new URL(origin).hostname;
    const match = host.match(/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.\d{1,3}$/);
    if (!match) return null;

    return `${match[1]}.${match[2]}.${match[3]}`;
  } catch (_) {
    return null;
  }
}

function isLocalNetworkHost(hostname) {
  return /^(localhost|127\.0\.0\.1|10\.\d{1,3}\.\d{1,3}\.\d{1,3}|172\.(1[6-9]|2\d|3[0-1])\.\d{1,3}\.\d{1,3}|192\.168\.\d{1,3}\.\d{1,3})$/i.test(hostname);
}

function networkHelpMessage(apiBaseUrl) {
  return [
    'No pude conectar con el servidor.',
    'La app intentara descubrirlo automaticamente.',
    'Verifica que el celular y la PC esten en la misma Wi-Fi.',
    'Levanta Laravel con: php artisan serve --host=0.0.0.0 --port=8000.',
    apiBaseUrl ? `Ultimo servidor probado: ${apiBaseUrl}.` : '',
  ].join(' ');
}

function showRuntimeError(message) {
  const formMessage = $('[data-login-message]');
  const isLoginVisible = !$('[data-screen="login"]')?.classList.contains('hidden');

  if (formMessage && isLoginVisible) {
    formMessage.textContent = message;
    return;
  }

  showHomeMessage(message);
}

function showHomeMessage(message) {
  const homeMessage = $('[data-home-message]');
  if (!homeMessage) return;

  homeMessage.textContent = message || '';
}

function hideLaunch() {
  const launch = $('[data-launch]');
  if (!launch) return;

  launch.classList.add('launch-hidden');
}

function setLoginLoading(isLoading, label = 'Ingresar') {
  const button = $('[data-login-submit]');
  if (!button) return;

  button.disabled = isLoading;
  button.querySelector('span:first-child').textContent = isLoading ? label : 'Ingresar';
}

function togglePasswordVisibility() {
  const input = $('[name="password"]');
  const button = $('[data-toggle-password]');
  const shouldShow = input.type === 'password';
  input.type = shouldShow ? 'text' : 'password';
  button.textContent = shouldShow ? 'Ocultar' : 'Ver';
}

boot();
