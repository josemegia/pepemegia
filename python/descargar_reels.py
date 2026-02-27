#!/usr/bin/env python3
"""
Descargador de Reels/Stories/Facebook desde texto pegado.
Clasifica links y descarga todo lo soportado usando cookies.
"""

import re
import os
import time
import subprocess
import sys
from urllib.parse import urlparse, urlunparse

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
COOKIES = os.path.join(BASE_DIR, 'cookies.txt')
CARPETA_DESCARGAS = os.path.join(BASE_DIR, '..', 'storage', 'app', 'reels')
PAUSA_ENTRE_DESCARGAS = 3


def extraer_urls(texto):
    return re.findall(r'https?://[^\s<>"\']+', texto)


def limpiar_url(url):
    parsed = urlparse(url)
    return urlunparse((parsed.scheme, parsed.netloc, parsed.path, '', '', '')).rstrip('/')


def resolver_facebook_share(url):
    """Resuelve URLs de /share/r/ de Facebook a la URL real del reel."""
    try:
        resultado = subprocess.run(
            ['curl', '-Ls', '-o', '/dev/null', '-w', '%{url_effective}', url],
            capture_output=True, text=True, timeout=15
        )
        url_real = resultado.stdout.strip()
        if '/reel/' in url_real or '/watch/' in url_real:
            return limpiar_url(url_real)
    except Exception:
        pass
    return url


def clasificar_url(url):
    u = url.lower()
    if 'instagram.com/reel/' in u:
        return 'ig_reel'
    elif 'instagram.com/stories/' in u:
        return 'ig_story'
    elif 'instagram.com/p/' in u:
        return 'ig_post'
    elif 'facebook.com' in u or 'fb.com' in u:
        return 'facebook'
    elif 'tiktok.com' in u:
        return 'tiktok'
    elif 'youtube.com' in u or 'youtu.be' in u:
        return 'youtube'
    return 'otro'


ETIQUETAS = {
    'ig_reel':  'IG Reel',
    'ig_story': 'IG Story',
    'ig_post':  'IG Post',
    'facebook': 'Facebook',
    'tiktok':   'TikTok',
    'youtube':  'YouTube',
    'otro':     'Otro',
}

DESCARGABLES = {'ig_reel', 'ig_story', 'facebook'}


def descargar(url, tipo, carpeta, numero, total):
    prefijos = {'ig_reel': '', 'ig_story': 'story_', 'facebook': 'fb_'}
    prefijo = prefijos.get(tipo, '')

    print(f"\n  Descargando {numero}/{total} [{ETIQUETAS[tipo]}]: {url}")

    # Resolver redirect de Facebook /share/r/
    if tipo == 'facebook' and '/share/r/' in url:
        print(f"  Resolviendo redirect...")
        url = resolver_facebook_share(url)
        print(f"  -> {url}")

    cmd = [
        'yt-dlp', '--no-warnings',
        '-o', os.path.join(carpeta, f'{prefijo}%(id)s.%(ext)s'),
        '--no-overwrites',
    ]

    # Agregar cookies si existen
    if os.path.exists(COOKIES):
        cmd.extend(['--cookies', COOKIES])

    # Para stories, descargar solo el item específico si tiene ID
    if tipo == 'ig_story':
        cmd.append('--no-playlist')

    cmd.append(url)

    try:
        resultado = subprocess.run(cmd, capture_output=True, text=True, timeout=120)
        if resultado.returncode == 0:
            print(f"  OK")
            return True
        else:
            error = resultado.stderr.strip().split('\n')[-1] if resultado.stderr else 'Error desconocido'
            print(f"  ERROR: {error}")
            return False
    except subprocess.TimeoutExpired:
        print(f"  TIMEOUT")
        return False
    except Exception as e:
        print(f"  ERROR: {e}")
        return False


def main():
    print("\n=== Descargador de Reels / Stories / Facebook ===")
    print("Pega tus links (linea vacia + ENTER para continuar):\n")

    lineas = []
    while True:
        try:
            linea = input()
            if linea.strip() == '' and lineas:
                break
            lineas.append(linea)
        except EOFError:
            break

    texto = '\n'.join(lineas)
    if not texto.strip():
        print("No se ingreso texto.")
        sys.exit(1)

    urls_raw = extraer_urls(texto)
    if not urls_raw:
        print("No se encontraron URLs.")
        sys.exit(1)

    # Clasificar y limpiar
    clasificadas = {}
    for url in urls_raw:
        tipo = clasificar_url(url)
        limpia = limpiar_url(url)
        clasificadas.setdefault(tipo, []).append({'original': url, 'limpia': limpia, 'tipo': tipo})

    # Mostrar resumen
    print(f"\n{'='*55}")
    print(f"  {len(urls_raw)} links encontrados:")
    print(f"{'='*55}")

    por_descargar = []
    no_soportados = []

    for tipo, urls in sorted(clasificadas.items()):
        descargable = tipo in DESCARGABLES
        marca = 'DL' if descargable else '--'
        print(f"\n  [{marca}] {ETIQUETAS.get(tipo, tipo)} ({len(urls)}):")
        for item in urls:
            print(f"       {item['limpia']}")
            if descargable:
                por_descargar.append(item)
            else:
                no_soportados.append(item)

    if not por_descargar:
        print("\nNo hay links descargables.")
        sys.exit(0)

    # Verificar cookies
    if os.path.exists(COOKIES):
        print(f"\n  Cookies: {COOKIES}")
    else:
        print(f"\n  Sin cookies (stories/FB pueden fallar)")

    carpeta = os.path.abspath(CARPETA_DESCARGAS)
    os.makedirs(carpeta, exist_ok=True)

    print(f"\n  {len(por_descargar)} links para descargar -> {carpeta}")
    if no_soportados:
        print(f"  {len(no_soportados)} links ignorados (TikTok, YouTube, etc.)")

    respuesta = input(f"\nIniciar? (s/n): ").strip().lower()
    if respuesta not in ('s', 'si', 'y', 'yes', ''):
        print("Cancelado.")
        sys.exit(0)

    # Descargar
    exitosos = 0
    fallidos = 0
    for i, item in enumerate(por_descargar, 1):
        if descargar(item['limpia'], item['tipo'], carpeta, i, len(por_descargar)):
            exitosos += 1
        else:
            fallidos += 1
        if i < len(por_descargar):
            time.sleep(PAUSA_ENTRE_DESCARGAS)

    print(f"\n{'='*55}")
    print(f"  Exitosas: {exitosos}  |  Fallidas: {fallidos}")
    print(f"  Carpeta:  {carpeta}")
    print()


if __name__ == '__main__':
    main()
