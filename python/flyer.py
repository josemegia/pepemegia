from playwright.sync_api import sync_playwright, TimeoutError as PlaywrightTimeoutError
import sys
import locale
from datetime import datetime
import os
from urllib.parse import urlparse, parse_qs, urlencode, urlunparse

locale.setlocale(locale.LC_TIME, 'es_ES.UTF-8')

def log(msg, level="INFO"):
    """
    Función de registro mejorada.
    """
    print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S.%f')[:-3]}] [{level.upper()}] {msg}", flush=True)

def add_lang_if_missing(url, lang='es'):
    """
    Añade el parámetro lang solo si no existe ya en la URL.
    """
    parsed = urlparse(url)
    query_params = parse_qs(parsed.query)
    
    # Solo añadir si no existe el parámetro lang
    if 'lang' not in query_params:
        query_params['lang'] = [lang]
        new_query = urlencode(query_params, doseq=True)
        return urlunparse(parsed._replace(query=new_query))
    return url

def capture_screenshot(url: str, output_path: str, device_name: str, browser_type: str = "chromium", auto_lang: bool = True):
    """
    Captura una captura de pantalla de una URL dada con Playwright.
    """
    # Añadir idioma automáticamente si está habilitado
    if auto_lang:
        original_url = url
        url = add_lang_if_missing(url)
        if url != original_url:
            log(f"URL modificada para incluir idioma: {url}")
    
    # 1. Preparación del directorio de salida
    output_dir = os.path.dirname(output_path)
    if not os.path.exists(output_dir):
        try:
            os.makedirs(output_dir, exist_ok=True)
            log(f"Directorio creado: {output_dir}")
        except OSError as e:
            log(f"❌ Error al crear el directorio {output_dir}: {e}", "ERROR")
            return False

    log(f"Iniciando captura de: {url} con dispositivo '{device_name}' usando {browser_type}")

    browser = None
    try:
        with sync_playwright() as p:
            # 2. Selección y validación del tipo de navegador
            if browser_type == "chromium":
                browser_launcher = p.chromium
            elif browser_type == "firefox":
                browser_launcher = p.firefox
            elif browser_type == "webkit":
                browser_launcher = p.webkit
            else:
                log(f"❌ Tipo de navegador no soportado: '{browser_type}'. Use 'chromium', 'firefox' o 'webkit'.", "ERROR")
                return False

            # 3. Obtención y validación del dispositivo
            try:
                device_config = p.devices[device_name]
            except KeyError:
                log(f"❌ Dispositivo no reconocido: '{device_name}'. Consulte la documentación de Playwright para dispositivos válidos.", "ERROR")
                return False

            # 4. Lanzamiento del navegador
            browser = browser_launcher.launch(
                headless=True,
                args=['--no-sandbox', '--disable-setuid-sandbox'] if os.getenv('DOCKER_ENV') else []
            )
            
            # 5. Crear contexto con configuración del dispositivo
            # Esto configura viewport, user agent, touch events, etc.
            context = browser.new_context(**device_config)
            page = context.new_page()

            # 6. Navegación a la URL
            try:
                page.goto(url, wait_until="networkidle", timeout=60000)
            except PlaywrightTimeoutError:
                log(f"Advertencia: Tiempo de espera (60s) agotado al cargar {url}. La página podría no haber cargado completamente.", "WARNING")
            except Exception as e:
                log(f"❌ Error crítico al navegar a {url}: {e}", "ERROR")
                return False

            # 7. Manipulación opcional de elementos (botón)
            try:
                page.wait_for_selector("#buttons-wrapper", state="attached", timeout=5000)
                page.locator("#buttons-wrapper").evaluate("e => e.remove()")
                log("Elemento #buttons-wrapper eliminado.")
            except PlaywrightTimeoutError:
                log("Advertencia: Elemento #buttons-wrapper no encontrado o no disponible en 5s. No se pudo eliminar.", "WARNING")
            except Exception as e:
                log(f"Advertencia: Error al intentar ocultar el botón: {e}", "WARNING")

            # 8. Tomar la captura de pantalla
            page.screenshot(path=output_path, full_page=True)
            log(f"✅ Captura guardada en {output_path}")
            return True

    except Exception as e:
        log(f"❌ Error inesperado durante la captura de pantalla: {e}", "ERROR")
        return False
    finally:
        # Cerrar navegador sin warnings molestos
        if browser:
            try:
                browser.close()
                log("Navegador cerrado.")
            except Exception as e:
                # Filtrar warnings comunes de cierre
                error_msg = str(e).lower()
                if ("event loop is closed" not in error_msg and 
                    "playwright already stopped" not in error_msg and
                    "object has no attribute" not in error_msg):
                    log(f"⚠️ Error al cerrar navegador: {e}", "WARNING")

if __name__ == "__main__":
    # Uso: script_name, url, output_path, device_name, [browser_type]
    if len(sys.argv) < 4 or len(sys.argv) > 5:
        log("Uso: flyer.py <url> <output_path> <device_name> [browser_type]", "ERROR")
        log("Ejemplo: python flyer.py https://example.com /tmp/output.png 'iPhone 14 Pro Max' chromium", "ERROR")
        log("Dispositivos populares: 'iPhone 14 Pro Max', 'iPad Pro', 'Desktop Chrome', 'Galaxy S20'", "INFO")
        sys.exit(1)

    url = sys.argv[1]
    output_path = sys.argv[2]
    device_name = sys.argv[3]
    browser_type = sys.argv[4] if len(sys.argv) == 5 else "chromium"

    if capture_screenshot(url, output_path, device_name, browser_type):
        sys.exit(0)  # Éxito
    else:
        sys.exit(1)  # Error