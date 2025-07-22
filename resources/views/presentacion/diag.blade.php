<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ __('presentation.diag.titulo_pagina') }}</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800&family=Playfair+Display:ital,wght@1,400&family=Roboto:wght@300;400&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Roboto', sans-serif;
      background-color: #030712;
      color: #f9fafb;
      overflow: hidden;
    }
    .font-title {
      font-family: 'Poppins', sans-serif;
    }
    .font-quote {
      font-family: 'Playfair Display', serif;
      font-style: italic;
    }
    .hero-container {
      position: relative;
      width: 100vw;
      height: 100vh;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .hero-background {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: url('{{ asset('storage/universo.jpeg') }}');
      background-size: cover;
      background-position: center;
      animation: zoom-in 30s ease-in-out infinite alternate;
    }
    .hero-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle, rgba(3, 7, 18, 0.2) 0%, rgba(3, 7, 18, 0.8) 85%);
    }
    .hero-content {
      position: relative;
      z-index: 10;
      text-align: center;
      padding: 2rem;
      max-width: 900px;
    }
    .quote-text,
    .quote-author,
    .cta-button {
      opacity: 0;
      animation: fade-in-up 2s cubic-bezier(0.19, 1, 0.22, 1) forwards;
    }
    .quote-text { animation-delay: 0.5s; }
    .quote-author { animation-delay: 1.5s; }
    .cta-button { animation-delay: 2.5s; }

    @keyframes zoom-in {
      from { transform: scale(1); }
      to { transform: scale(1.1); }
    }
    @keyframes fade-in-up {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

  <div class="hero-container">
    <div class="hero-background"></div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
      <blockquote class="quote-text">
        <p class="font-quote text-3xl md:text-5xl text-white leading-relaxed md:leading-relaxed drop-shadow-lg">
          {{-- Usamos {!! !!} para renderizar el HTML (<span>) de la traducción --}}
          {!! __('presentation.diag.cita_principal') !!}
        </p>
      </blockquote>
      <cite class="quote-author block font-title text-xl md:text-2xl text-gray-300 mt-8 not-italic">
        – {{ __('presentation.diag.autor') }}<br>
        <span class="text-base text-gray-500">{{ __('presentation.diag.autor_cargo') }}</span>
      </cite>
      <br><br><br>
    </div>
  </div>
</body>
</html>