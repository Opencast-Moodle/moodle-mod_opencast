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
 * Video Upload form
 *
 * @package    mod_opencast
 * @copyright  2023 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

use \block_opencast\local\autocomplete_suggestion_helper;

/**
 * Video Upload form
 *
 * @package    mod_opencast
 * @copyright  2023 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_opencast_uploadvideo_form extends moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $PAGE, $OUTPUT;
        // Get the renderer to use its methods.
        $renderer = $PAGE->get_renderer('block_opencast');

        $cmid = $this->_customdata['cmid'];
        $ocinstances = $this->_customdata['ocinstances'];
        $allseries = $this->_customdata['allseries'];
        $metadatacatalogs = $this->_customdata['metadatacatalogs'];
        $eventdefaults = $this->_customdata['eventdefaults'];

        $mform = $this->_form;

        $explanation = \html_writer::tag('p', get_string('uploadform_uploadexplaination', 'mod_opencast'));
        $mform->addElement('html', $explanation);

        $requirednotice = \html_writer::tag('p',
            get_string('uploadform_requirednotice', 'mod_opencast', $OUTPUT->pix_icon('req', '')));
        $mform->addElement('html', $requirednotice);

        $mform->addElement('header', 'uploadform_general_header', get_string('uploadform_general_header', 'mod_opencast'));
        $mform->setExpanded('uploadform_general_header', true);

        // Select ocinstance first as it is important.
        $ocinstancesselection = [];
        $defaultocinstance = null;
        foreach ($ocinstances as $ocinstance) {
            $ocinstancesselection[$ocinstance->id] = $ocinstance->name;
            if (empty($defaultocinstance) && $ocinstance->isdefault == 1) {
                $defaultocinstance = $ocinstance->id;
            }
        }
        $lbltext = get_string('uploadform_ocinstancesselect', 'mod_opencast');
        $mform->addElement('select', 'ocinstance', $lbltext, $ocinstancesselection);
        $mform->setDefault('ocinstance', $defaultocinstance);

        // Select the sereis of that ocinstances.
        foreach ($ocinstances as $ocinstance) {
            $serieslist = $allseries[$ocinstance->id];
            $defaultseries = 0;
            $seriesselection = [];
            foreach ($serieslist as $series) {
                $seriesselection[$series->id] = $series->name;
                if (empty($defaultseries) && $series->isdefault == 1) {
                    $defaultseries = $series->id;
                }
            }
            $elementid = "series_{$ocinstance->id}";
            $lbltext = get_string('uploadform_seriessselect', 'mod_opencast');
            $mform->addElement('select', $elementid, $lbltext, $seriesselection);
            $mform->setDefault($elementid, $defaultseries);
            $mform->disabledIf($elementid, 'ocinstance' , 'neq', $ocinstance->id);
            $mform->hideif($elementid, 'ocinstance' , 'neq', $ocinstance->id);
        }

        $mform->closeHeaderBefore('uploadform_metadata_header');
        $mform->addElement('header', 'uploadform_metadata_header', get_string('uploadform_metadata_header', 'mod_opencast'));
        $mform->setExpanded('uploadform_metadata_header', true);

        // Providing sets od metadata based on ocinstances.
        foreach ($ocinstances as $ocinstance) {
            $metadatacatalog = $metadatacatalogs[$ocinstance->id];
            $settitle = true;
            foreach ($metadatacatalog as $field) {
                $elementid = "{$field->name}_{$ocinstance->id}";
                $param = array();
                $attributes = array();
                if ($field->name == 'title') {
                    if ($field->required) {
                        $settitle = false;
                    } else {
                        continue;
                    }
                }
                if ($field->param_json) {
                    $param = $field->datatype == 'static' ? $field->param_json : json_decode($field->param_json, true);
                }
                if ($field->datatype == 'autocomplete') {
                    $attributes = [
                        'multiple' => true,
                        'placeholder' => get_string('metadata_autocomplete_placeholder', 'block_opencast',
                            $this->try_get_string($field->name, 'block_opencast')),
                        'showsuggestions' => true,
                        'noselectionstring' => get_string('metadata_autocomplete_noselectionstring', 'block_opencast',
                            $this->try_get_string($field->name, 'block_opencast')),
                        'tags' => true
                    ];

                    if ($field->name == 'creator' || $field->name == 'contributor') {
                        $param = array_merge($param,
                            autocomplete_suggestion_helper::get_suggestions_for_creator_and_contributor($ocinstance->id));
                    }
                }

                // Apply format_string to each value of select option, to use Multi-Language filters (if any).
                if ($field->datatype == 'select') {
                    array_walk($param, function (&$item) {
                        $item = format_string($item);
                    });
                }

                // Get the created element back from addElement function, in order to further use its attrs.
                $lbltext = $this->try_get_string($field->name, 'block_opencast');
                $element = $mform->addElement($field->datatype, $elementid, $lbltext, $param, $attributes);

                // Check if the description is set for the field, to display it as help icon.
                if (isset($field->description) && !empty($field->description)) {
                    // Use the renderer to generate a help icon with custom text.
                    $element->_helpbutton = $renderer->render_help_icon_with_custom_text(
                        $this->try_get_string($field->name, 'block_opencast'), $field->description);
                }

                if ($field->datatype == 'text') {
                    $mform->setType($elementid, PARAM_TEXT);
                }

                $mform->setAdvanced($elementid, !$field->required);
                $default = (isset($eventdefaults[$field->name]) ? $eventdefaults[$field->name] : null);
                if ($default) {
                    $mform->setDefault($elementid, $default);
                }
                $mform->disabledIf($elementid, 'ocinstance' , 'neq', $ocinstance->id);
                $mform->hideif($elementid, 'ocinstance' , 'neq', $ocinstance->id);
            }
            if ($settitle) {
                $elementid = 'title_' . $ocinstance->id;
                $mform->addElement('text', 'title_' . $elementid, get_string('title', 'block_opencast'));
                $mform->setType($elementid, PARAM_TEXT);
                $mform->disabledIf($elementid, 'ocinstance' , 'neq', $ocinstance->id);
                $mform->hideif($elementid, 'ocinstance' , 'neq', $ocinstance->id);
            }

            // Radio boxes for video flavor.
            $elementid = 'flavor_' . $ocinstance->id;
            $lbltext = get_string('uploadform_flavor_label', 'mod_opencast');
            $radioarray = array();
            $radioarray[] = $mform->addElement('radio', $elementid, $lbltext,
                get_string('uploadform_flavor_presenter', 'mod_opencast'), 0);
            $radioarray[] = $mform->addElement('radio', $elementid, '',
                get_string('uploadform_flavor_presentation', 'mod_opencast'), 1);
            $mform->disabledIf($elementid, 'ocinstance' , 'neq', $ocinstance->id);
            $mform->hideif($elementid, 'ocinstance' , 'neq', $ocinstance->id);
        }

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'metadatacatalogs', json_encode($metadatacatalogs));
        $mform->setType('metadatacatalogs', PARAM_TEXT);

        $mform->closeHeaderBefore('buttonar');
        $this->add_action_buttons(true, get_string('addvideo', 'block_opencast'));
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
        // Ask parent class for errors first.
        $errors = parent::validation($data, $files);

        $ocinstance = intval($data['ocinstance']);
        $metadatacatalogs = json_decode($data['metadatacatalogs'], true);
        $fields = array_column($metadatacatalogs[$ocinstance], 'name');
        $fields = array_map(function ($fieldname) use ($ocinstance) {
            return $fieldname . '_' . $ocinstance;
        }, $fields);
        $fields[] = 'series_' . $ocinstance;

        foreach ($fields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = get_string('required');
            }
        }

        // Return errors.
        return $errors;
    }
}
