/**
 * Vue bootstrap: combines methods from modular files.
 */
(function () {
    'use strict';
    if (typeof Vue === 'undefined') return;
    const { createApp } = Vue;

    const shared = window.VueAppModules?.shared || {};
    const forms = window.VueAppModules?.forms || {};
    const favorites = window.VueAppModules?.favorites || {};
    const gallery = window.VueAppModules?.gallery || {};

    const methods = Object.assign({}, shared, forms, favorites, gallery);
    const page = document.body?.dataset?.page || '';

    const app = createApp({
        mounted() {
            const alwaysMethods = ['bindLoginModalHint', 'bindLogin', 'bindRegister', 'bindRegEmailCheck'];
            const byPage = {
                index: ['bindPagination', 'bindFavoriteButtons', 'bindHistoryPanel'],
                detail: ['bindFavoriteButtons', 'bindDetailGallery', 'trackCurrentDetailInHistory'],
                favorites: ['bindRemoveFavorite'],
                add: ['bindAddForm', 'bindCityArea'],
                edit: ['bindEditForm', 'bindCityArea'],
                'edit-advert': ['bindDeleteButtons'],
                'forgot-password': ['bindForgotForm'],
                'reset-password': ['bindResetForm']
            };

            alwaysMethods.forEach((name) => this[name]?.());
            (byPage[page] || []).forEach((name) => this[name]?.());
        },
        methods,
        template: '<div></div>',
    });

    const mountPoint = document.createElement('div');
    mountPoint.id = 'vue-app';
    document.body.appendChild(mountPoint);
    app.mount('#vue-app');
})();
