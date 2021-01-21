define(['jquery', 'mod_opencast/opencast_to_paella_converter'],
    function($, initOcToPaella) {

        var wwwroot = M.cfg.wwwroot;

        function initManage() {
            $().ready(() => {
                const iframeWindow = document.getElementById('player-iframe').contentWindow;
                iframeWindow.paella.lazyLoad('playerContainer', {
                    'configUrl': wwwroot + '/mod/opencast/paella/player/config/config.json',
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
