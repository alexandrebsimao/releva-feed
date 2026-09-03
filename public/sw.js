const CACHE_NAME = 'releva-static-v1';

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => Promise.all(
            cacheNames
                .filter((cacheName) => cacheName !== CACHE_NAME)
                .map((cacheName) => caches.delete(cacheName)),
        )),
    );

    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const requestUrl = new URL(event.request.url);

    if (requestUrl.origin !== self.location.origin) {
        return;
    }

    const cacheableAsset = ['style', 'script', 'font', 'image'].includes(event.request.destination);

    if (!cacheableAsset) {
        return;
    }

    event.respondWith(
        caches.open(CACHE_NAME).then(async (cache) => {
            const cachedResponse = await cache.match(event.request);
            const networkResponse = fetch(event.request).then((response) => {
                if (response.ok) {
                    cache.put(event.request, response.clone());
                }

                return response;
            });

            return cachedResponse || networkResponse;
        }),
    );
});
