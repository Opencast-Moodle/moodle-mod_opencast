<?php
// This file is part of Moodle - http://moodle.org/
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
 * Upload Settings helper class for Opencast Activity module.
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_opencast\settings;

use admin_setting_configcheckbox;
use admin_setting_configempty;
use admin_setting_configselect;
use admin_setting_configmultiselect;
use admin_setting_heading;
use admin_settingpage;
use lang_string;
use tool_opencast\local\workflowconfiguration_helper;

/**
 * Upload Settings helper class for Opencast Activity module.
 *
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_settings_helper {

    /** @var string The setting tab config ID prefix. */
    const SETTING_TAB_PREFIX = 'mod_opencast_upload_';

    /** @var string The setting config ID prefix. */
    const SETTING_ID_PREFIX = 'mod_opencast/upload_';

    /** @var string The setting string ID prefix. */
    const SETTING_STRING_ID_PREFIX = 'settings:upload_';

    /** @var string The metadata tab name/ID. */
    const METADATA_TAB = 'metadata';

    /** @var string The presentation tab name/ID. */
    const PRESENTATION_TAB = 'presentation';

    /** @var string The visibility tab name/ID. */
    const VISIBILITY_TAB = 'visibility';

    /** @var string The processing tab name/ID. */
    const PROCESSING_TAB = 'processing';

    /** @var string The subtitle tab name/ID. */
    const SUBTITLE_TAB = 'subtitle';

    /** @var array The settings tabs order. */
    const SETTINGS_TABS_ORDERS = [
        self::METADATA_TAB,
        self::PRESENTATION_TAB,
        self::VISIBILITY_TAB,
        self::PROCESSING_TAB,
        self::SUBTITLE_TAB,
    ];

    /** @var array The advanced tab orders. */
    const ADVANCED_TAB_ORDERS = [
        self::METADATA_TAB,
        self::PRESENTATION_TAB,
        self::VISIBILITY_TAB,
        self::PROCESSING_TAB,
        self::SUBTITLE_TAB,
    ];

    /**
     * Defines the upload settings tabs and their settings.
     *
     * @param mod_opencast_admin_settingspage_tabs $uploadtabssetting The upload tabs settings page.
     * @param int $ocinstanceid The Opencast instance ID.
     */
    public static function define_upload_tabs_settings(mod_opencast_admin_settingspage_tabs &$uploadtabssetting, $ocinstanceid) {
        // Main Tab is always at the first place.
        self::add_main_tab_settings($uploadtabssetting, $ocinstanceid);

        // Loop through the tab orders to call and render the tabs based of the defined order in SETTINGS_TABS_ORDERS.
        foreach (self::SETTINGS_TABS_ORDERS as $tabname) {
            $methodname = "add_{$tabname}_tab_settings";
            if (method_exists(self::class, $methodname)) {
                // Dynamically call the methods.
                self::{$methodname}($uploadtabssetting, $ocinstanceid);
            }
        }
    }

    /**
     * Adds the main tab settings to the upload settings tabs.
     *
     * @param mod_opencast_admin_settingspage_tabs $uploadtabssetting The upload tabs settings page.
     * @param int $ocinstanceid The Opencast instance ID.
     */
    private static function add_main_tab_settings(mod_opencast_admin_settingspage_tabs &$uploadtabssetting, $ocinstanceid) {
        $tabname = 'main';
        $settings = new admin_settingpage(self::SETTING_TAB_PREFIX . $tabname . '_' . $ocinstanceid,
            new lang_string(self::SETTING_STRING_ID_PREFIX . $tabname, 'mod_opencast'));
        // Add heading.
        self::add_heading($settings, $tabname, $ocinstanceid);

        // Inline Visibility activation.
        $settingname = 'inline_visibility';
        $settingid = self::SETTING_ID_PREFIX . $settingname . '_' . $ocinstanceid;
        $enablevisibility = new admin_setting_configcheckbox($settingid,
            new lang_string(self::SETTING_STRING_ID_PREFIX . $settingname, 'mod_opencast'),
            new lang_string(self::SETTING_STRING_ID_PREFIX . $settingname . '_desc', 'mod_opencast'),
            1
        );
        $settings->add($enablevisibility);

        // Inline Processing setting.
        $settingname = 'inline_processing';
        $settingid = self::SETTING_ID_PREFIX . $settingname . '_' . $ocinstanceid;
        $enableprocessing = new admin_setting_configcheckbox($settingid,
            new lang_string(self::SETTING_STRING_ID_PREFIX . $settingname, 'mod_opencast'),
            new lang_string(self::SETTING_STRING_ID_PREFIX . $settingname . '_desc', 'mod_opencast'),
            0
        );
        $settings->add($enableprocessing);

        // Enable advanced mode setting.
        $settingname = 'enable_advanced_mode';
        $settingid = self::SETTING_ID_PREFIX . $settingname . '_' . $ocinstanceid;
        $enableadvanced = new admin_setting_configcheckbox($settingid,
            new lang_string(self::SETTING_STRING_ID_PREFIX . $settingname, 'mod_opencast'),
            new lang_string(self::SETTING_STRING_ID_PREFIX . $settingname . '_desc', 'mod_opencast'),
            1
        );
        $settings->add($enableadvanced);

        $uploadtabssetting->add($settings);
    }

    /**
     * Adds the metadata tab settings to the upload settings tabs.
     *
     * @param mod_opencast_admin_settingspage_tabs $uploadtabssetting The upload tabs settings page.
     * @param int $ocinstanceid The Opencast instance ID.
     */
    private static function add_metadata_tab_settings(mod_opencast_admin_settingspage_tabs &$uploadtabssetting, $ocinstanceid) {
        $tabname = 'metadata';
        $settings = new admin_settingpage(self::SETTING_TAB_PREFIX . $tabname . '_' . $ocinstanceid,
            new lang_string(self::SETTING_STRING_ID_PREFIX . $tabname, 'mod_opencast'));
        self::add_heading($settings, $tabname, $ocinstanceid);

        self::add_activation_toggle($settings, $tabname, $ocinstanceid);

        // Extract configured event metadata.
        $eventmetadata = json_decode(get_config('tool_opencast', 'metadata_' . $ocinstanceid));
        $metadatalist = [];
        if ($eventmetadata) {
            foreach ($eventmetadata as $obj) {
                if ($obj->name === 'title' || !empty($obj->required)) {
                    continue;
                }
                $metadatalist[$obj->name] = self::try_get_string($obj->name, 'tool_opencast');
            }
        }

        $settings->add(new admin_setting_configmultiselect(self::SETTING_ID_PREFIX . $tabname . '_list_' . $ocinstanceid,
            new lang_string(self::SETTING_STRING_ID_PREFIX . $tabname . '_list', 'mod_opencast'),
            new lang_string(self::SETTING_STRING_ID_PREFIX . $tabname . '_list_desc', 'mod_opencast'),
            null,
            $metadatalist
        ));

        $uploadtabssetting->add($settings);
    }

    /**
     * Adds the presentation tab settings to the upload settings tabs.
     *
     * @param mod_opencast_admin_settingspage_tabs $uploadtabssetting The upload tabs settings page.
     * @param int $ocinstanceid The Opencast instance ID.
     */
    private static function add_presentation_tab_settings(mod_opencast_admin_settingspage_tabs &$uploadtabssetting, $ocinstanceid) {
        $tabname = 'presentation';
        $settings = new admin_settingpage(self::SETTING_TAB_PREFIX . $tabname . '_' . $ocinstanceid,
            new lang_string(self::SETTING_STRING_ID_PREFIX . $tabname, 'mod_opencast'));
        self::add_heading($settings, $tabname, $ocinstanceid);

        self::add_activation_toggle($settings, $tabname, $ocinstanceid);

        $uploadtabssetting->add($settings);
    }

    /**
     * Adds the visibility tab settings to the upload settings tabs.
     *
     * @param mod_opencast_admin_settingspage_tabs $uploadtabssetting The upload tabs settings page.
     * @param int $ocinstanceid The Opencast instance ID.
     */
    private static function add_visibility_tab_settings(mod_opencast_admin_settingspage_tabs &$uploadtabssetting, $ocinstanceid) {
        global $PAGE;
        $tabname = 'visibility';
        $settings = new admin_settingpage(self::SETTING_TAB_PREFIX . $tabname . '_' . $ocinstanceid,
            new lang_string(self::SETTING_STRING_ID_PREFIX . $tabname, 'mod_opencast'));
        self::add_heading($settings, $tabname, $ocinstanceid);

        self::add_activation_toggle($settings, $tabname, $ocinstanceid);

        $mainrenderer = $PAGE->get_renderer('tool_opencast');

        $visiblitychoices = [
            $mainrenderer::VISIBLE => new lang_string('legendvisibility_visible', 'tool_opencast'),
            $mainrenderer::HIDDEN => new lang_string('legendvisibility_hidden', 'tool_opencast'),
        ];

        $defaultvisibility = $mainrenderer::VISIBLE;

        $settings->add(new admin_setting_configselect(self::SETTING_ID_PREFIX . $tabname . '_default_' . $ocinstanceid,
            new lang_string(self::SETTING_STRING_ID_PREFIX . $tabname . '_default', 'mod_opencast'),
            new lang_string(self::SETTING_STRING_ID_PREFIX . $tabname . '_default_desc', 'mod_opencast'),
            $defaultvisibility,
            $visiblitychoices
        ));

        $uploadtabssetting->add($settings);
    }

    /**
     * Adds the processing tab settings to the upload settings tabs.
     *
     * @param mod_opencast_admin_settingspage_tabs $uploadtabssetting The upload tabs settings page.
     * @param int $ocinstanceid The Opencast instance ID.
     */
    private static function add_processing_tab_settings(mod_opencast_admin_settingspage_tabs &$uploadtabssetting, $ocinstanceid) {
        $tabname = 'processing';
        $settings = new admin_settingpage(self::SETTING_TAB_PREFIX . $tabname . '_' . $ocinstanceid,
            new lang_string(self::SETTING_STRING_ID_PREFIX . $tabname, 'mod_opencast'));
        self::add_heading($settings, $tabname, $ocinstanceid);

        $disabled = false;
        if (!(defined('BEHAT_UTIL') && BEHAT_UTIL) && !(defined('PHPUNIT_UTIL') && PHPUNIT_UTIL) && !during_initial_install()) {
            try {
                $wfconfighelper = workflowconfiguration_helper::get_instance($ocinstanceid);
                if (!$wfconfighelper->can_provide_configuration_panel()) {
                    $disabled = true;
                }
            } catch (\Throwable $e) {
                // We want this to NOT throw error at this point, when it does we just disable the feature.
                $disabled = false;
            }
        }

        self::add_activation_toggle($settings, $tabname, $ocinstanceid, $disabled);

        $uploadtabssetting->add($settings);
    }

    /**
     * Adds the subtitle tab settings to the upload settings tabs.
     *
     * @param mod_opencast_admin_settingspage_tabs $uploadtabssetting The upload tabs settings page.
     * @param int $ocinstanceid The Opencast instance ID.
     */
    private static function add_subtitle_tab_settings(mod_opencast_admin_settingspage_tabs &$uploadtabssetting, $ocinstanceid) {
        $tabname = 'subtitle';
        $settings = new admin_settingpage(self::SETTING_TAB_PREFIX . $tabname . '_' . $ocinstanceid,
            new lang_string(self::SETTING_STRING_ID_PREFIX . $tabname, 'mod_opencast'));
        self::add_heading($settings, $tabname, $ocinstanceid);

        $disabled = false;

        // Prepare the subtitle settings check from tool plugin.
        $sublangsconfig = get_config('tool_opencast', 'transcriptionlanguages_' . $ocinstanceid);
        $subuploadenabled = (bool) get_config('tool_opencast', 'enableuploadtranscription_' . $ocinstanceid);

        // If the transcription upload is not enabled or no languages are configured, we show a disabled config with information.
        if (!$subuploadenabled || empty($sublangsconfig)) {
            $disabled = true;
        }

        self::add_activation_toggle($settings, $tabname, $ocinstanceid, $disabled);

        // In case the subtitle is already configured in block/tool plugin, we proceed with offering more settings.
        if (!$disabled) {
            $languagesselection = [];
            $configedlanguages = json_decode($sublangsconfig);
            foreach ($configedlanguages as $lang) {
                $languagesselection[$lang->key] = $lang->value;
            }

            $settings->add(new admin_setting_configmultiselect(self::SETTING_ID_PREFIX . $tabname . '_langlist_' . $ocinstanceid,
                new lang_string(self::SETTING_STRING_ID_PREFIX . $tabname . '_langlist', 'mod_opencast'),
                new lang_string(self::SETTING_STRING_ID_PREFIX . $tabname . '_langlist_desc', 'mod_opencast'),
                array_keys($languagesselection),
                $languagesselection
            ));
        }

        $uploadtabssetting->add($settings);
    }

    /**
     * Adds a heading to the settings page.
     *
     * @param admin_settingpage $settings The settings page to add the heading to.
     * @param string $tabname The name of the tab.
     * @param int $ocinstanceid The Opencast instance ID.
     */
    private static function add_heading(\admin_settingpage &$settings, $tabname, $ocinstanceid) {
        $stringname = self::SETTING_STRING_ID_PREFIX . $tabname;
        $settings->add(
            new admin_setting_heading(self::SETTING_ID_PREFIX . $tabname . '_h_' . $ocinstanceid,
                new lang_string($stringname . '_heading', 'mod_opencast'),
                new lang_string($stringname . '_desc', 'mod_opencast')
            )
        );
    }

    /**
     * Adds an activation toggle to the settings page for each tab.
     *
     * @param admin_settingpage $settings The settings page to add the toggle to.
     * @param string $tabname The name of the tab.
     * @param int $ocinstanceid The Opencast instance ID.
     * @param bool $disabled Whether the toggle should be disabled.
     */
    private static function add_activation_toggle(\admin_settingpage &$settings, $tabname, $ocinstanceid, $disabled = false) {
        $configid = self::SETTING_ID_PREFIX . $tabname . '_activate_' . $ocinstanceid;
        $configstringkey = self::SETTING_STRING_ID_PREFIX . $tabname . '_activate';
        $configdescstringkey = $configstringkey . '_desc';
        // In case the setting is disabled, we switch to configempty element.
        if ($disabled) {
            $settings->add(new admin_setting_configempty($configid,
                new lang_string($configstringkey, 'mod_opencast'),
                new lang_string($configdescstringkey . '_disabled', 'mod_opencast')),
                0
            );
            return;
        }

        $settings->add(
            new admin_setting_configcheckbox($configid,
                new lang_string($configstringkey, 'mod_opencast'),
                new lang_string($configdescstringkey, 'mod_opencast'),
                1
            )
        );
    }

    /**
     * Tries to get the string for identifier and component.
     * As a fallback it outputs the identifier itself with the first letter being uppercase.
     * @param string $identifier The key identifier for the localized string
     * @param string $component The module where the key identifier is stored,
     *      usually expressed as the filename in the language pack without the
     *      .php on the end but can also be written as mod/forum or grade/export/xls.
     *      If none is specified then moodle.php is used.
     * @param string|object|array $a An object, string or number that can be used
     *      within translation strings
     * @return string
     * @throws coding_exception
     */
    protected static function try_get_string($identifier, $component = '', $a = null) {
        if (!get_string_manager()->string_exists($identifier, $component)) {
            return ucfirst($identifier);
        } else {
            return get_string($identifier, $component, $a);
        }
    }
}
