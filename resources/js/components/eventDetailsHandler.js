export default function eventDetailsHandler() {
    return {
        // --- ESTADO CENTRALIZADO ---
        phoneValid: false,
        e164Phone: '', // Guardamos el número formateado aquí
        showWhatsapp: false,
        showZoom: false,
        showMaps: false,
        ctaLink: '',

        /**
         * Valida el teléfono y actualiza el estado.
         * Es la ÚNICA función que modifica phoneValid y e164Phone.
         */
        validatePhone() {
            const phoneInput = document.getElementById('event_phone')?.value || '';
            const countrySelect = document.getElementById('event_phone_country')?.value || '';
            const rawPhone = phoneInput.replace(/\D/g, '');
            const rawCode = countrySelect.trim().toUpperCase();

            // Siempre resetea el estado antes de una nueva validación
            this.phoneValid = false;
            this.e164Phone = '';

            if (!rawPhone || !rawCode) return;

            try {
                const phoneNumber = libphonenumber.parsePhoneNumberFromString(rawPhone, rawCode);

                if (phoneNumber && phoneNumber.isValid()) {
                    this.phoneValid = true;
                    // GUARDA EL NÚMERO FORMATEADO EN EL ESTADO
                    this.e164Phone = phoneNumber.number;
                }
            } catch (error) {
                // Falla silenciosamente
            }
        },

        /**
         * Actualiza las opciones de la UI basándose en el estado actual.
         */
        updateOptions() {
            this.validatePhone(); // Llama a la única fuente de verdad para la validación

            const platform = document.getElementById('event_platform')?.value || '';
            const details = document.getElementById('event_platform_details')?.value || '';
            const combined = `${platform} ${details}`.toLowerCase();
            const zoomMatch = combined.includes('zoom') && (combined.match(/\d{9,}/) || [])[0];

            //this.showZoom = !!zoomMatch;
            this.showZoom = combined.includes('zoom');
            this.showMaps = !combined.includes('zoom') && details.trim() !== '';
            this.showWhatsapp = this.phoneValid && (this.showZoom || this.showMaps);

            // Lógica para autoseleccionar la opción en el dropdown
            setTimeout(() => {
                const select = this.$refs.presetSelect;
                if (!select) return;

                const options = [];
                if (this.showWhatsapp) options.push('whatsapp');
                if (this.showMaps) options.push('maps');
                if (this.showZoom) options.push('zoom');

                if (options.length === 1) {
                    select.value = options[0];
                    this.buildPresetLink(options[0]);
                } else if (!options.includes(select.value)) {
                    select.value = '';
                    this.ctaLink = '';
                }
            }, 0);
        },

        /**
         * Construye el enlace de acción. NO valida de nuevo, solo USA el estado.
         */
        buildPresetLink(type) {
            const platform = document.getElementById('event_platform')?.value || '';
            const details = document.getElementById('event_platform_details')?.value || '';
            const combined = `${platform} ${details}`.toLowerCase();
            const zoomId = combined.includes('zoom') ? ((combined.match(/\d{9,}/) || [])[0] || 'pending') : '';
            let locationText = '';

            if (zoomId) {
                locationText = `https://zoom.us/j/${zoomId}`;
            } else if (!combined.includes('zoom') && details.trim()) {
                locationText = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(details)}`;
            }
            
            // USA EL NÚMERO GUARDADO. No se recalcula nada.
            const phone = this.e164Phone;

            if (type === 'whatsapp' && this.phoneValid && locationText) {
                const msg = `*Hola*,\n_Deseo asistir_:\n> 📍 ${locationText}\n*Gracias*.\n😊`;
                this.ctaLink = `https://api.whatsapp.com/send?phone=${phone.replace('+', '')}&text=${encodeURIComponent(msg)}`;
            } else if (type === 'maps' && locationText.includes('google.com/maps')) {
                this.ctaLink = locationText;
            } else if (type === 'zoom' && locationText.includes('zoom.us')) {
                this.ctaLink = locationText;
            } else {
                this.ctaLink = '';
            }
        },

        init() {
            this.updateOptions();
        }
    };
}