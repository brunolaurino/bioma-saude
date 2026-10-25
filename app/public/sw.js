/* Service worker do Bioma CD. Guarda só a casca do app (CSS, JS, fontes, ícones).
   Páginas e arquivos com dados de saúde nunca vão para o cache. */
const CACHE = 'bioma-shell-v1';
const SHELL = ['/css/tokens.css?v=1', '/css/components.css?v=1', '/css/app.css?v=1', '/js/app.js?v=1', '/offline.html', '/icons/icon-192.png'];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== location.origin) return;

  const isStatic = /^\/(css|js|fonts|icons|vendor)\//.test(url.pathname);
  if (isStatic) {
    e.respondWith(caches.match(req).then((hit) => hit || fetch(req).then((res) => {
      if (res.ok) { const copy = res.clone(); caches.open(CACHE).then((c) => c.put(req, copy)); }
      return res;
    })));
    return;
  }

  // Navegação: sempre da rede. Sem conexão, mostra a página offline (sem dados).
  if (req.mode === 'navigate') {
    e.respondWith(fetch(req).catch(() => caches.match('/offline.html')));
  }
});
