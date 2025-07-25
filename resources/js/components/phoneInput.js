// resources/js/components/phoneInput.js
import { parsePhoneNumberFromString, getExampleNumber } from 'libphonenumber-js'
import examples from 'libphonenumber-js/examples.mobile.json'

export default function phoneInput() {
    return {
        countryCode: 'CO',
        phoneNumber: '',

        get placeholder() {
            try {
                const example = getExampleNumber(this.countryCode, examples)
                return example?.formatNational() || 'Ej. ingresa el número'
            } catch (e) {
                console.error('No se pudo generar placeholder:', e)
                return 'Ej. ingresa el número'
            }
        },

        updatePhoneFormat() {
            this.formatPhone()
        },

        formatPhone() {
            if (!this.phoneNumber) return
            const phoneNumber = parsePhoneNumberFromString(this.phoneNumber, this.countryCode)
            if (phoneNumber && phoneNumber.isValid()) {
                this.phoneNumber = phoneNumber.formatNational()
            }
        }
    }
}
