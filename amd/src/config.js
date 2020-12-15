/***
 * @module     mod_opencastepisode/config
 */
define([], function () {
    // Ugly, but I couldn't find a better solution.
    window.paella_debug_baseUrl = M.cfg.wwwroot + "/mod/opencastepisode/paella/player/";
    window.requirejs.config({
        paths: {
            "swfobject": M.cfg.wwwroot + '/mod/opencastepisode/paella/player/javascript/swfobject',
            "base": M.cfg.wwwroot + '/mod/opencastepisode/paella/player/javascript/base',
            "jquery": M.cfg.wwwroot + '/mod/opencastepisode/paella/player/javascript/jquery.min',
            "lunr": M.cfg.wwwroot + '/mod/opencastepisode/paella/player/javascript/lunr.min',
            "paella": M.cfg.wwwroot + '/mod/opencastepisode/paella/player/javascript/paella_player'
        },
        shim: {
            'swfobject': {exports: 'swfobject'},
            'base': {exports: 'base'},
            'jquery': {exports: 'jquery'},
            'lunr': {exports: 'lunr'},
            'paella': {exports: 'paella', deps:['base']}
        }
    });
});