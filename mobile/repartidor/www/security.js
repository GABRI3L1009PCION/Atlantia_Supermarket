(function () {
  'use strict';

  function isLocalHost(hostname) {
    return /^(localhost|\[::1\]|127\.0\.0\.1|10\.\d{1,3}\.\d{1,3}\.\d{1,3}|172\.(1[6-9]|2\d|3[0-1])\.\d{1,3}\.\d{1,3}|192\.168\.\d{1,3}\.\d{1,3})$/i.test(hostname)
      || /\.(test|local)$/i.test(hostname);
  }

  function normalizeBaseUrl(value, allowLocalHttp = false) {
    const raw = String(value || '').trim();
    if (!raw || /[\u0000-\u0020\u007f]/.test(raw)) return '';

    try {
      const explicitScheme = /^[a-z][a-z0-9+.-]*:\/\//i.test(raw);
      const parsed = new URL(explicitScheme ? raw : `https://${raw}`);
      const local = isLocalHost(parsed.hostname);
      if (!explicitScheme && allowLocalHttp && local) parsed.protocol = 'http:';
      if (parsed.username || parsed.password || parsed.search || parsed.hash) return '';
      if (parsed.protocol !== 'https:' && !(parsed.protocol === 'http:' && allowLocalHttp && local)) return '';
      if (parsed.protocol === 'http:' && !parsed.port && local) parsed.port = '8000';

      let path = parsed.pathname.replace(/\/+$/, '');
      if (path.endsWith('/api/repartidor/ping')) path = path.slice(0, -5);
      if (path.endsWith('/api')) path += '/repartidor';
      else if (!path.endsWith('/api/repartidor')) path += '/api/repartidor';
      return parsed.origin + path;
    } catch (_) {
      return '';
    }
  }

  globalThis.AtlantiaTransport = Object.freeze({ isLocalHost, normalizeBaseUrl });
}());
