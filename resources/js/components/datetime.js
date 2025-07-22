// resources/js/components/datetime.js

// Flatpickr y su tema (vía alias desde vite.config.js)
import flatpickr from 'flatpickr';
import 'flatpickr-theme';

// Idiomas disponibles (puedes añadir más si lo necesitas)
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import { English } from 'flatpickr/dist/l10n/default.js';
// import { Russian } from 'flatpickr/dist/l10n/ru.js'; // Ejemplo

// Disponibles globalmente (por si necesitas acceder desde otros scripts o Blade)
window.flatpickr = flatpickr;
window.flatpickrLocales = {
    es: Spanish,
    en: English,
    // ru: Russian,
};

// Función de inicialización reutilizable
function initFlatpickrPickers() {
    const localeCode = document.documentElement.lang?.substring(0, 2) || 'es';
    const selectedLocale = window.flatpickrLocales[localeCode] || window.flatpickrLocales['es'];

    flatpickr('.flatpickr-date', {
        defaultDate: new Date(), // hoy
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'l d \\d\\e F',
        locale: selectedLocale,
        allowInput: true,
        disableMobile: true,
    });


    flatpickr('.flatpickr-time', {
        enableTime: true,
        noCalendar: true,
        defaultDate: new Date(), // ahora
        dateFormat: 'H:i',
        altInput: true,
        altFormat: 'h:i K',
        time_24hr: false,
        locale: selectedLocale,
        allowInput: true,
        disableMobile: true,
    });

}

// Ejecutar en DOM listo
document.addEventListener('DOMContentLoaded', initFlatpickrPickers);
