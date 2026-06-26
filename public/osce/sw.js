const CACHE = 'osce-panel-v1';
const ASSETS = [
  '/osce/panel',
  '/osce/osce-panel.css',
  '/osce/manifest.json'
];

self.addEventListener('install', e=>{
  e.waitUntil(caches.open(CACHE).then(c=>c.addAll(ASSETS)));
});
self.addEventListener('activate', e=>{
  e.waitUntil(caches.keys().then(keys=>Promise.all(keys.map(k=>k!==CACHE&&caches.delete(k)))));
});
self.addEventListener('fetch', e=>{
  const url = new URL(e.request.url);

  // cache-first untuk assets & halaman panel
  if (ASSETS.includes(url.pathname)) {
    e.respondWith(
      caches.match(e.request).then(r=> r || fetch(e.request).then(res=>{
        const copy = res.clone(); caches.open(CACHE).then(c=>c.put(e.request, copy)); return res;
      }))
    );
    return;
  }

  // network-first untuk API GET
  if (url.pathname.startsWith('/osce/api/')) {
    e.respondWith(
      fetch(e.request).then(res=>{
        const copy = res.clone(); caches.open(CACHE).then(c=>c.put(e.request, copy)); return res;
      }).catch(()=> caches.match(e.request))
    );
    return;
  }
});
// cache-first untuk halaman ujian
if (url.pathname.startsWith('/osce/ujian/')) {
  e.respondWith(
    caches.match(e.request).then(r=> r || fetch(e.request).then(res=>{
      const copy = res.clone(); caches.open(CACHE).then(c=>c.put(e.request, copy)); return res;
    }))
  );
  return;
}

