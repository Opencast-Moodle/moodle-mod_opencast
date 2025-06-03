import { VideoCanvasPlugin } from 'paella-core';

export default class VideoCanvasWithoutContextMenuPlugin extends VideoCanvasPlugin {
  get name() {
    return 'org.opencast.paella.videoCanvasWithoutContextMenu';
  }

  getCanvasInstance(videoContainer) {
    videoContainer.addEventListener('contextmenu', (e) => {
      e.preventDefault();
    });

    return super.getCanvasInstance(videoContainer);
  }
}
