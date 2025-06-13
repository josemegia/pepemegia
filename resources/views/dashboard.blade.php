<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Estancias por País</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      background: #f2f2f2;
      color: #333;
    }
    .container {
      max-width: 1300px;
      margin: auto;
      padding: 40px 20px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    h1 {
      text-align: center;
      color: #0072ff;
    }
    .filters {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 20px;
      margin: 20px 0;
    }
    select, input, button {
      padding: 10px;
      font-size: 1rem;
      border-radius: 8px;
      border: 1px solid #ccc;
    }
    button {
      background-color: #0072ff;
      color: white;
      border: none;
      cursor: pointer;
    }
    .cards {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      justify-content: center;
    }
    .card {
      background: #eef3fb;
      border-radius: 12px;
      padding: 20px;
      width: 200px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      text-align: center;
    }
    .timeline {
      display: flex;
      overflow-x: auto;
      padding: 10px;
      background: #f9f9f9;
      border-radius: 12px;
      font-size: 0.8rem;
      white-space: nowrap;
      border: 1px solid #ccc;
    }
    .day-block {
      min-width: 30px;
      padding: 10px 5px;
      text-align: center;
      border-right: 1px solid #ddd;
      color: white;
    }
    .legend {
      text-align: center;
      font-size: 0.85rem;
      margin-top: 10px;
    }
    .tooltip {
      position: relative;
      display: inline-block;
      cursor: default;
    }
    .tooltip .tooltiptext {
      visibility: hidden;
      width: auto;
      background-color: #333;
      color: #fff;
      font-size: 0.75rem;
      padding: 4px 8px;
      border-radius: 4px;
      position: absolute;
      z-index: 10;
      bottom: 125%; /* Encima del elemento */
      left: 50%;
      transform: translateX(-50%);
      white-space: nowrap;
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    .tooltip:hover .tooltiptext {
      visibility: visible;
      opacity: 1;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>🌍 Estancias por País</h1>
    <div class="filters">
      <select id="selectPasajero"></select>
      <label>Desde: <input type="date" id="fechaInicio"></label>
      <label>Hasta: <input type="date" id="fechaFin"></label>
      <button onclick="filtrar()">Consultar</button>
    </div>
@if($actualizado)
    <div class="alert alert-success">
        ✈️ Las reservas fueron sincronizadas automáticamente al entrar.
    </div>
@else
    <div class="alert alert-warning">
        ⏳ Las reservas ya estaban sincronizadas recientemente.
    </div>
@endif
    <div id="resumenCards" class="cards"></div>
    <h2 style="text-align:center; color:#0072ff;">📆 Línea de Tiempo</h2>
    <div id="timelineContainer" class="timeline"></div>
    <div id="legendContainer" class="legend"></div>
  </div>

  <script>
    const base = window.location.origin;
    const colores = ['#0072ff', '#00b894', '#ff7675', '#fdcb6e', '#6c5ce7', '#e84393', '#55efc4', '#2d3436'];

    function formatFecha(fecha) {
      if (!fecha || typeof fecha !== 'string' || !fecha.includes('-')) return '--';
      try {
        const [y, m, d] = fecha.split('-');
        return new Date(Number(y), Number(m) - 1, Number(d)).toLocaleDateString('es-ES', {
          day: '2-digit',
          month: 'short'
        });
      } catch {
        return '--';
      }
    }

    function getDiasEntre(f1, f2) {
      const d1 = new Date(f1 + 'T12:00:00');
      const d2 = new Date(f2 + 'T12:00:00');
      const dias = [];
      while (d1 <= d2) {
        dias.push(new Date(d1));
        d1.setDate(d1.getDate() + 1);
      }
      return dias;
    }

    function pintarResumen(estancias) {
      const cont = document.getElementById('resumenCards');
      cont.innerHTML = '';
      estancias.forEach((e, i) => {
        const div = document.createElement('div');
        div.className = 'card';
        div.style.borderTop = `5px solid ${colores[i % colores.length]}`;
        div.innerHTML = `
          <h3>${e.pais}</h3>
          <div class="dias">${e.dias} días</div>
          <div>${formatFecha(e.desde)} - ${formatFecha(e.hasta)}</div>
          ${e.nota ? `<div style="color:#e74c3c">(${e.nota})</div>` : ''}
        `;
        cont.appendChild(div);
      });
    }

    function pintarLineaTiempo(estancias) {
      const timeline = document.getElementById('timelineContainer');
      const legend = document.getElementById('legendContainer');
      timeline.innerHTML = '';
      legend.innerHTML = '';

      const mapaColor = {};
      estancias.forEach((e, i) => {
        mapaColor[e.pais] = colores[i % colores.length];
      });

      const bloques = [];
      estancias.forEach(e => {
        getDiasEntre(e.desde, e.hasta).forEach(d => {
          bloques.push({
            fecha: d,
            pais: e.pais,
            color: mapaColor[e.pais]
          });
        });
      });

      bloques.sort((a, b) => a.fecha - b.fecha);
      bloques.forEach(b => {
        const div = document.createElement('div');
        div.className = 'day-block';
        div.style.backgroundColor = b.color;
        div.innerHTML = `<div>${formatFecha(b.fecha.toISOString().split('T')[0])}</div><div>${b.pais}</div>`;
        timeline.appendChild(div);
      });

      for (const [pais, color] of Object.entries(mapaColor)) {
        const estancia = estancias.find(e => e.pais === pais); 
        const iso = estancia && estancia.iso2 ? estancia.iso2.toLowerCase() : 'un'; // fallback

        const span = document.createElement('span');
        span.innerHTML = `
          <span class="tooltip">
            <img src="https://flagcdn.com/24x18/${iso}.png" alt="${pais}" style="vertical-align:middle; margin-right:4px;">
            <i style="background:${color}; width:14px; height:14px; display:inline-block; border-radius:3px; margin-right:4px;"></i>
            <span class="tooltiptext">${pais}</span>
          </span>
        `;
        legend.appendChild(span);
      }

    }

    async function filtrar() {
      const pid = document.getElementById('selectPasajero').value || 1;

      let desde = document.getElementById('fechaInicio').value;
      let hasta = document.getElementById('fechaFin').value;

      const hoy = new Date();
      const hace365 = new Date();
      hace365.setDate(hoy.getDate() - 365);

      if (!hasta) {
        hasta = hoy.toISOString().split('T')[0];
        document.getElementById('fechaFin').value = hasta;
      }

      if (!desde) {
        desde = hace365.toISOString().split('T')[0];
        document.getElementById('fechaInicio').value = desde;
      }

      try {
        const res = await fetch(`${base}/api/reservas/estancias?pasajero_id=${pid}&desde=${desde}&hasta=${hasta}`);
        const data = await res.json();

        const estancias = (data.estancias || []).filter(e => e.pais && e.dias > 0);
        const bloques = (data.bloques || []).filter(e => e.pais && e.desde && e.hasta);

        pintarResumen(estancias);
        pintarLineaTiempo(bloques);

      } catch (err) {
        console.error('❌ Error al consultar estancias:', err);
      }
    }

    async function cargarPasajeros() {
      const res = await fetch(`${base}/api/reservas/estancias/pasajeros`);
      const data = await res.json();
      const select = document.getElementById('selectPasajero');
      select.innerHTML = data.map(p => `<option value="${p.id}">${p.nombre_unificado}</option>`).join('');
      select.value = 1;
      filtrar();
    }

    document.addEventListener('DOMContentLoaded', cargarPasajeros);
  </script>
</body>
</html>
