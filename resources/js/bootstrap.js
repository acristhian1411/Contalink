import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configure axios for CSRF protection with Sanctum
window.axios.defaults.withCredentials = true;

// Set up CSRF token handling
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// Add request interceptor to handle CSRF cookie
window.axios.interceptors.request.use(
    async (config) => {
        // For state-changing requests, ensure CSRF cookie is set
        if (['post', 'put', 'patch', 'delete'].includes(config.method.toLowerCase())) {
            // Check if we need to get CSRF cookie
            if (!document.cookie.includes('XSRF-TOKEN')) {
                try {
                    await axios.get('/sanctum/csrf-cookie');
                } catch (error) {
                    console.warn('Failed to get CSRF cookie:', error);
                }
            }
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Add response interceptor to handle CSRF errors
window.axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        // Handle CSRF token mismatch
        if (error.response?.status === 419) {
            try {
                // Get new CSRF cookie and retry the request
                await axios.get('/sanctum/csrf-cookie');
                return window.axios.request(error.config);
            } catch (csrfError) {
                console.error('Failed to refresh CSRF token:', csrfError);
                // Redirect to login or show error message
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);
