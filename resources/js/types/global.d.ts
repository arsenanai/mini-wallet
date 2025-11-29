import '@inertiajs/core';
import { AxiosInstance } from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { route as ziggyRoute } from 'ziggy-js';
import { Page } from './';

declare global {
    interface Window {
        axios: AxiosInstance;
        Pusher: typeof Pusher;
        Echo: Echo<'pusher'>;
    }

    /* eslint-disable no-var */
    var route: typeof ziggyRoute;

    interface ViewTransition {
        readonly finished: Promise<void>;
        readonly ready: Promise<void>;
        readonly updateCallbackDone: Promise<void>;
        skipTransition(): void;
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof ziggyRoute;
    }
}

declare module '@inertiajs/core' {
    interface PageProps extends Page {}
}
