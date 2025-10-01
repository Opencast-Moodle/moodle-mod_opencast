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
 * Simple Video Upload form.
 *
 * @package    mod_opencast
 * @copyright  2023 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

use mod_opencast\local\upload_helper as mod_upload_helper;
use mod_opencast\output\inplace_edit_text_metadata;
use mod_opencast\output\inplace_edit_select_metadata;
use mod_opencast\output\inplace_edit_autocomplete_metadata;
use mod_opencast\output\inplace_edit_checkbox_metadata;
use mod_opencast\output\inplace_edit_toggle_visibility;
use mod_opencast\output\inplace_edit_series_select;
use mod_opencast\output\inplace_edit_ocinstance_select;
use mod_opencast\output\inplace_edit_text_processing;
use mod_opencast\output\inplace_edit_checkbox_processing;

/**
 * Simple Video Upload form.
 *
 * @package    mod_opencast
 * @copyright  2023 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_opencast_uploadvideo_form extends moodleform {

    /** @var array Required metadata fields to verify */
    private $requiredmetadatafields = [];
    /**
     * Defines forms elements
     */
    public function definition() {
        $cmid = $this->_customdata['cmid'];
        $ocmoduleinstance = $this->_customdata['moduleinstance'];
        $uploadoptions = json_decode($ocmoduleinstance->uploadoptionsjson);
        $simpleuploaddata = mod_upload_helper::get_simple_upload_form_data($ocmoduleinstance, $cmid);
        $ocinstanceformdata = $simpleuploaddata['ocinstanceformdata'];
        $defaultocinstance = (int) $uploadoptions->selectedocinstanceid;
        $ocinstancesoptions = $uploadoptions->options;

        $mform = $this->_form;

        $explanation = \html_writer::tag('p', get_string('uploadform_simple_uploadexplaination', 'mod_opencast'));
        $mform->addElement('html', $explanation);

        $mform->addElement('hidden', 'ocinstance', $defaultocinstance);
        $mform->setType('ocinstance', PARAM_INT);

        if (count($ocinstanceformdata) > 1) {
            $lbltext = get_string('uploadform_ocinstancesselect', 'mod_opencast');
            $data = (object) [
                'moduleid' => $ocmoduleinstance->id,
                'id' => 'ocinstance',
                'value' => $defaultocinstance,
            ];
            $inplaceobj = new inplace_edit_ocinstance_select($data);
            $inplaceobjhtml = mod_upload_helper::render_inplace_editable_object($inplaceobj);
            $mform->addElement(
                'static',
                'ocinstanceselect',
                $lbltext,
                $inplaceobjhtml
            );
        }

        foreach ($ocinstanceformdata as $formdata) {
            // Series.
            $defaultseriesid = $ocinstancesoptions?->{$formdata->ocinstanceid}?->seriesid ?? $formdata->series['default'];
            $elementid = "series_{$formdata->ocinstanceid}";
            if (!empty($formdata->series['list']) && count($formdata->series['list']) > 1) {
                $lbltext = get_string('uploadform_seriessselect', 'mod_opencast');
                $data = (object) [
                    'moduleid' => $ocmoduleinstance->id,
                    'ocinstanceid' => $formdata->ocinstanceid,
                    'courseid' => $ocmoduleinstance->course,
                    'id' => 'series',
                    'value' => $defaultseriesid,
                ];
                $inplaceobj = new inplace_edit_series_select($data);
                $inplaceobjhtml = mod_upload_helper::render_inplace_editable_object($inplaceobj);
                $mform->addElement(
                    'static',
                    $elementid,
                    $lbltext,
                    $inplaceobjhtml
                );
                $this->set_element_toggles($mform, $elementid, $formdata->ocinstanceid);
            }

            // Metadata.
            if (!empty($formdata->metadatacatalogs)) {
                foreach ($formdata->metadatacatalogs as $field) {
                    $elementid = "{$field->name}_{$formdata->ocinstanceid}";
                    $value = (isset($field->userdefault) ? $field->userdefault : null);
                    $lbltext = $this->try_get_string($field->name, 'block_opencast');

                    if (!empty($ocinstancesoptions?->{$formdata->ocinstanceid}?->metadata?->{$field->name}->value)) {
                        $value = $ocinstancesoptions->{$formdata->ocinstanceid}->metadata->{$field->name}->value;
                    }

                    // Take care of title.
                    if ($field->name == 'title' && empty($value)) {
                        $value = str_replace(get_string('uploadtitledisplay', 'mod_opencast'), '', $ocmoduleinstance->name);
                    }

                    $data = (object) [
                        'moduleid' => $ocmoduleinstance->id,
                        'ocinstanceid' => $formdata->ocinstanceid,
                        'id' => $field->name,
                        'value' => $value,
                    ];

                    $inplaceobjhtml = null;
                    switch ($field->datatype) {
                        case 'checkbox':
                            $inplaceobj = new inplace_edit_checkbox_metadata($data);
                            $inplaceobjhtml = mod_upload_helper::render_inplace_editable_object($inplaceobj);
                            break;
                        case 'select':
                            $inplaceobj = new inplace_edit_select_metadata($data);
                            $inplaceobjhtml = mod_upload_helper::render_inplace_editable_object($inplaceobj);
                            break;
                        case 'autocomplete':
                            $inplaceobj = new inplace_edit_autocomplete_metadata($data);
                            $inplaceobjhtml = mod_upload_helper::render_inplace_editable_object($inplaceobj);
                            break;
                        case 'text':
                        default:
                            $inplaceobj = new inplace_edit_text_metadata($data);
                            $inplaceobjhtml = mod_upload_helper::render_inplace_editable_object($inplaceobj);
                            break;
                    }

                    if (!empty($inplaceobjhtml)) {
                        $mform->addElement(
                            'static',
                            $elementid,
                            $lbltext,
                            $inplaceobjhtml
                        );
                        $this->set_element_toggles($mform, $elementid, $formdata->ocinstanceid);
                    }

                    // We record the required metadata fields to verify them later on.
                    // In Simple Upload Page, only required metadata fields are shown, but we check nontheless.
                    if ($field->required) {
                        $this->requiredmetadatafields[$formdata->ocinstanceid][] = $field->name;
                    }
                }
            }

            // Visibility.
            // Since we are using null, true and false. if not null means the feature is configured to be displyed here.
            if (!is_null($formdata->visibility)) {
                $elementid = "visibility_{$formdata->ocinstanceid}";
                $lbltext = $this->try_get_string('uploadform_visibility', 'mod_opencast');
                $inplvisibilitydata = mod_upload_helper::get_inplace_visibility_data(
                    $formdata->ocinstanceid,
                    $ocmoduleinstance->id
                );
                if (isset($ocinstancesoptions?->{$formdata->ocinstanceid}?->visibility)) {
                    $inplvisibilitydata->value = (int) $ocinstancesoptions->{$formdata->ocinstanceid}->visibility;
                }
                $inplaceobj = new inplace_edit_toggle_visibility($inplvisibilitydata);
                $inplaceobjhtml = mod_upload_helper::render_inplace_editable_object($inplaceobj);
                $mform->addElement(
                    'static',
                    $elementid,
                    $lbltext,
                    $inplaceobjhtml
                );
                $this->set_element_toggles($mform, $elementid, $formdata->ocinstanceid);
            }

            // Processing.
            // Regarding processing in simple upload form, we only support checkbox and text types.
            if ($formdata->processing) {
                $inplprocessingdata = mod_upload_helper::get_inplace_processings_data(
                    $formdata->ocinstanceid,
                    $ocmoduleinstance->id
                );
                $processingoptions = $ocinstancesoptions?->{$formdata->ocinstanceid}?->processing ?? new stdClass();
                if (!empty($inplprocessingdata)) {
                    foreach ($inplprocessingdata as $elmdata) {
                        if (isset($processingoptions->{$elmdata->name})) {
                            $value = $processingoptions->{$elmdata->name};
                            if ($elmdata->type == 'checkbox' || in_array($value, ['true', 'false'])) {
                                $value = $value == 'true' ? 1 : 0;
                            }
                            $elmdata->value = $value;
                        }
                        $elementid = "processing_{$elmdata->id}_{$formdata->ocinstanceid}";
                        $lbltext = format_string($elmdata->label);
                        $inplaceobj = null;
                        switch ($elmdata->type) {
                            case 'checkbox':
                                $inplaceobj = new inplace_edit_checkbox_processing($elmdata);
                                break;
                            case 'text':
                                $inplaceobj = new inplace_edit_text_processing($elmdata);
                                break;
                            case 'hidden':
                            default:
                                break;
                        }
                        if (!empty($inplaceobj)) {
                            $inplaceobjhtml = mod_upload_helper::render_inplace_editable_object($inplaceobj);
                            $mform->addElement(
                                'static',
                                $elementid,
                                $lbltext,
                                $inplaceobjhtml
                            );
                            $this->set_element_toggles($mform, $elementid, $formdata->ocinstanceid);
                        }
                    }
                }
            }

            // Advanced mode.
            if (!empty($formdata->advancedmode)) {
                $elementid = 'advancedmode_block_' . $formdata->ocinstanceid;
                $mform->addElement('static', $elementid, $formdata->advancedmode->label, $formdata->advancedmode->html);
                $this->set_element_toggles($mform, $elementid, $formdata->ocinstanceid);
            }
        }

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);

        $mform->closeHeaderBefore('buttonar');
        $this->add_action_buttons(true, get_string('uploadform_submit', 'mod_opencast'));
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
     * @throws \coding_exception
     */
    protected function try_get_string($identifier, $component = '', $a = null) {
        if (!get_string_manager()->string_exists($identifier, $component)) {
            return ucfirst($identifier);
        } else {
            return get_string($identifier, $component, $a);
        }
    }

    /**
     * Form validation.
     * @param array $data Form data
     * @param array $files Form files
     * @return array Validation results
     */
    public function validation($data, $files) {
        global $DB;
        // Ask parent class for errors first.
        $errors = parent::validation($data, $files);

        $ocinstanceid = $data['ocinstance'];
        $cmid = $data['cmid'];

        // Now we get the moulde instance from the database, to read the inplace values.
        $cm = get_coursemodule_from_id('opencast', $cmid, 0, false, MUST_EXIST);
        $moduleinstance = $DB->get_record('opencast', ['id' => $cm->instance], '*', MUST_EXIST);
        $alluploadoptions = json_decode($moduleinstance->uploadoptionsjson);

        if ((int) $alluploadoptions->selectedocinstanceid !== (int) $ocinstanceid) {
            $errors['ocinstanceselect'] = get_string('uploadmismatchedocinstanceids', 'mod_opencast');
        }

        $recordedmetadata = $alluploadoptions->options->{$ocinstanceid}->metadata ?? [];
        if (!empty($this->requiredmetadatafields)  && isset($this->requiredmetadatafields[$ocinstanceid]) &&
            !empty($recordedmetadata)) {
            foreach ($this->requiredmetadatafields[$ocinstanceid] as $fieldname) {
                if (!isset($recordedmetadata->{$fieldname}) || empty($recordedmetadata->{$fieldname}->value)) {
                    $elementid = "{$fieldname}_{$ocinstanceid}";
                    $errors[$elementid] = get_string('requiredelement', 'form');
                }
            }
        }

        // Return errors.
        return $errors;
    }

    /**
     * Sets conditional visibility and enabled state for a form element based on the selected Opencast instance.
     *
     * Hides and disables the specified form element unless the 'ocinstance' field matches the given instance ID.
     *
     * @param MoodleQuickForm $mform The Moodle form object.
     * @param string $elementid The element ID to toggle.
     * @param int $ocinstanceid The Opencast instance ID to match.
     * @return void
     */
    private function set_element_toggles(&$mform, $elementid, $ocinstanceid): void {
        $mform->hideif($elementid, 'ocinstance', 'neq', $ocinstanceid);
        $mform->disabledIf($elementid, 'ocinstance', 'neq', $ocinstanceid);
    }
}
