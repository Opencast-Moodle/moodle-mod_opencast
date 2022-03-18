export const init = (configUrl) => {
    const iframeWindow = document.getElementById('player-iframe').contentWindow;

    if (!iframeWindow.MoodlePaellaPlayer || !window.episode) {
        setTimeout(init, 20, configUrl);
    } else {
        iframeWindow.MoodlePaellaPlayer.initPaella(configUrl, window.episode);
    }
};