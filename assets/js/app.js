/* Skrypt aplikacji. Ładowany na końcu layoutu. */

(function () {
    'use strict';

    /**
     * Żądanie AJAX rozpoznawane przez AppController::isAjax().
     * Nagłówek X-Requested-With musimy ustawić jawnie — fetch() go nie dodaje.
     */
    window.appFetch = function (url, options) {
        options = options || {};
        options.headers = Object.assign({ 'X-Requested-With': 'XMLHttpRequest' }, options.headers || {});
        return fetch(url, options);
    };
})();
