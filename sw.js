// ÜniBütçe Service Worker — v7
// Network-first HTML, stale-while-revalidate asset'ler, API cache yok.

const VERSION      = 'v7';
const STATIC_CACHE = `unibutce-static-${VERSION}`;
const ASSET_CACHE  = `unibutce-assets-${VERSION}`;

const PRECACHE = [
    '/unistudent/',
    '/unistudent/index.php',
    '/unistudent/style.css',
    '/unistudent/css/dashboard.css',
    '/unistudent/css/auth.css',
    '/unistudent/css/ui-helpers.css',
    '/unistudent/app.js',
    '/unistudent/js/utils.js',
    '/unistudent/manifest.json',
];

self.addEventListener('install', event => {
    self.skipWaiting();
    event.waitUntil(caches.open(STATIC_CACHE).then(c => c.addAll(PRECACHE).catch(() => {})));
});

self.addEventListener('activate', event => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys
            .filter(k => k !== STATIC_CACHE && k !== ASSET_CACHE)
            .map(k => caches.delete(k)));
        await self.clients.claim();
    })());
});

function isApi(url) {
    return url.pathname.includes('/api/') || url.pathname.endsWith('.php');
}

function isAsset(url) {
    return /\.(css|js|png|jpe?g|webp|gif|svg|woff2?|ttf)$/i.test(url.pathname);
}

self.addEventListener('fetch', event => {
    const req = event.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);

    // Same-origin olmayan istekleri atla (3rd-party SW karışmasın)
    if (url.origin !== self.location.origin) return;

    // API / dynamic PHP — hiç cache'leme
    if (isApi(url) && req.mode !== 'navigate') return;

    // Sayfa navigasyonu → network-first, offline fallback
    if (req.mode === 'navigate') {
        event.respondWith((async () => {
            try {
                const net = await fetch(req);
                const clone = net.clone();
                caches.open(STATIC_CACHE).then(c => c.put(req, clone)).catch(() => {});
                return net;
            } catch {
                return (await caches.match(req)) || caches.match('/unistudent/');
            }
        })());
        return;
    }

    // Asset'ler → stale-while-revalidate
    if (isAsset(url)) {
        event.respondWith((async () => {
            const cache = await caches.open(ASSET_CACHE);
            const cached = await cache.match(req);
            const fetchPromise = fetch(req).then(res => {
                if (res && res.status === 200) cache.put(req, res.clone());
                return res;
            }).catch(() => cached);
            return cached || fetchPromise;
        })());
    }
});
