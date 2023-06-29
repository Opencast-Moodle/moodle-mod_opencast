import {PluginModule} from 'paella-core';

export default class PaellaMoodlePlugins extends PluginModule {
    get moduleName() {
        return 'paella-moodle-plugins';
    }

    get moduleVersion() {
        return '1.0';
    }
}
