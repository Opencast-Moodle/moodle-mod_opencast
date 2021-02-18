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
    $settings->add(new admin_setting_configtext('mod_opencast/channel',
        new lang_string('settings:api-channel', 'mod_opencast'), '', 'api',
        PARAM_ALPHANUMEXT));

    $settings->add(new admin_setting_configtext('mod_opencast/configurl',
        new lang_string('settings:configurl', 'mod_opencast'),
        new lang_string('settings:configurl_desc', 'mod_opencast'), '/mod/opencast/config.json'));
}