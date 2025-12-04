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
    authorizer: (channel: { name: string }) => {
        return {
            authorize: (
                socketId: string, // The socket ID for the connection
                callback: (
                    error: Error | null,
                    // The authData object must have an `auth` property.
                    authData: { auth: string } | null,
                ) => void,
            ) => {
                window.axios
                    .post('/api/broadcasting/auth', {
                        socket_id: socketId,
                        channel_name: channel.name,
                    })
                    .then((response) => {
                        callback(null, response.data); // Pass null for the error on success
                    })
                    .catch((error) => {
                        callback(error, null); // Pass the error object on failure
                    });
            },
        };
    },
    // You might need to specify host and port if you are not using the default Pusher sandbox.
    // wsHost: window.location.hostname,
    // wsPort: 6001,
});
