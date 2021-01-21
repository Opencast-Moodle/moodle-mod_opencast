/**
 * @module     mod_opencast/config
 */
define([], function() {
    window.requirejs.config({
        paths: {
            "swfobject": M.cfg.wwwroot + '/mod/opencast/paella/player/javascript/swfobject',
            "base": M.cfg.wwwroot + '/mod/opencast/paella/player/javascript/base',
            "jquery": M.cfg.wwwroot + '/mod/opencast/paella/player/javascript/jquery.min',
            "lunr": M.cfg.wwwroot + '/mod/opencast/paella/player/javascript/lunr.min',
            "paella": M.cfg.wwwroot + '/mod/opencast/paella/player/javascript/paella_player'
        },
        shim: {
            'swfobject': {exports: 'swfobject'},
            'base': {exports: 'base'},
            'jquery': {exports: 'jquery'},
            'lunr': {exports: 'lunr'},
            'paella': {exports: 'paella', deps: ['base']}
        }
    });
});
