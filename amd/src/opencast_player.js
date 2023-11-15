export const init = (configUrl, themeUrl) => {
    const iframeWindow = document.getElementById('player-iframe').contentWindow;

    if (!iframeWindow.MoodlePaellaPlayer || !window.episode) {
        setTimeout(init, 20, configUrl, themeUrl);
    } else {
        iframeWindow.MoodlePaellaPlayer.initPaella(configUrl, themeUrl, window.episode);
    }
};