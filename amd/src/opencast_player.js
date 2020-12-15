define(['mod_opencastepisode/jquery', 'jqueryui', 'mod_opencastepisode/swfobject', 'mod_opencastepisode/base',
        'mod_opencastepisode/lunr', 'mod_opencastepisode/paella', 'mod_opencastepisode/opencast_to_paella_converter'],
    function($, jqui, swfobject, base, lunr, paella, OpencastToPaellaConverter) {

        var wwwroot = M.cfg.wwwroot;

        function initManage(id, episode) {
            $("body").ready(() => {
                paella.lazyLoad(id, {
                    'configUrl': wwwroot + '/mod/opencastepisode/paella/player/config/config.json',
                    loadVideo: function () {
                        return new Promise((resolve) => {
                            let data = new OpencastToPaellaConverter().convertToDataJson(episode);
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