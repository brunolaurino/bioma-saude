/* Service worker do Saúde App. Guarda só a casca do app (CSS, JS, fontes, ícones).
   Páginas e arquivos com dados de saúde nunca vão para o cache. */
const CACHE = 'saude-shell-v4';
const SHELL = ['/css/tokens.css?v=2', '/css/components.css?v=2', '/css/app.css?v=2', '/css/theme.css?v=2', '/vendor/turbo/turbo.js?v=8.0.12', '/js/app.js?v=3', '/offline.html', '/icons/icon-192.png'];

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

  const isStatic = /^\/(css|js|fonts|icons|img|vendor)\//.test(url.pathname);
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

// Push: o servidor manda só título, texto genérico e o caminho da tela. Nada de dado de saúde.
self.addEventListener('push', (e) => {
  let data = {};
  try { data = e.data ? e.data.json() : {}; } catch (err) { data = {}; }
  const title = data.title || 'Saúde App';
  e.waitUntil(self.registration.showNotification(title, {
    body: data.body || 'Você tem uma nova notificação.',
    icon: '/icons/icon-192.png',
    badge: '/icons/icon-192.png',
    tag: 'saude-app',
    renotify: true,
    data: { url: typeof data.url === 'string' && data.url.startsWith('/') && !data.url.startsWith('//') ? data.url : '/' },
  }));
});

self.addEventListener('notificationclick', (e) => {
  e.notification.close();
  const target = new URL(e.notification.data && e.notification.data.url || '/', location.origin).href;
  e.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
    for (const c of list) {
      if (new URL(c.url).origin === location.origin && 'focus' in c) { c.navigate(target).catch(() => {}); return c.focus(); }
    }
    return clients.openWindow(target);
  }));
});
