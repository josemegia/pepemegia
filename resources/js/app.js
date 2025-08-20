import './bootstrap'
import Alpine from 'alpinejs'

import './components/datetime.js'
import phoneInput from './components/phoneInput'
import eventDetailsHandler from './components/eventDetailsHandler.js'

Alpine.data('phoneInput', phoneInput)
Alpine.data('eventDetailsHandler', eventDetailsHandler)

window.Alpine = Alpine
Alpine.start()
