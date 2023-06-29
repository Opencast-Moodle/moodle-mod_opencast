'use strict';

import {Paella} from 'paella-core';
import getBasicPluginContext from 'paella-basic-plugins';
import getSlidePluginContext from 'paella-slide-plugins';
import getZoomPluginContext from 'paella-zoom-plugin';
import getUserTrackingPluginsContext from 'paella-user-tracking';

const loadVideoManifestFunction = () => {
    return window.episode;
};

const noop = () => {
};

export const initPaella = (configurl, themeurl, manifest) => {
    window.episode = manifest;
    let paella = new Paella('playerContainer', {
        logLevel: "DEBUG",
        configUrl: configurl,
        getManifestUrl: noop,
        getManifestFileUrl: noop,
        loadVideoManifest: loadVideoManifestFunction,
        customPluginContext: [
            require.context('./plugins', true, /\.js/),
            getBasicPluginContext(),
            getSlidePluginContext(),
            getZoomPluginContext(),
            getUserTrackingPluginsContext()
        ]
    });
    paella.skin.loadSkin(themeurl);
    paella.loadManifest()
        .then(() => console.log("Initialization done"))
        .catch(e => console.error(e));
}