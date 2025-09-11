const CACHE = 'asclepius-cache-v2';
const ASSETS = [
  '/',
  '/assets/css/style.css',
  '/assets/js/main.js',
  '/index.php',
  '/offline.html',
  '/assets/img/placeholder.svg'
];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(ASSETS)));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k))))
  );
});

self.addEventListener('fetch', (e) => {
  const { request } = e;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  // Avoid caching admin and dynamic user pages
  if (url.pathname.startsWith('/admin') || url.pathname.startsWith('/user')) {
    return; // network only
  }

  // Navigation requests: network-first
  if (request.mode === 'navigate' || request.destination === 'document') {
    e.respondWith(
      fetch(request).then((res) => {
        const copy = res.clone();
        caches.open(CACHE).then((c) => c.put(request, copy));
        return res;
      }).catch(() => caches.match(request).then(cached => cached || caches.match('/offline.html')))
    );
    return;
  }

  // Static assets: cache-first
  e.respondWith(
    caches.match(request).then((cached) => cached || fetch(request).then((res) => {
      const copy = res.clone();
      caches.open(CACHE).then((c) => c.put(request, copy)).catch(() => {});
      return res;
    }).catch(() => cached))
  );
});