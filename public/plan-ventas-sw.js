// public/plan-sw.js
// Service Worker para la sección de la presentación "/plan".
// Estrategia: Network First (Red Primero) para asegurar que el contenido dinámico
// (idioma, importes) esté siempre actualizado si hay conexión.
// Cache Fallback (Respaldo de Caché) para garantizar el funcionamiento offline.

// 1. USA UN NOMBRE DE CACHÉ ÚNICO Y CON VERSIÓN
const CACHE_NAME = 'pepemegia-plan-ventas-cache-v1';

// Opcional: una página offline personalizada
// const OFFLINE_URL = '/offline-plan.html'; 

// 2. DEFINE LOS RECURSOS ESENCIALES PARA LA PRESENTACIÓN
// Lista de URLs que se guardarán en caché durante la instalación.
const urlsToCache = [
  // Rutas principales de la presentación.
  '/plan/ventas/presentacion/1',

  // Fuentes de Google Fonts que necesite la presentación
  // 'https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap', // Ejemplo

  // --- Iconos de la PWA para "El Plan" ---
  // (¡Actualizado con toda la lista que proporcionaste!)
  // Esto es crucial para la experiencia "Añadir a Pantalla de Inicio".
  '/storage/icons/plan/ventas/icon-72x72.png',
  '/storage/icons/plan/ventas/icon-96x96.png',
  '/storage/icons/plan/ventas/icon-128x128.png',
  '/storage/icons/plan/ventas/icon-144x144.png',
  '/storage/icons/plan/ventas/icon-152x152.png',
  '/storage/icons/plan/ventas/icon-192x192.png',
  '/storage/icons/plan/ventas/icon-384x384.png',
  '/storage/icons/plan/ventas/icon-512x512.png'
];

// --- EVENTO 'INSTALL': CACHEO INICIAL ---
self.addEventListener('install', (event) => {
  console.log('SW-Plan: Instalando Service Worker...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('SW-Plan: Cacheando recursos iniciales de la presentación.');
        return cache.addAll(urlsToCache);
      })
      .catch(error => {
        console.error('SW-Plan: Fallo en cacheo durante la instalación:', error);
      })
  );
});

// --- EVENTO 'FETCH': GESTIÓN DE PETICIONES ---
self.addEventListener('fetch', (event) => {
  // Ignora peticiones que no sean HTTP/HTTPS
  if (!(event.request.url.startsWith('http:') || event.request.url.startsWith('https:'))) {
    return;
  }

  // 3. ESTRATEGIA "NETWORK FIRST, WITH CACHE FALLBACK"
  event.respondWith(
    fetch(event.request)
      .then((networkResponse) => {
        console.log('SW-Plan: Sirviendo desde la red:', event.request.url);
        
        if (networkResponse.ok) {
          const clonedResponse = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, clonedResponse);
          });
        }
        return networkResponse;
      })
      .catch(() => {
        console.log('SW-Plan: Red falló, intentando caché para:', event.request.url);
        
        return caches.match(event.request)
          .then((cachedResponse) => {
            if (cachedResponse) {
              console.log('SW-Plan: Sirviendo desde caché:', event.request.url);
              return cachedResponse;
            }
            console.warn('SW-Plan: Recurso no encontrado en red ni caché:', event.request.url);
            return new Response('No hay conexión a internet y el recurso no está en caché.', {
              status: 503,
              statusText: 'Service Unavailable',
              headers: new Headers({'Content-Type': 'text/plain'})
            });
          });
      })
  );
});

// --- EVENTO 'ACTIVATE': LIMPIEZA DE CACHÉS ANTIGUAS ---
self.addEventListener('activate', (event) => {
  console.log('SW-Plan: Activando Service Worker...');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME && cacheName.startsWith('pepemegia-plan')) {
            console.log('SW-Plan: Eliminando caché antigua:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim(); 
});