define(['jquery', 'mod_opencast/opencast_to_paella_converter'],
    function($, initOcToPaella) {

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
                            const OpencastToPaellaConverter = initOcToPaella(iframeWindow.base, iframeWindow.paella);
                            let data = new OpencastToPaellaConverter().convertToDataJson(window.episode);
                            resolve(data);
                        });
                    }
                });
            });
        }

        return {
            init: initManage
        };
    });
