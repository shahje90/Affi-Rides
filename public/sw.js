const staticCache = 'acr-static-v1';
const offlineCache = 'acr-offline-v1';
const offlinePage = '/offline.html';
const staticPrefixes = ['/build/', '/icons/', '/audio/'];

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const offline = await caches.open(offlineCache);
        await offline.addAll([offlinePage, '/manifest.json', '/icons/acr-icon.svg']);

        const response = await fetch('/build/manifest.json');
        if (!response.ok) {
            throw new Error('Production asset manifest could not be loaded.');
        }

        const manifest = await response.json();
        const assets = Object.values(manifest).flatMap((entry) => [
            ...(entry.file ? [`/build/${entry.file}`] : []),
            ...(entry.css ?? []).map((file) => `/build/${file}`),
        ]);
        const cache = await caches.open(staticCache);
        await cache.addAll([...new Set(assets)]);
        await self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const names = await caches.keys();
        await Promise.all(names
            .filter((name) => (
                (name.startsWith('acr-static-') || name.startsWith('acr-offline-'))
                && ![staticCache, offlineCache].includes(name)
            ))
            .map((name) => caches.delete(name)));
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(offlinePage)));
        return;
    }

    if (staticPrefixes.some((prefix) => url.pathname.startsWith(prefix)) || url.pathname === '/manifest.json') {
        event.respondWith((async () => {
            const cached = await caches.match(request);
            if (cached) {
                return cached;
            }

            const response = await fetch(request);
            if (response.ok) {
                const cache = await caches.open(staticCache);
                await cache.put(request, response.clone());
            }

            return response;
        })());
    }
});
