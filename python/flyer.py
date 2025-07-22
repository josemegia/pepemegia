from playwright.sync_api import sync_playwright, TimeoutError as PlaywrightTimeoutError
import sys
from datetime import datetime
import os

def log(msg, level="INFO"):
    """
    Función de registro mejorada.
    """
    # Usar el formato de fecha y hora que sea más útil para tus logs de Laravel
    # Aquí un ejemplo que coincide con el formato del log de Laravel:
    # return datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S.%f')[:-3]}] [{level.upper()}] {msg}", flush=True)


def capture_screenshot(url: str, output_path: str, device_name: str, browser_type: str = "chromium"):
    """
    Captura una captura de pantalla de una URL dada con Playwright.
    """
    # 1. Preparación del directorio de salida
    output_dir = os.path.dirname(output_path)
    if not os.path.exists(output_dir):
        try:
            os.makedirs(output_dir, exist_ok=True)
            log(f"Directorio creado: {output_dir}")
        except OSError as e:
            log(f"❌ Error al crear el directorio {output_dir}: {e}", "ERROR")
            return False # Fallar aquí si no se puede crear el directorio

    log(f"Iniciando captura de: {url} con dispositivo '{device_name}' usando {browser_type}")

    browser = None # Inicializar browser a None para el bloque finally
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
                headless=True, # Siempre headless en un servidor
                # Puedes añadir más args para optimizar o resolver problemas de dependencia en Docker/servidor
                # args=['--no-sandbox', '--disable-setuid-sandbox'] # Común para entornos Docker o Linux sin privilegios
            )
            context = browser.new_context(**device_config)
            page = context.new_page()

            # 5. Navegación a la URL
            try:
                page.goto(url, wait_until="networkidle", timeout=60000) # 60 segundos de timeout para la carga
            except PlaywrightTimeoutError:
                log(f"Advertencia: Tiempo de espera (60s) agotado al cargar {url}. La página podría no haber cargado completamente.", "WARNING")
            except Exception as e:
                log(f"❌ Error crítico al navegar a {url}: {e}", "ERROR")
                return False # Error crítico, no se pudo navegar

            # 6. Manipulación opcional de elementos (botón)
            try:
                page.wait_for_selector("#buttons-wrapper", state="attached", timeout=5000) # 5 segundos de timeout para el selector
                page.locator("#buttons-wrapper").evaluate("e => e.remove()")
                log("Elemento #buttons-wrapper eliminado.")
            except PlaywrightTimeoutError:
                log("Advertencia: Elemento #buttons-wrapper no encontrado o no disponible en 5s. No se pudo eliminar.", "WARNING")
            except Exception as e:
                log(f"Advertencia: Error al intentar ocultar el botón: {e}", "WARNING")

            # 7. Tomar la captura de pantalla
            page.screenshot(path=output_path, full_page=True)
            log(f"✅ Captura guardada en {output_path}")
            return True

    except Exception as e:
        # Captura cualquier otro error inesperado en el proceso
        log(f"❌ Error inesperado durante la captura de pantalla: {e}", "ERROR")
        return False
    finally:
        # Asegurarse de que el navegador se cierre siempre
        if browser:
            try:
                browser.close()
                log("Navegador cerrado.")
            except Exception as e:
                log(f"⚠️ Error al cerrar navegador: {e}", "WARNING")



if __name__ == "__main__":
    # Ahora esperamos 4 argumentos: script_name, url, output_path, device_name, [optional_browser_type]
    if len(sys.argv) < 4 or len(sys.argv) > 5:
        log("Uso: flyer.py <url> <output_path> <device_name> [browser_type]", "ERROR")
        log("Ejemplo: python flyer.py https://example.com /tmp/output.png 'iPhone 14 Pro Max' chromium", "ERROR")
        sys.exit(1)

    url = sys.argv[1]
    output_path = sys.argv[2]
    device_name = sys.argv[3]
    browser_type = sys.argv[4] if len(sys.argv) == 5 else "chromium" # Valor por defecto

    if capture_screenshot(url, output_path, device_name, browser_type):
        sys.exit(0) # Éxito
    else:
        sys.exit(1) # Error