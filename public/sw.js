'use strict';

const VERSION = 'parmobile-v1';
const OFFLINE_URL = '/offline.html';
const PRECACHE = [
    '/offline.html',
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(VERSION)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((name) => name !== VERSION).map((name) => caches.delete(name))))
            .then(() => self.clients.claim())
    );
});

const isNavigation = (request) => request.mode === 'navigate' || (request.method === 'GET' && String(request.headers.get('accept') || '').includes('text/html'));
const isStaticAsset = (url) => /\/(?:[^\/]*\.(?:js|css|png|jpe?g|gif|svg|webp|woff2?|ico|webmanifest))$/i.test(url.pathname);

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (response && response.ok) {
                        const copy = response.clone();
                        caches.open(VERSION).then((cache) => cache.put(request, copy));
                    }
                    return response;
                })
                .catch(() => caches.match(request))
        );
        return;
    }

    if (isStaticAsset(url)) {
        event.respondWith(
            caches.match(request).then((cached) => {
                if (cached) return cached;
                return fetch(request).then((response) => {
                    if (response && response.ok) {
                        const copy = response.clone();
                        caches.open(VERSION).then((cache) => cache.put(request, copy));
                    }
                    return response;
                });
            })
        );
        return;
    }

    if (isNavigation(request)) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (response && (response.ok || response.type === 'opaqueredirect')) return response;
                    return caches.match(OFFLINE_URL);
                })
                .catch(() => caches.match(OFFLINE_URL).then((fallback) => fallback || Response.error()))
        );
        return;
    }

    event.respondWith(fetch(request));
});