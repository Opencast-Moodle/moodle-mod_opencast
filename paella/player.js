'use strict';

import {Paella} from 'paella-core';
import getBasicPluginContext from 'paella-basic-plugins';
import getSlidePluginContext from 'paella-slide-plugins';
import getZoomPluginContext from 'paella-zoom-plugin';
import getUserTrackingPluginsContext from 'paella-user-tracking';
import getMP4MultiQualityContext from 'paella-mp4multiquality-plugin';

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
        getVideoId: async () => manifest.metadata.id,
        getManifestUrl: noop,
        getManifestFileUrl: noop,
        loadVideoManifest: loadVideoManifestFunction,
        customPluginContext: [
            require.context('./plugins', true, /\.js/),
            getBasicPluginContext(),
            getSlidePluginContext(),
            getZoomPluginContext(),
            getUserTrackingPluginsContext(),
            getMP4MultiQualityContext(),
        ]
    });
    paella.skin.loadSkin(themeurl);
    paella.loadManifest()
        .then(() => console.log("Initialization done"))
        .catch(e => console.error(e));
}