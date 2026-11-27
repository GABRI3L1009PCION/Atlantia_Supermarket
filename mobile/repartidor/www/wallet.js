/*
 * Pantalla de Ganancias: saldo, resumen semanal/mensual, grafica, historial,
 * retiros, liquidacion de efectivo y cuenta bancaria.
 */

state.wallet = null;

const EARNING_TYPES = {
  earning: { label: 'Entrega', icon: 'box' },
  tip: { label: 'Propina', icon: 'gift' },
  bonus: { label: 'Bono por desempeño', icon: 'award' },
  cash_collected: { label: 'Efectivo cobrado', icon: 'cash' },
  cash_paid_pickup: { label: 'Pago en comercio', icon: 'store' },
  cash_settlement: { label: 'Liquidación de efectivo', icon: 'wallet' },
  withdrawal: { label: 'Retiro', icon: 'bank' },
  adjustment: { label: 'Ajuste', icon: 'clipboard' },
};
const DAY_NAMES = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

async function loadWallet() {
  const history = document.querySelector('[data-wallet-history]');
  if (history && !state.wallet) history.innerHTML = '<p class="muted ar-loading">Cargando...</p>';

  try {
    const response = await api('/wallet');
    state.wallet = response.data || {};
    renderWallet();
  } catch (error) {
    if (history) history.innerHTML = `<p class="sheet-error ar-loading">${escapeHtml(error.message)}</p>`;
  }
}

function renderWallet() {
  const wallet = state.wallet || {};
  const set = (selector, value) => {
    const element = document.querySelector(selector);
    if (element) element.textContent = value;
  };

  set('[data-wallet-available]', money(wallet.available_to_withdraw ?? wallet.wallet?.available_balance));
  set('[data-week-earnings]', money(wallet.current_week_earnings));
  set('[data-month-earnings]', money(wallet.current_month_earnings));
  set('[data-tip-earnings]', money(wallet.tip_earnings));
  set('[data-bonus-earnings]', money(wallet.bonus_earnings));

  const bank = wallet.bank_account;
  set('[data-bank-label]', bank?.bank_name
    ? `${bank.bank_name} • **** ${bank.bank_account_number_last4 || '----'}${bank.verified_at ? '' : ' (en verificacion)'}`
    : 'Registra tu cuenta bancaria');

  const settle = document.querySelector('[data-settle]');
  if (settle) settle.disabled = !(Number(wallet.wallet?.cash_balance) > 0);

  renderWalletChart();
  renderWalletHistory(4);
}

function renderWalletChart() {
  const chart = document.querySelector('[data-wallet-chart]');
  if (!chart) return;
  const range = document.querySelector('[data-chart-range]')?.value || 'daily';
  const series = (range === 'weekly' ? state.wallet?.weekly_earnings : state.wallet?.daily_earnings) || [];

  if (!series.length) {
    chart.innerHTML = '<p class="muted ar-loading">Aun no hay ganancias en este periodo.</p>';
    return;
  }

  const max = Math.max(...series.map((point) => Number(point.total || 0)), 1);
  const best = series.reduce((top, point) => (Number(point.total) > Number(top.total) ? point : top), series[0]);

  chart.innerHTML = series.map((point) => {
    const date = new Date(`${point.start}T12:00:00`);
    const label = range === 'weekly'
      ? `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}`
      : DAY_NAMES[date.getDay()];
    const total = Number(point.total || 0);
    const height = total > 0 ? Math.max(8, Math.round((total / max) * 100)) : 4;
    return `
      <div class="ar-bar ${point === best && total > 0 ? 'is-best' : ''}">
        <span class="ar-bar-fill" style="height:${height}%"></span>
        <small>${escapeHtml(label)}</small>
        <strong>Q ${total.toFixed(0)}</strong>
      </div>
    `;
  }).join('');
}

function walletEntries() {
  const wallet = state.wallet || {};
  const movements = (wallet.movements || []).map((movement) => ({
    type: movement.type,
    amount: Number(movement.amount || 0),
    description: movement.description || '',
    at: movement.created_at,
  }));
  const withdrawals = (wallet.withdrawals || []).map((withdrawal) => ({
    type: 'withdrawal',
    amount: -Math.abs(Number(withdrawal.transferred_amount ?? withdrawal.approved_amount ?? withdrawal.requested_amount ?? 0)),
    description: `Retiro ${withdrawalStatusLabel(withdrawal.status)}`,
    at: withdrawal.requested_at || withdrawal.created_at,
  }));

  return [...movements, ...withdrawals]
    .filter((entry) => entry.at)
    .sort((a, b) => new Date(b.at) - new Date(a.at));
}

function renderWalletHistory(limit) {
  const container = document.querySelector('[data-wallet-history]');
  if (!container) return;
  const entries = walletEntries().filter((entry) => ['earning', 'tip', 'bonus', 'withdrawal'].includes(entry.type));
  container.innerHTML = entries.length
    ? ledgerHtml(entries.slice(0, limit))
    : '<p class="muted ar-loading">Tus ingresos apareceran aqui al completar entregas.</p>';
}

