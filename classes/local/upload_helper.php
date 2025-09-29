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
 * Upload helper class for Opencast Activity module.
 *
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_opencast\local;

use tool_opencast\local\autocomplete_suggestion_helper;
use tool_opencast\local\upload_helper as tool_upload_helper;
use tool_opencast\local\workflowconfiguration_helper;
use tool_opencast\local\apibridge;
use tool_opencast\local\settings_api;
use tool_opencast\seriesmapping;
use MoodleQuickForm;
use moodle_url;
use stdClass;

/**
 * Upload helper class for Opencast Activity module.
 *
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_helper {

    /** @var string The presentation file picker ID. */
    const PRESENTATION_FILEPICKER_ID = 'presentation_filepicker';

    /** @var string The presentation chunk upload ID. */
    const PRESENTATION_CHUNKUPLOAD_ID = 'presentation_chunkupload';

    /** @var string The visibility ID. */
    const VISIBILITY_ID = 'visibility';

    /** @var string The metadata ID prefix. */
    const METADATA_ID_PREFIX = 'metadata_';

    /** @var string The transcription/subtitle file ID prefix. */
    const TRANSCRIPTION_ID_PREFIX = 'transcription_file_';

    /** @var string The transcription enabled ID suffix. */
    const TRANSCRIPTION_ENABLED_ID_SUFFIX = '_enabled';

    /**
     * Retrieves the configuration data for the advanced upload form tabs for a given Opencast instance.
     *
     * Iterates through all advanced tab orders, checks if each tab is enabled, and collects their configuration
     * using the corresponding tab config methods. Returns an array containing the configuration for all enabled tabs.
     *
     * @param int $ocinstanceid The Opencast instance ID.
     * @param int $ocmoduleinstanceid The Opencast module instance ID.
     * @return array The configuration data for the advanced upload form tabs.
     */
    public static function get_advanced_upload_form_data(int $ocinstanceid, int $ocmoduleinstanceid): array {
        $formdata = [];
        foreach (\mod_opencast\settings\upload_settings_helper::ADVANCED_TAB_ORDERS as $tabname) {
            if (self::advanced_tab_enabled($ocinstanceid, $tabname)) {
                $tabconfig = [];
                $methodname = "get_advanced_tab_{$tabname}_config";
                if (method_exists(self::class, $methodname)) {
                    // Dynamically call the methods.
                    $tabconfig = self::{$methodname}($ocinstanceid, $ocmoduleinstanceid);
                }
                if (!empty($tabconfig)) {
                    $formdata['tabs'][$tabname] = $tabconfig;
                }
            }
        }
        return $formdata;
    }

    /**
     * Checks if a specific advanced upload tab is enabled for the given Opencast instance.
     *
     * @param int $ocinstanceid The Opencast instance ID.
     * @param string $tabname The name of the tab to check.
     * @return bool True if the tab is enabled, false otherwise.
     */
    public static function advanced_tab_enabled(int $ocinstanceid, string $tabname): bool {
        $settingid = "upload_{$tabname}_activate_{$ocinstanceid}";
        return (bool) get_config('mod_opencast', $settingid);
    }

    /**
     * Retrieves the configuration for the "metadata" advanced upload tab for a given Opencast instance.
     *
     * Collects required and optional metadata fields, converts them to advancedupload_field objects,
     * and returns them as an array for use in the advanced upload form.
     *
     * @param int $ocinstanceid The Opencast instance ID.
     * @param int $ocmoduleinstanceid The Opencast module instance ID.
     * @return array Array of advancedupload_field objects representing metadata fields.
     */
    private static function get_advanced_tab_metadata_config(int $ocinstanceid, int $ocmoduleinstanceid): array {
        global $DB;
        $ocmoduleinstance = $DB->get_record('opencast', ['id' => $ocmoduleinstanceid], '*', MUST_EXIST);
        $simpleuploadoptions = json_decode($ocmoduleinstance->uploadoptionsjson);
        $simpleuploadmetadata = $simpleuploadoptions->options->{$ocinstanceid}->metadata ?? new stdClass();
        $config = [];
        $requiredmtcats = self::get_metadatacatalogs($ocinstanceid, true, false);
        if (!empty($requiredmtcats)) {
            foreach ($requiredmtcats as $field) {
                $advancedfield = self::convert_metadatacatalog($field, $ocinstanceid);
                if (isset($simpleuploadmetadata->{$field->name})) {
                    $configuredvalue = $simpleuploadmetadata->{$field->name}->value ?? null;
                    $advancedfield->set_default($configuredvalue);
                }
                $config[] = $advancedfield;
            }
        }
        $optionalmtcats = self::get_metadatacatalogs($ocinstanceid, false, true);
        if (!empty($optionalmtcats)) {
            foreach ($optionalmtcats as $field) {
                $additionalattr['set_advanced'] = true;
                $config[] = self::convert_metadatacatalog($field, $ocinstanceid, $additionalattr);
            }
        }
        return $config;
    }

    /**
     * Converts a metadata catalog field to an advancedupload_field object.
     *
     * Prepares the datatype, id, label, parameters, and attributes for the field.
     * Handles special cases for autocomplete and select datatypes, and merges additional attributes if provided.
     *
     * @param stdClass $field The metadata field object.
     * @param int $ocinstanceid The Opencast instance ID.
     * @param array $additionalattr Optional additional HTML attributes to merge.
     * @return advancedupload_field The constructed advancedupload_field object.
     */
    private static function convert_metadatacatalog(
        stdClass $field, int $ocinstanceid, array $additionalattr = []
    ): advancedupload_field {
        $datatype = $field->datatype;
        $id = self::METADATA_ID_PREFIX . $field->name;
        $label = ucfirst($field->name);
        if (get_string_manager()->string_exists($field->name, 'tool_opencast')) {
            $label = get_string($field->name, 'tool_opencast');
        }
        $params = [];
        if ($field->param_json) {
            $params = $datatype == 'static' ? $field->param_json : json_decode($field->param_json, true);
        }
        $attributes = [];
        if ($datatype == 'autocomplete') {
            $attributes = [
                'multiple' => true,
                'placeholder' => get_string('metadata_autocomplete_placeholder', 'tool_opencast', $label),
                'showsuggestions' => true,
                'noselectionstring' => get_string('metadata_autocomplete_noselectionstring', 'tool_opencast', $label),
                'tags' => true,
            ];

            if ($field->name == 'creator' || $field->name == 'contributor') {
                $params = array_merge($params,
                    autocomplete_suggestion_helper::get_suggestions_for_creator_and_contributor($ocinstanceid));
            }
        }
        if ($field->datatype == 'select') {
            array_walk($params, function (&$item) {
                $item = format_string($item);
            });
        }

        $default = $field->userdefault ?? null;
        $required = !empty($field->required);
        if (!empty($additionalattr)) {
            $attributes = array_merge($additionalattr, $attributes);
        }
        return new advancedupload_field($datatype, $id, $label, $params, $attributes, $default, $required);
    }

    /**
     * Retrieves the configuration for the "presentation" advanced upload tab for a given Opencast instance.
     *
     * Prepares the filepicker or chunkupload field for video upload, sets accepted file types and size limits,
     * and returns an array containing the advancedupload_field object for use in the advanced upload form.
     *
     * @param int $ocinstanceid The Opencast instance ID.
     * @return array Array of advancedupload_field objects representing the presentation upload field.
     */
    private static function get_advanced_tab_presentation_config(int $ocinstanceid): array {
        $config = [];
        $datatype = 'filepicker';
        $id = self::PRESENTATION_FILEPICKER_ID;
        $label = get_string('uploadform_presentation_upload', 'mod_opencast');
        $attributes = [];

        $acceptedtypes = ['video'];
        $videotypescfg = get_config('tool_opencast', 'uploadfileextensions_' . $ocinstanceid);
        if (!empty($videotypescfg)) {
            $acceptedtypes = [];
            foreach (explode(',', $videotypescfg) as $videotype) {
                if (empty($videotype)) {
                    continue;
                }
                $acceptedtypes[] = $videotype;
            }
        }
        $attributes['accepted_types'] = $acceptedtypes;

        $usechunkupload = class_exists('\local_chunkupload\chunkupload_form_element')
            && get_config('tool_opencast', 'enablechunkupload_' . $ocinstanceid);

        if ($usechunkupload) {
            $datatype = 'chunkupload';
            $id = self::PRESENTATION_CHUNKUPLOAD_ID;
            $uploadfsizelimit = (int) get_config('tool_opencast', 'uploadfilesizelimitmode_' . $ocinstanceid);
            $maxuploadsize = defined('USER_CAN_IGNORE_FILE_SIZE_LIMITS') ? USER_CAN_IGNORE_FILE_SIZE_LIMITS : -1; // Unlimited.
            if ($uploadfsizelimit !== 1) { // The flag for unlimited size is "1", and "0" for limited.
                $maxuploadsize = (int) get_config('tool_opencast', 'uploadfilelimit_' . $ocinstanceid);
            }
            $attributes['maxbytes'] = $maxuploadsize;
        }

        $params = [];
        $advanceduploadfield = new advancedupload_field($datatype, $id, $label, $params, $attributes);
        $config[] = $advanceduploadfield;
        return $config;
    }

    /**
     * Retrieves the configuration for the "visibility" advanced upload tab for a given Opencast instance.
     *
     * Prepares radio button fields for visibility options, sets default values, and returns an array
     * containing advancedupload_field objects for use in the advanced upload form.
     *
     * @param int $ocinstanceid The Opencast instance ID.
     * @param int $ocmoduleinstanceid The Opencast module instance ID.
     * @return array Array of advancedupload_field objects representing visibility options.
     */
    private static function get_advanced_tab_visibility_config(int $ocinstanceid, int $ocmoduleinstanceid): array {
        global $PAGE, $DB;
        $ocmoduleinstance = $DB->get_record('opencast', ['id' => $ocmoduleinstanceid], '*', MUST_EXIST);
        $simpleuploadoptions = json_decode($ocmoduleinstance->uploadoptionsjson);
        $config = [];
        $mainrenderer = $PAGE->get_renderer('tool_opencast');
        $defaultvisibility = get_config('mod_opencast', 'upload_visibility_default_' . $ocinstanceid);
        if ($defaultvisibility === null) {
            $defaultvisibility = $mainrenderer::VISIBLE;
        }
        // Here we take the final value from the simple page option.
        if (isset($simpleuploadoptions->options->{$ocinstanceid}->visibility)) {
            $defaultvisibility = $simpleuploadoptions->options->{$ocinstanceid}->visibility;
        }
        $datatype = 'radio';
        $id = self::VISIBILITY_ID;
        $label = get_string('uploadform_visibility', 'mod_opencast');
        $params = get_string('visibility_hide', 'tool_opencast');
        $attributes = 0;
        $default = $defaultvisibility;
        $config[] = new advancedupload_field($datatype, $id, $label, $params, $attributes, $default);
        $params = get_string('visibility_show', 'tool_opencast');
        $label = '';
        $attributes = 1;
        $config[] = new advancedupload_field($datatype, $id, $label, $params, $attributes, $default);
        return $config;
    }

    /**
     * Retrieves the configuration for the "processing" advanced upload tab for a given Opencast instance.
     *
     * Extracts processing elements, converts them to advancedupload_field objects, and returns them as an array
     * for use in the advanced upload form.
     *
     * @param int $ocinstanceid The Opencast instance ID.
     * @param int $ocmoduleinstanceid The Opencast module instance ID.
     * @return array Array of advancedupload_field objects representing processing options.
     */
    private static function get_advanced_tab_processing_config(int $ocinstanceid, int $ocmoduleinstanceid): array {
        global $DB;
        $ocmoduleinstance = $DB->get_record('opencast', ['id' => $ocmoduleinstanceid], '*', MUST_EXIST);
        $simpleuploadoptions = json_decode($ocmoduleinstance->uploadoptionsjson);
        $config = [];
        if ($elements = self::extract_processing_elements($ocinstanceid)) {
            foreach ($elements as $element) {
                $attr = $element->_attributes;
                $label = $element->_label;
                $datatype = $attr['type'];
                $id = $attr['name'];
                $name = str_replace(workflowconfiguration_helper::CONFIG_PANEL_ELEMENT_SUFFIX, '', $id);
                $default = $attr['value'] ?? null;
                if (isset($simpleuploadoptions->options->{$ocinstanceid}->processing->{$name})) {
                    $configuredvalue = $simpleuploadoptions->options->{$ocinstanceid}->processing->{$name};
                    if ($datatype == 'checkbox' || in_array($configuredvalue, ['true', 'false'])) {
                        $configuredvalue = $configuredvalue == 'true' ? 1 : 0;
                    }
                    $default = $configuredvalue;
                }
                $params = [];
                if ($datatype == 'checkbox') {
                    $params = ' ';
                }
                $attributes = [];
                $advanceduploadfield = new advancedupload_field($datatype, $id, $label, $params, $attributes, $default);
                $config[] = $advanceduploadfield;
            }
        }
        return $config;
    }

    /**
     * Retrieves the configuration for the "subtitle" advanced upload tab for a given Opencast instance.
     *
     * Prepares filepicker fields for subtitle file uploads in configured languages, sets accepted file types,
     * and returns an array of advancedupload_field objects for use in the advanced upload form.
     * If subtitle upload is not enabled or no languages are configured, returns an empty array.
     *
     * @param int $ocinstanceid The Opencast instance ID.
     * @return array Array of advancedupload_field objects representing subtitle upload fields.
     */
    private static function get_advanced_tab_subtitle_config(int $ocinstanceid): array {
        $config = [];

        $configuredsublist = [];
        $configuredsubstr = get_config('mod_opencast', 'upload_subtitle_langlist_' . $ocinstanceid);
        if (!empty($configuredsubstr)) {
            $configuredsublist = array_map('trim', explode(',', $configuredsubstr));
        }

        // Prepare the subtitle settings check from tool plugin.
        $sublangconfig = get_config('tool_opencast', 'transcriptionlanguages_' . $ocinstanceid);
        $subuploadenabled = (bool) get_config('tool_opencast', 'enableuploadtranscription_' . $ocinstanceid);

        // If the transcription upload is not enabled or no languages are configured, we show a disabled config with information.
        if (!$subuploadenabled || empty($sublangconfig)) {
            return [];
        }

        $subtitletypescfg = get_config('tool_opencast', 'transcriptionfileextensions_' . $ocinstanceid);
        $transcriptiontypes = ['html_track'];
        if (!empty($subtitletypescfg)) {
            $transcriptiontypes = [];
            foreach (explode(',', $subtitletypescfg) as $transcriptiontype) {
                if (empty($transcriptiontype)) {
                    continue;
                }
                $transcriptiontypes[] = $transcriptiontype;
            }
        }

        $configedlanguages = json_decode($sublangconfig);
        foreach ($configedlanguages as $lang) {
            if (empty($lang->key) || (!empty($configuredsublist) && !in_array($lang->key, $configuredsublist))) {
                continue;
            }
            $datatype = 'filepicker';
            $label = !empty($lang->value) ? format_string($lang->value, true) :
                    get_string('transcriptionfilefield', 'tool_opencast', $lang->key);
            $id = self::TRANSCRIPTION_ID_PREFIX . $lang->key;
            $params = [];
            $attributes['accepted_types'] = $transcriptiontypes;
            $filepickerlabel = ''; // We pass it as empty string, because the parent element has the label.
            $params['class'] = 'mod-opencast-subtitle-filepicker-' . $lang->key;
            $advanceduploadfield = new advancedupload_field($datatype, $id, $filepickerlabel, $params, $attributes);
            $parentdatatype = 'checkbox';
            $parentid = $id . self::TRANSCRIPTION_ENABLED_ID_SUFFIX;
            $parentlabel = $label;
            $parenttext = get_string('uploadform_subtitle_field_enabled', 'mod_opencast');
            $parentfield = new advancedupload_field($parentdatatype, $parentid, $parentlabel, $parenttext, [], false);
            $parentfield->set_subfield($advanceduploadfield);
            $config[] = $parentfield;
        }
        return $config;
    }

    /**
     * Retrieves the configuration data for the simple upload form for all available Opencast instances.
     *
     * For each Opencast instance, collects series, required metadata catalogs, visibility, processing,
     * and advanced mode block information, and returns them as part of the form data array.
     *
     * @param stdClass $moduleinstance The module instance object.
     * @param int $cmid The course module ID.
     * @return array The configuration data for the simple upload form.
     */
    public static function get_simple_upload_form_data(stdClass $moduleinstance, int $cmid): array {
        $formdata = [];
        $ocinstances = self::get_ocinstances();
        foreach ($ocinstances as $ocinstance) {
            // Prepare instance form data.
            $ocinstanceformdata = new stdClass();
            $ocinstanceformdata->ocinstanceid = $ocinstance->id;
            $ocinstanceformdata->series = self::get_course_series($ocinstance->id, $moduleinstance->course);
            $ocinstanceformdata->metadatacatalogs = self::get_metadatacatalogs($ocinstance->id, true);
            $ocinstanceformdata->visibility = self::has_simple_visibility($ocinstance->id);
            $ocinstanceformdata->processing = self::has_simple_processing($ocinstance->id);
            $ocinstanceformdata->advancedmode = self::get_simple_form_advanced_mode_block($cmid, $ocinstance->id);
            $formdata['ocinstanceformdata'][] = $ocinstanceformdata;
        }
        return $formdata;
    }

    /**
     * Checks if the simple visibility option is enabled for the given Opencast instance.
     *
     * Returns the default visibility value if inline visibility is enabled, or null otherwise.
     *
     * @param int $ocinstanceid The Opencast instance ID.
     * @return int|null The default visibility value, or null if not enabled.
     */
    public static function has_simple_visibility(int $ocinstanceid): ?int {
        global $PAGE;
        $mainrenderer = $PAGE->get_renderer('tool_opencast');
        if (!empty(get_config('mod_opencast', 'upload_inline_visibility_' . $ocinstanceid))) {
            $defaultvisibility = get_config('mod_opencast', 'upload_visibility_default_' . $ocinstanceid);
            return $defaultvisibility == $mainrenderer::VISIBLE ?: $mainrenderer::HIDDEN;
        }
        return null;
    }

    /**
     * Retrieves the inplace editable visibility data for a given Opencast instance and module instance.
     *
     * Prepares and returns a stdClass object containing the module ID, field ID, and current visibility value,
     * for use with inplace editable UI components.
     *
     * @param int $ocinstanceid The Opencast instance ID.
     * @param int $ocmoduleinstanceid The Opencast module instance ID.
     * @return stdClass The inplace editable visibility data object.
     */
    public static function get_inplace_visibility_data(int $ocinstanceid, int $ocmoduleinstanceid): stdClass {
        global $PAGE;
        $mainrenderer = $PAGE->get_renderer('tool_opencast');
        $defaultvisibility = get_config('mod_opencast', 'upload_visibility_default_' . $ocinstanceid);
        if ($defaultvisibility === null) {
            $defaultvisibility = $mainrenderer::VISIBLE;
        }
        $data = (object) [
            'moduleid' => $ocmoduleinstanceid,
            'id' => 'visibility',
            'value' => (int) $defaultvisibility,
        ];
        return $data;
    }

    /**
     * Checks if the simple processing option is enabled for the given Opencast instance.
     *
     * Returns true if inline processing is enabled and a configuration panel is available, false otherwise.
     *
     * @param int $ocinstanceid The Opencast instance ID.
     * @return bool True if simple processing is enabled, false otherwise.
     */
    public static function has_simple_processing(int $ocinstanceid): bool {
        $wfconfighelper = workflowconfiguration_helper::get_instance($ocinstanceid);
        if (!empty(get_config('mod_opencast', 'upload_inline_processing_' . $ocinstanceid)) &&
            $wfconfighelper->can_provide_configuration_panel()) {
            return true;
        }
        return false;
    }

    /**
     * Extracts processing elements for the given Opencast instance.
     *
     * Renders the workflow configuration panel form elements using a dummy form and returns
     * the extracted elements as an array. Used to build processing options for upload forms.
     *
     * @param int $ocinstanceid The Opencast instance ID.
     * @return array The extracted processing elements.
     */
    private static function extract_processing_elements(int $ocinstanceid): array {
        global $PAGE;
        $dummyform = new MoodleQuickForm('dummyform', 'POST', '#');
        $wfconfighelper = workflowconfiguration_helper::get_instance($ocinstanceid);
        $mainrenderer = $PAGE->get_renderer('tool_opencast');
        $mainrenderer->render_configuration_panel_form_elements(
            $dummyform,
            $wfconfighelper->get_upload_workflow_configuration_panel(),
            $wfconfighelper->get_allowed_upload_configurations()
        );
        return $dummyform?->_elements ?? [];
    }

    /**
     * Retrieves inplace editable processing data for a given Opencast instance and module instance.
     *
     * Extracts processing elements, filters out hidden fields, and prepares an array of stdClass objects
     * containing module ID, field ID, value, label, and type for use with inplace editable UI components.
     *
     * @param int $ocinstanceid The Opencast instance ID.
     * @param int $ocmoduleinstanceid The Opencast module instance ID.
     * @return array Array of stdClass objects representing inplace editable processing fields.
     */
    public static function get_inplace_processings_data(int $ocinstanceid, int $ocmoduleinstanceid): array {
        $inplaceelms = [];
        $elements = self::extract_processing_elements($ocinstanceid);
        if (!empty($elements)) {
            foreach ($elements as $element) {
                $attr = $element->_attributes;
                if ($attr['type'] == 'hidden') {
                    continue;
                }
                $label = $element->_label;
                $data = (object) [
                    'moduleid' => $ocmoduleinstanceid,
                    'id' => $attr['name'],
                    'value' => $attr['value'],
                    'label' => $label,
                    'type' => $attr['type'],
                    'name' => str_replace(workflowconfiguration_helper::CONFIG_PANEL_ELEMENT_SUFFIX, '', $attr['name']),
                ];
                $inplaceelms[] = $data;
            }
        }
        return $inplaceelms;
    }

    /**
     * Retrieves all available Opencast instances.
     *
     * Uses the settings API to fetch and return an array of Opencast instance objects.
     *
     * @return array Array of Opencast instance objects.
     */
    public static function get_ocinstances(): array {
        return settings_api::get_ocinstances();
    }

    /**
     * Retrieves an array of Opencast instance options for use in select elements.
     *
     * Returns an associative array where the keys are Opencast instance IDs and the values are instance names.
     *
     * @return array Array of Opencast instance options (id => name).
     */
    public static function get_ocinstances_options(): array {
        $options = [];
        foreach (self::get_ocinstances() as $ocinstance) {
            $options[$ocinstance->id] = $ocinstance->name;
        }
        return $options;
    }

    /**
     * Retrieves the list of Opencast series for a given course and Opencast instance.
     *
     * Fetches series records from the database, determines the default series, and retrieves series titles
     * using the Opencast API. Returns an array containing the default series and a list of available series.
     *
     * @param int $ocinstanceid The Opencast instance ID.
     * @param int $courseid The course ID.
     * @return array Array with 'default' series ID and 'list' of series (id => title).
     */
    public static function get_course_series(int $ocinstanceid, int $courseid): array {
        global $DB;
        $seriesrecords = $DB->get_records('tool_opencast_series',
            ['courseid' => $courseid, 'ocinstanceid' => $ocinstanceid]);

        $courseseries = [];
        if ($seriesrecords) {
            $defaultseries = array_search('1', array_column($seriesrecords, 'isdefault', 'series'));
            $courseseries['default'] = $defaultseries;
            try {
                $apibridge = apibridge::get_instance($ocinstanceid);
                $mappings = $apibridge->get_multiple_series_by_identifier($seriesrecords);
                foreach ($mappings as $series) {
                    $courseseries['list'][$series->identifier] = $series->title;
                }
            } catch (\tool_opencast\exception\opencast_api_response_exception $e) {
                \core\notification::warning($e->getMessage());
                foreach ($mappings as $series) {
                    $courseseries['list'][$series->series] = $series->series;
                }
            }
        }
        return $courseseries;
    }

    /**
     * Retrieves metadata catalogs for a given Opencast instance.
     *
     * Returns required and/or optional metadata fields based on the provided flags.
     * Filters optional fields by configuration, merges user defaults, and returns the resulting array.
     *
     * @param int $ocinstanceid The Opencast instance ID.
     * @param bool $requiredonly If true, returns only required fields.
     * @param bool $optionalonly If true, returns only optional fields.
     * @return array Array of metadata catalog field objects.
     */
    public static function get_metadatacatalogs(int $ocinstanceid, bool $requiredonly = false, bool $optionalonly = false): array {
        $configuredmdlist = [];
        $configuredmdstr = get_config('mod_opencast', 'upload_metadata_list_' . $ocinstanceid);
        if (!empty($configuredmdstr)) {
            $configuredmdlist = array_map('trim', explode(',', $configuredmdstr));
        }

        if ($optionalonly && !$requiredonly && empty($configuredmdlist)) {
            return [];
        }

        $metadatacatalogs = tool_upload_helper::get_opencast_metadata_catalog($ocinstanceid);
        $requiredmtcats = [];
        $optionalmtcats = [];
        if (!empty($metadatacatalogs)) {
            $requiredmtcats = array_filter($metadatacatalogs, function ($metadata) {
                return $metadata->required == 1;
            });
            $optionalmtcats = array_filter($metadatacatalogs, function ($metadata) {
                return $metadata->required == 0;
            });
        }

        if (!empty($configuredmdlist) && !empty($optionalmtcats)) {
            $filteredopmtcats = array_filter($optionalmtcats,
                function ($metadata) use ($configuredmdlist) {
                    return in_array($metadata->name, $configuredmdlist);
                }
            );
            $optionalmtcats = $filteredopmtcats;
        }

        if ($requiredonly) {
            $metadatacatalogs = $requiredmtcats;
        } else if ($optionalonly) {
            $metadatacatalogs = $optionalmtcats;
        } else {
            $metadatacatalogs = array_merge($optionalmtcats, $requiredmtcats);
        }

        $userdefaults = self::get_user_defaults();
        if (!empty($userdefaults) && !empty($metadatacatalogs)) {
            $filteredmtcats = [];
            foreach ($metadatacatalogs as $field) {
                if (array_key_exists($field->name, $userdefaults)) {
                    $field->userdefault = $userdefaults[$field->name];
                }
                $filteredmtcats[] = $field;
            }
            $metadatacatalogs = $filteredmtcats;
        }
        return $metadatacatalogs;
    }

    /**
     * Retrieves the user's default metadata values for a given Opencast instance.
     *
     * Fetches the user's default settings from the database and returns an array of event metadata defaults.
     *
     * @return array Array of user default metadata values.
     */
    public static function get_user_defaults(): array {
        global $DB, $USER;
        $userdefaultsrecord = $DB->get_record('tool_opencast_user_default', ['userid' => $USER->id]);
        $userdefaults = $userdefaultsrecord ? json_decode($userdefaultsrecord->defaults, true) : [];
        $usereventdefaults = (!empty($userdefaults['event'])) ? $userdefaults['event'] : [];
        return $usereventdefaults;
    }

    /**
     * Retrieves the default upload options for all available Opencast instances for a given course and title.
     *
     * Prepares default values for series, metadata, visibility, and processing options for each Opencast instance.
     * Returns the options as an array or a JSON-encoded string, depending on the $encoded parameter.
     *
     * It creates a new series if no default series mapping exists for the course and instance.
     *
     * @param string $title The title to use for the upload.
     * @param int $courseid The course ID.
     * @return string|stdClass The default upload options as an array/stdClass or JSON string.
     * @throws \tool_opencast\opencast_state_exception
     */
    public static function get_default_upload_options(string $title, int $courseid): string {
        global $USER;
        $uploadoptions = [];

        // We prepare the default option based on the opencast instances.
        $ocinstances = settings_api::get_ocinstances();

        $uploadoptions['selectedocinstanceid'] = settings_api::get_default_ocinstance()->id;

        foreach ($ocinstances as $ocinstance) {
            $uploadoption = new stdClass();
            $uploadoption->ocinstanceid = $ocinstance->id;
            $uploadoption->isdefault = $ocinstance->isdefault ? true : false;

            // Series.
            $defaultseriesid = null;
            $seriesmapping = seriesmapping::get_record([
                'courseid' => $courseid,
                'ocinstanceid' => $ocinstance->id,
                'isdefault' => 1,
            ]);
            // We have to create a new series for this instance in this course, when it does not exist yet.
            if (empty($seriesmapping)) {
                $apibridge = apibridge::get_instance($ocinstance->id);
                $series = $apibridge->ensure_course_series_exists($courseid, $USER->id);
                $defaultseriesid = $series->identifier;
            } else {
                $defaultseriesid = $seriesmapping->get('series');
            }

            $uploadoption->seriesid = $defaultseriesid;

            // Metadata.
            $metadata = [];
            $metadatacatalogs = self::get_metadatacatalogs($ocinstance->id, true);
            foreach ($metadatacatalogs as $field) {
                $id = $field->name;
                $value = !empty($field->userdefault) ? $field->userdefault : null;
                if ($field->name == 'title') {
                    $value = $title;
                }
                $metadata[$id] = [
                    'id' => $id,
                    'value' => $value,
                ];
            }
            $uploadoption->metadata = $metadata;

            // Visiblity.

            $uploadoption->visibility = get_config('mod_opencast', 'upload_visibility_default_' . $ocinstance->id);

            // Processing.
            if (self::has_simple_processing($ocinstance->id)) {
                $elements = self::extract_processing_elements($ocinstance->id);
                if (!empty($elements)) {
                    $processing = [];
                    foreach ($elements as $element) {
                        $attr = $element->_attributes;
                        if ($attr['type'] == 'hidden') {
                            continue;
                        }
                        $parts = explode('_', $attr['name']);
                        $id = reset($parts);
                        $value = $attr['value'];
                        if ($attr['type'] == 'checkbox') {
                            $value = $value ? 'true' : 'false';
                        }
                        $processing[$id] = $value;
                    }
                    $uploadoption->processing = $processing;
                }
            }
            $uploadoptions['options']["{$ocinstance->id}"] = $uploadoption;
        }

        return json_encode($uploadoptions);
    }

    /**
     * Retrieves the advanced mode block configuration for the simple upload form.
     *
     * If advanced mode is enabled for the given Opencast instance, prepares and returns a stdClass object
     * containing the label and HTML for the advanced mode link block. Returns null if advanced mode is not enabled.
     *
     * @param int $cmid The course module ID.
     * @param int $ocinstanceid The Opencast instance ID.
     * @return stdClass|null The advanced mode block configuration, or null if not enabled.
     */
    public static function get_simple_form_advanced_mode_block(int $cmid, int $ocinstanceid): ?stdClass {
        global $OUTPUT;

        // Make sure all advanced tabs are enabled.
        $hasadvancedtab = false;
        foreach (\mod_opencast\settings\upload_settings_helper::ADVANCED_TAB_ORDERS as $tabname) {
            if (self::advanced_tab_enabled($ocinstanceid, $tabname)) {
                $hasadvancedtab = true;
                break;
            }
        }
        if (!$hasadvancedtab) {
            return null;
        }

        // Prepare advanced mode block if advanced mode is enabled in settings.
        $advancedmodeurl = new moodle_url('/mod/opencast/uploadvideoadvanced.php',
            ['cmid' => $cmid, 'ocinstanceid' => $ocinstanceid]);
        if (!empty(get_config('mod_opencast', 'upload_enable_advanced_mode_' . $ocinstanceid))) {
            $linkattr['href'] = $advancedmodeurl->out(false);
            $advancedmodelink = \html_writer::start_tag('a', $linkattr);
            $advancedmodelink .= \html_writer::tag('span',
                get_string('uploadform_advanced_mode_link_text', 'mod_opencast'), ['class' => 'mr-1']);
            $advancedmodelink .= $OUTPUT->pix_icon('i/share', get_string('uploadform_advanced_mode_title', 'mod_opencast'));
            $advancedmodelink .= \html_writer::end_tag('a');
            return (object) [
                'label' => \html_writer::tag('h5', get_string('uploadform_advanced_mode_title', 'mod_opencast')),
                'html' => \html_writer::tag('p', get_string('uploadform_advanced_mode_desc', 'mod_opencast', $advancedmodelink)),
            ];
        }
        return null;
    }

    /**
     * Renders an inplace editable object using the core template.
     *
     * Uses the global $OUTPUT renderer to render the provided inplace_editable object
     * with the 'core/inplace_editable' template and returns the resulting HTML string.
     *
     * @param \core\output\inplace_editable $object The inplace editable object to render.
     * @return string The rendered HTML output.
     */
    public static function render_inplace_editable_object(\core\output\inplace_editable $object): string {
        global $OUTPUT;
        return $OUTPUT->render_from_template('core/inplace_editable', $object->export_for_template($OUTPUT));
    }
}
