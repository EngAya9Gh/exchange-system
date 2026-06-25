self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(clients.claim());
});

self.addEventListener('fetch', (event) => {
    // This is a minimal Service Worker.
    // It passes the PWA installation requirement by having a fetch handler,
    // but doesn't aggressively cache files to avoid breaking Livewire functionality.
});
