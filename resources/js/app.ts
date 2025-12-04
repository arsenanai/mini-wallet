import '../css/app.css';
import './bootstrap';

import { createInertiaApp, Page } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import _ from 'lodash';
import { createApp, DefineComponent, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { i18n } from './i18n';

interface PagePropsWithTranslations extends Page.props {
    jetstream: {
        canCreateTeams: boolean;
        hasTeamFeatures: boolean;
    };
    user: {
        id: number;
        name: string;
        email: string;
    };
    translations: Record<string, string>;
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({
        el,
        App,
        props,
        plugin,
    }: {
        el: HTMLElement;
        App: any;
        props: { initialPage: Page<PagePropsWithTranslations> };
        plugin: any;
    }) {
        // Get the existing frontend translations
        const frontendTranslations = i18n.global.getLocaleMessage('en');
        // Merge them with the backend translations from Inertia props
        const mergedTranslations = _.merge(
            frontendTranslations,
            props.initialPage.props.translations,
        );
        // Set the merged translations
        i18n.global.setLocaleMessage('en', mergedTranslations);
        i18n.global.locale.value = 'en';

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
