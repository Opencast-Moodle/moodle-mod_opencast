<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Adds admin settings for the plugin.
 *
 * @package     mod_opencast
 * @category    admin
 * @copyright   2021 Justus Dieckmann WWU
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ocinstances = \tool_opencast\local\settings_api::get_ocinstances();

    foreach ($ocinstances as $ocinstance) {
        $settings->add(
            new admin_setting_heading('mod_opencast/settings_' . $ocinstance->id, $ocinstance->name, ''));

        $settings->add(new admin_setting_configtext('mod_opencast/channel_' . $ocinstance->id,
            new lang_string('settings:api-channel', 'mod_opencast'), '', 'api',
            PARAM_ALPHANUMEXT));


        $settings->add(new admin_setting_configtext('mod_opencast/configurl_' . $ocinstance->id,
            new lang_string('settings:configurl', 'mod_opencast'),
            new lang_string('settings:configurl_desc', 'mod_opencast'), '/mod/opencast/config.json'));

        $settings->add(new admin_setting_configtext('mod_opencast/themeurl_' . $ocinstance->id,
                new lang_string('settings:themeurl', 'mod_opencast'),
                new lang_string('settings:themeurl_desc', 'mod_opencast'),
                '/mod/opencast/paella/default_theme/theme.json'));

        $settings->add(
            new admin_setting_heading('mod_opencast/download_' . $ocinstance->id,
                $ocinstance->name . ': ' . get_string('settings:download_header', 'mod_opencast'),
                ''));

        $settings->add(new admin_setting_configtext('mod_opencast/download_channel_' . $ocinstance->id,
            new lang_string('settings:download-channel', 'mod_opencast'),
            new lang_string('settings:download-channel_desc', 'mod_opencast'), 'api',
            PARAM_ALPHANUMEXT));

        $settings->add(new admin_setting_configcheckbox('mod_opencast/download_default_' . $ocinstance->id,
            new lang_string('settings:download-default', 'mod_opencast'),
            new lang_string('settings:download-default_desc', 'mod_opencast'), 0));

        $settings->add(new admin_setting_configcheckbox('mod_opencast/global_download_' . $ocinstance->id,
            new lang_string('settings:global_download', 'mod_opencast'),
            new lang_string('settings:global_download_desc', 'mod_opencast'), 0));
    }
}
