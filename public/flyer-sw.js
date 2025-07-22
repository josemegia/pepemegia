// public/sw-flyer.js
// Este Service Worker está diseñado específicamente para la sección /flyer de tu aplicación.
// Estrategia principal: Network First (Red Primero) para HTML y contenido dinámico,
// con Cache Fallback (Caché de Respaldo) para funcionamiento offline.
// Cacheo agresivo para assets estáticos (CSS, JS, iconos, fuentes).

const CACHE_NAME = 'pepemegia-flyer-cache-v3'; // Incrementa la versión de la caché para forzar la actualización
const OFFLINE_URL = '/offline.html'; // Opcional: una página HTML simple para cuando no hay conexión

// Lista de URLs que el Service Worker intentará cachear durante la instalación.
// ¡Todas estas URLs deben ser exactas y accesibles desde el navegador!
const urlsToCache = [
  // Ruta principal de tu aplicación flyer
  '/flyer', 

  // Assets de CDN (CSS y JS)
  'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', // html2canvas

  // Fuentes de Google Fonts (CSS para las fuentes)
  // Asegúrate de que estas URLs coincidan con las que usas en tus vistas Blade
  'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;900&display=swap', // Para 'professional' y 'flyer_form'
  'https://fonts.googleapis.com/css2?family=Exo+2:wght@400;700;900&display=swap', // Para 'neon'
  'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap', // Para 'vibrant'
  'https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap', // Para 'corporate'
  'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap', // Para 'elegant'
  'https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800&display=swap', // Para 'natural'

  // Íconos de la PWA (¡CRUCIAL que estén en caché para la instalación en pantalla de inicio!)
  // Asegúrate de que estos archivos existan en public/storage/icons/flyer/
  '/storage/icons/flyer/icon-72x72.png',
  '/storage/icons/flyer/icon-96x96.png',
  '/storage/icons/flyer/icon-128x128.png',
  '/storage/icons/flyer/icon-144x144.png',
  '/storage/icons/flyer/icon-152x152.png',
  '/storage/icons/flyer/icon-192x192.png',
  '/storage/icons/flyer/icon-384x384.png',
  '/storage/icons/flyer/icon-512x512.png',

  // Imágenes por defecto o críticas que deberían estar disponibles offline
  // Asegúrate de que esta ruta sea la que usa asset('storage/flyers/' . $data['speaker']['image'])
  '/storage/flyers/claudia.png', // Ejemplo: imagen por defecto del orador
  // '/favicon.ico', // Si tienes un favicon
  // OFFLINE_URL, // Descomentar si implementas una página offline específica
];

// Evento 'install': Se dispara cuando el Service Worker se instala por primera vez.
// Aquí se cachean los assets esenciales de la aplicación.
self.addEventListener('install', (event) => {
  // console.log('SW-Flyer: Instalando Service Worker...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        // console.log('SW-Flyer: Cacheando shell de la aplicación.');
        return cache.addAll(urlsToCache);
      })
      .catch(error => {
        console.error('SW-Flyer: Fallo en cacheo durante la instalación:', error);
        // Si falla, es importante que el usuario lo sepa, pero no debe bloquear la instalación
      })
  );
});

// Evento 'fetch': Intercepta todas las peticiones de red del navegador.
// Aquí se implementa la estrategia de cacheo.
self.addEventListener('fetch', (event) => {
  // Ignora peticiones que no sean HTTP/HTTPS (ej. extensiones de Chrome, data URIs)
  if (!(event.request.url.startsWith('http:') || event.request.url.startsWith('https:'))) {
    return;
  }

  // --- Estrategia para rutas dinámicas o que siempre deben ser frescas (Network Only o Network First sin cachear) ---
  // Para el formulario de actualización o imágenes de speaker que pueden cambiar,
  // siempre vamos a la red y no cacheamos la respuesta para evitar contenido obsoleto o problemas de CSRF.
  // Las URLs de imágenes de speaker deben ser manejadas con cuidado si se suben nuevas.
  if (event.request.url.includes('/actualizar') || event.request.url.includes('/storage/flyers/')) {
    // console.log('SW-Flyer: Petición dinámica o de imagen, siempre a la red:', event.request.url);
    return event.respondWith(fetch(event.request));
  }

  // --- Estrategia para el HTML principal del flyer y assets estáticos (Network First with Cache Fallback) ---
  // Esto asegura que la lógica PHP de rotación de temas siempre se ejecute si hay conexión.
  // Si la red falla, se sirve la versión cacheada.
  event.respondWith(
    fetch(event.request)
      .then((networkResponse) => {
        // Si la respuesta de la red es exitosa (código 2xx), la cachea para usos futuros.
        if (networkResponse.ok) {
          const clonedResponse = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, clonedResponse);
          });
        }
        // console.log('SW-Flyer: Sirviendo desde la red:', event.request.url);
        return networkResponse;
      })
      .catch(() => {
        // Si la red falla (ej. sin conexión), intenta servir desde la caché.
        // console.log('SW-Flyer: Red falló, intentando caché para:', event.request.url);
        return caches.match(event.request)
          .then((cachedResponse) => {
            if (cachedResponse) {
              // console.log('SW-Flyer: Sirviendo desde caché:', event.request.url);
              return cachedResponse;
            }
            // Si ni la red ni la caché tienen el recurso, y es una navegación,
            // puedes redirigir a una página offline si la tienes.
            // if (event.request.mode === 'navigate' && OFFLINE_URL) {
            //   console.log('SW-Flyer: Navegación fallida, redirigiendo a offline page.');
            //   return caches.match(OFFLINE_URL);
            // }
            // Si no hay página offline, simplemente deja que el navegador muestre su error.
            console.warn('SW-Flyer: Recurso no encontrado en red ni caché:', event.request.url);
            return new Response('No hay conexión a internet y el recurso no está en caché.', {
              status: 503,
              statusText: 'Offline',
              headers: new Headers({'Content-Type': 'text/plain'})
            });
          });
      })
  );
});

// Evento 'activate': Se dispara cuando el Service Worker toma el control de la página.
// Aquí se limpia cualquier caché antigua para asegurar que solo la versión actual esté activa.
self.addEventListener('activate', (event) => {
  // console.log('SW-Flyer: Activando Service Worker...');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          // Elimina cualquier caché que no sea la actual (CACHE_NAME)
          if (cacheName !== CACHE_NAME) {
            // console.log('SW-Flyer: Eliminando caché antigua:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  // self.clients.claim() asegura que el Service Worker toma control de la página inmediatamente
  // sin necesidad de un refresco. Útil para la primera activación.
  self.clients.claim(); 
});