import './bootstrap'
import Alpine from 'alpinejs'

import './components/datetime.js'
import phoneInput from './components/phoneInput'

Alpine.data('phoneInput', phoneInput)

window.Alpine = Alpine
Alpine.start()