function ledgerHtml(entries) {
  return entries.map((entry) => {
    const meta = EARNING_TYPES[entry.type] || { label: 'Movimiento', icon: 'wallet' };
    const reference = (entry.description.match(/[A-Z]{2,}-?\d[\w-]*/) || [''])[0];
    const title = entry.type === 'earning' && reference ? `Entrega ${reference}` : (entry.type === 'withdrawal' ? entry.description : meta.label);
    const positive = entry.amount >= 0;
    return `
      <div class="ar-ledger-row">
        <span class="ar-stat-icon">${svgIcon(meta.icon)}</span>
        <span class="ar-ledger-text"><strong>${escapeHtml(title)}</strong><small>${escapeHtml(formatDate(entry.at))}</small></span>
        <strong class="ar-ledger-amount ${positive ? 'is-in' : 'is-out'}">${positive ? '+' : '-'} ${money(Math.abs(entry.amount))}</strong>
      </div>
    `;
  }).join('');
}

function withdrawalStatusLabel(status) {
  return {
    pending: 'solicitado',
    approved: 'aprobado',
    transferred: 'transferido',
    rejected: 'rechazado',
    cancelled: 'cancelado',
  }[status] || status || '';
}

function showAllWalletHistory() {
  const entries = walletEntries();
  openSheet(`
    <h3>Historial de movimientos</h3>
    <div class="ar-ledger ar-ledger--sheet">${entries.length ? ledgerHtml(entries) : '<p class="muted">Sin movimientos.</p>'}</div>
    <div class="sheet-actions"><button type="button" class="sheet-secondary" data-sheet-close>Cerrar</button></div>
  `);
}

async function requestWithdrawal() {
  const wallet = state.wallet || {};
  if (!wallet.bank_account?.bank_name) {
    showToast('Primero registra tu cuenta de retiro.', true);
    editBankAccount(null);
    return;
  }
  const value = await askText(`Monto a retirar (disponible ${money(wallet.available_to_withdraw)})`, '0.00', true, 'decimal');
  if (value === null) return;
  const amount = Number(String(value).replace(',', '.'));
  if (!Number.isFinite(amount) || amount <= 0) {
    showToast('Ingresa un monto valido.', true);
    return;
  }
  try {
    await api('/wallet/withdrawals', { method: 'POST', body: JSON.stringify({ amount }) });
    showToast('Retiro solicitado.');
    loadWallet();
  } catch (error) {
    showToast(error.message, true);
  }
}

async function requestCashSettlement() {
  const wallet = state.wallet || {};
  const value = await askText(`Efectivo que entregas a caja (saldo ${money(wallet.wallet?.cash_balance)})`, '0.00', true, 'decimal');
  if (value === null) return;
  const reportedAmount = Number(String(value).replace(',', '.'));
  if (!Number.isFinite(reportedAmount) || reportedAmount <= 0) {
    showToast('Ingresa un monto valido.', true);
    return;
  }
  try {
    await api('/wallet/cash-settlements', { method: 'POST', body: JSON.stringify({ reported_amount: reportedAmount }) });
    showToast('Liquidacion registrada. Administracion la confirmara.');
    loadWallet();
  } catch (error) {
    showToast(error.message, true);
  }
}

function editBankAccount(bank = state.wallet?.bank_account) {
  const dialog = openSheet(`
    <h3>Cuenta de retiro</h3>
    <form class="sheet-form" data-bank-form>
      <label class="sheet-field"><span>Banco</span><input name="bank_name" maxlength="120" required value="${escapeHtml(bank?.bank_name || '')}"></label>
      <label class="sheet-field"><span>Tipo de cuenta</span>
        <select name="bank_account_type">
          <option value="monetaria">Monetaria</option>
          <option value="ahorro" ${bank?.bank_account_type === 'ahorro' ? 'selected' : ''}>Ahorro</option>
        </select>
      </label>
      <label class="sheet-field"><span>Numero de cuenta</span><input name="bank_account_number" maxlength="40" inputmode="numeric" required></label>
      <label class="sheet-field"><span>Titular</span><input name="bank_account_holder" maxlength="120" required value="${escapeHtml(bank?.bank_account_holder || state.user?.name || '')}"></label>
      <p class="sheet-error" data-sheet-error></p>
      <div class="sheet-actions">
        <button type="button" class="sheet-secondary" data-sheet-close>Cancelar</button>
        <button type="submit" class="sheet-primary">Guardar</button>
      </div>
    </form>
  `);

  dialog.querySelector('[data-bank-form]').addEventListener('submit', async (event) => {
    event.preventDefault();
    const data = Object.fromEntries(new FormData(event.currentTarget).entries());
    try {
      await api('/wallet/bank-account', { method: 'PATCH', body: JSON.stringify({ ...data, payout_method: 'transfer' }) });
      closeSheet();
      showToast('Cuenta guardada. Administracion la verificara.');
      loadWallet();
    } catch (error) {
      dialog.querySelector('[data-sheet-error]').textContent = error.message;
    }
  });
}

function wireWallet() {
  document.querySelector('[data-withdraw]')?.addEventListener('click', requestWithdrawal);
  document.querySelector('[data-settle]')?.addEventListener('click', requestCashSettlement);
  document.querySelector('[data-bank]')?.addEventListener('click', () => editBankAccount());
  document.querySelector('[data-history-all]')?.addEventListener('click', showAllWalletHistory);
  document.querySelector('[data-chart-range]')?.addEventListener('change', renderWalletChart);
}

wireWallet();
