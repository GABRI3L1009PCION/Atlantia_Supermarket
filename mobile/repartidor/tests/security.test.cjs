const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const context = vm.createContext({ URL });
vm.runInContext(fs.readFileSync(path.join(__dirname, '../www/security.js'), 'utf8'), context);
const normalize = context.AtlantiaTransport.normalizeBaseUrl;

for (const [input, debug, expected] of [
  ['api.example.com', false, 'https://api.example.com/api/repartidor'],
  ['https://api.example.com:8443/base/api', false, 'https://api.example.com:8443/base/api/repartidor'],
  ['https://api.example.com/api/repartidor/ping', false, 'https://api.example.com/api/repartidor'],
  ['192.168.0.7', true, 'http://192.168.0.7:8000/api/repartidor'],
  ['http://192.168.0.7:8000', false, ''],
  ['http://api.example.com', true, ''],
  ['http://127.0.0.1.evil.example', true, ''],
  ['https://user:password@api.example.com', false, ''],
  ['https://api.example.com/?next=/api/repartidor', false, ''],
  ['https://api.example.com/#/api/repartidor', false, ''],
  ['javascript:alert(1)', false, ''],
  ['https://api.example.com\n/api', false, ''],
]) {
  test(`transport validation: ${JSON.stringify(input)} (debug=${debug})`, () => {
    assert.equal(normalize(input, debug), expected);
  });
}

const appSource = fs.readFileSync(path.join(__dirname, '../www/app.js'), 'utf8');
const discoverySource = appSource.slice(appSource.indexOf('function discoveryCandidates('),
  appSource.indexOf('\nasync function firstReachableApiBaseUrl('));
const discoveryContext = vm.createContext({
  normalizeApiBaseUrl: value => normalize(value, false),
  state: { apiBaseUrl: 'https://cached.example.com' },
  DEFAULT_API_BASE_URL: '',
  ALLOW_LOCAL_API_DISCOVERY: false,
  COMMON_LOCAL_HOSTS: ['http://192.168.0.7:8000'],
});
vm.runInContext(discoverySource, discoveryContext);

test('an explicitly selected server cannot fall back to a different cached server', () => {
  const candidates = vm.runInContext("discoveryCandidates('https://selected.example.com')", discoveryContext);
  assert.deepEqual(Array.from(candidates), ['https://selected.example.com/api/repartidor']);
});

test('production does not scan local HTTP servers', () => {
  const candidates = vm.runInContext('discoveryCandidates()', discoveryContext);
  assert.deepEqual(Array.from(candidates), ['https://cached.example.com/api/repartidor']);
});
