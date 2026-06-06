// Minimal service worker (no framework). Bump CACHE to invalidate on deploy.
// Strategy: network-first for navigations (with an offline fallback),
// cache-first for AssetMapper's content-hashed /assets/* (safe — the hash in
// the URL changes whenever the file changes), network-first otherwise.
const CACHE = 'mc-v1';
const PRECACHE = [
  '/offline.html',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  // Page navigations: serve fresh, fall back to the offline page when down.
  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
    return;
  }

  // Hashed static assets: cache-first, then populate the cache.
  if (url.pathname.startsWith('/assets/')) {
    event.respondWith(
      caches.match(request).then((cached) => cached || fetch(request).then((response) => {
        const copy = response.clone();
        caches.open(CACHE).then((cache) => cache.put(request, copy));
        return response;
      }))
    );
    return;
  }

  // Everything else (icons, manifest, etc.): network, fall back to cache.
  event.respondWith(fetch(request).catch(() => caches.match(request)));
});
