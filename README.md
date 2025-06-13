<h1 align="center">📍 Seguimiento de Estancias por País</h1>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-10.x-red?style=flat-square&logo=laravel" />
  <img src="https://img.shields.io/badge/API-Gmail-blue?style=flat-square&logo=gmail" />
  <img src="https://img.shields.io/badge/license-MIT-green?style=flat-square" />
</p>

<p align="center">
  Proyecto Laravel para rastrear cuánto tiempo pasaste en cada país,
  procesando automáticamente tus correos con reservas de vuelos. 💌✈️🌍
</p>

---

## ✨ Características

<ul>
  <li>🔐 Integración con Gmail usando la API oficial de Google</li>
  <li>📎 Análisis de correos con adjuntos PDF o texto plano</li>
  <li>🧠 Soporte opcional para Google Gemini (IA)</li>
  <li>🧳 Registro automático de reservas y pasajeros</li>
  <li>📅 Cálculo de días por país (timeline)</li>
  <li>🌐 API REST + panel visual con filtros</li>
</ul>

---

## 🛠️ Requisitos

- PHP ≥ 8.2
- Extensiones necesarias (ver `composer.json`)
- Node.js + Vite (solo si usas el panel web)
- Archivo `credentials.json` de Google OAuth

---

## 🚀 Instalación rápida

```bash
git clone https://github.com/tuusuario/seguimiento-estancias.git
cd seguimiento-estancias
composer install
npm install && npm run dev
cp .env.example .env
php artisan migrate
```

🔐 Coloca tus credenciales de Google en:  
```
storage/app/private/credentials.json
```

---

## 🔓 Autenticación con Gmail

1. Visita:
   ```
   /google/auth
   ```
2. Concede permisos.
3. Se guardará un token en:
   ```
   storage/app/private/token-tuemail.json
   ```

📩 Configura los correos permitidos en `config/reservas.php`.

---

## ✈️ Extracción de vuelos

```bash
php artisan vuelos:extraer --meses=6
```

El sistema buscará vuelos, extraerá origen, destino y fechas, y los asociará a un pasajero.

---

## 📊 Panel web y API

- Accede al panel: [`/`](#)
- Usa la API: `/api/reservas`, `/api/estancias`
- Exportación CSV disponible

---

## 📝 Licencia

Distribuido bajo licencia MIT.  
Consulta [`LICENSE`](LICENSE) para más información.

---

<p align="center">
  <b>Desarrollado con 💚 por Pepe Megía</b><br>
  <a href="https://github.com/josemegia">github.com/josemegia</a>
</p>