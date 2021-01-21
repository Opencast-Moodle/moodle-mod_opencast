define(['jquery', 'mod_opencast/opencast_to_paella_converter'],
    function($, OpencastToPaellaConverter) {

        var wwwroot = M.cfg.wwwroot;

        function initManage() {
            $().ready(() => {
                const iframeWindow = document.getElementById('player-iframe').contentWindow;
                iframeWindow.paella.lazyLoad('playerContainer', {
                    'configUrl': wwwroot + '/mod/opencast/paella/player/config/config.json',
                    loadVideo: function() {
                        return new Promise((resolve) => {
                            let data = new OpencastToPaellaConverter(iframeWindow.paella).convertToDataJson(window.episode);
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
