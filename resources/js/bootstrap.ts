import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

/**
 * We'll add this line to configure axios to automatically send cookies with every request.
 * This is necessary for Laravel Sanctum's SPA authentication to work correctly.
 */
window.axios.defaults.withCredentials = true;

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
    // You might need to specify host and port if you are not using the default Pusher sandbox.
    // wsHost: window.location.hostname,
    // wsPort: 6001,
});
