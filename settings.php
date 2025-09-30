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

use mod_opencast\settings\mod_opencast_admin_settingspage_tabs;
use mod_opencast\settings\upload_settings_helper;

defined('MOODLE_INTERNAL') || die();


if ($hassiteconfig) {
    global $ADMIN;

    $settings = null;

    $settingscategory = new admin_category('mod_opencast', new lang_string('pluginname', 'mod_opencast'));
    $ADMIN->add('modsettings', $settingscategory);

    $ocinstances = \tool_opencast\local\settings_api::get_ocinstances();

    $hasmultitenancy = count($ocinstances) > 1;

    $modsettingopencastsubcat = 'mod_opencast';

    if (!$ADMIN->fulltree) {
        foreach ($ocinstances as $ocinstance) {
            if ($hasmultitenancy) {
                $instancecategory = new admin_category('mod_opencast_instance_' . $ocinstance->id, $ocinstance->name);
                $ADMIN->add('mod_opencast', $instancecategory);
                $modsettingopencastsubcat = 'mod_opencast_instance_' . $ocinstance->id;
            }
            // General Settings.
            $settings = new admin_settingpage('mod_opencast_general_' . $ocinstance->id,
                new lang_string('settings:general_header', 'mod_opencast'));
            $ADMIN->add($modsettingopencastsubcat, $settings);

            // Student Download Settings.
            $settings = new admin_settingpage('mod_opencast_student_download_' . $ocinstance->id,
                new lang_string('settings:download_header', 'mod_opencast'));
            $ADMIN->add($modsettingopencastsubcat, $settings);

            // Upload Settings.
            $settings = new admin_settingpage('mod_opencast_upload_' . $ocinstance->id,
                new lang_string('settings:upload_header', 'mod_opencast'));
            $ADMIN->add($modsettingopencastsubcat, $settings);
        }
    } else {
        foreach ($ocinstances as $ocinstance) {
            if ($hasmultitenancy) {
                $instancecategory = new admin_category('mod_opencast_instance_' . $ocinstance->id, $ocinstance->name);
                $ADMIN->add('mod_opencast', $instancecategory);
                $modsettingopencastsubcat = 'mod_opencast_instance_' . $ocinstance->id;
            }

            // General Settings.
            $generalsettings = new admin_settingpage('mod_opencast_general_' . $ocinstance->id,
                new lang_string('settings:general_header', 'mod_opencast'));
            $ADMIN->add($modsettingopencastsubcat, $generalsettings);

            $generalsettings->add(new admin_setting_configtext('mod_opencast/channel_' . $ocinstance->id,
                new lang_string('settings:api-channel', 'mod_opencast'), '', 'api',
                PARAM_ALPHANUMEXT));

            $generalsettings->add(new admin_setting_configtext('mod_opencast/configurl_' . $ocinstance->id,
                new lang_string('settings:configurl', 'mod_opencast'),
                new lang_string('settings:configurl_desc', 'mod_opencast'), '/mod/opencast/config.json'));

            $generalsettings->add(new admin_setting_configtext('mod_opencast/themeurl_' . $ocinstance->id,
                    new lang_string('settings:themeurl', 'mod_opencast'),
                    new lang_string('settings:themeurl_desc', 'mod_opencast'),
                    '/mod/opencast/paella/default_theme/theme.json'));

            // Student Download Settings.
            $studentdownloadsettings = new admin_settingpage('mod_opencast_student_download_' . $ocinstance->id,
                new lang_string('settings:download_header', 'mod_opencast'));
            $ADMIN->add($modsettingopencastsubcat, $studentdownloadsettings);

            $studentdownloadsettings->add(new admin_setting_configtext('mod_opencast/download_channel_' . $ocinstance->id,
                new lang_string('settings:download-channel', 'mod_opencast'),
                new lang_string('settings:download-channel_desc', 'mod_opencast'), 'api',
                PARAM_ALPHANUMEXT));

            $studentdownloadsettings->add(new admin_setting_configcheckbox('mod_opencast/download_default_' . $ocinstance->id,
                new lang_string('settings:download-default', 'mod_opencast'),
                new lang_string('settings:download-default_desc', 'mod_opencast'), 0));

            $studentdownloadsettings->add(new admin_setting_configcheckbox(
                'mod_opencast/enforce_download_default_' . $ocinstance->id,
                new lang_string('settings:enforce_download_default', 'mod_opencast'),
                new lang_string('settings:enforce_download_default_desc', 'mod_opencast'), 0));

            // Upload Settings with tabs.
            $uploadtabssetting = new mod_opencast_admin_settingspage_tabs('mod_opencast_upload_' . $ocinstance->id,
                new lang_string('settings:upload_header', 'mod_opencast'));
            $ADMIN->add($modsettingopencastsubcat, $uploadtabssetting);

            upload_settings_helper::define_upload_tabs_settings($uploadtabssetting, $ocinstance->id);

        }
    }
}

$settings = null; // We do not want standard settings link.
