define(['jquery'],
    function($) {

        /**
         * Initializes the Paella Player
         * @param {string} configUrl URL of the config.json
         */
        function initPaella(configUrl) {
            $().ready(() => {
                const iframeWindow = document.getElementById('player-iframe').contentWindow;
                if (!iframeWindow.paella || !iframeWindow.paella.lazyLoad || !window.episode) {
                    setTimeout(initPaella, 20, configUrl);
                    return;
                }
                iframeWindow.paella.lazyLoad('playerContainer', {
                    configUrl: configUrl,
                    data: window.episode
                });
            });
        }

        return {
            init: initPaella
        };
    });
