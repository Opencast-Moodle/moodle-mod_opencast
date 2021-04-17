define(['jquery'],
    function($) {

        var wwwroot = M.cfg.wwwroot;

        function initManage(configUrl) {
            $().ready(() => {
                const iframeWindow = document.getElementById('player-iframe').contentWindow;
                iframeWindow.paella_debug_baseUrl = wwwroot + '/mod/opencast/paella/repository/';
                if (!iframeWindow.paella || !iframeWindow.paella.lazyLoad) {
                    setTimeout(initManage, 20, configUrl);
                    return;
                }
                iframeWindow.paella.lazyLoad('playerContainer', {
                    configUrl: configUrl,
                    loadVideo: function() {
                        return new Promise((resolve) => {
                            resolve(window.episode);
                        });
                    }
                });
            });
        }

        return {
            init: initManage
        };
    });
