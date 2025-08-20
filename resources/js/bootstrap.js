import axios from 'axios';
import * as libphonenumber from 'libphonenumber-js/max';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.libphonenumber = libphonenumber;