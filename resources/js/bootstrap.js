import axios from 'axios';

window.axios = axios;

// Enviar siempre cabecera Ajax
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Enviar cookies de sesión (importante para mantener la autenticación)
window.axios.defaults.withCredentials = true;

// CSRF token para las peticiones POST, PUT, DELETE, etc.
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}
