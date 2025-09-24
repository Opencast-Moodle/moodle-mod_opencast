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
 * Advanced Video Upload form.
 *
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

use block_opencast\local\workflowconfiguration_helper;
use mod_opencast\local\advancedupload_field;
use mod_opencast\local\upload_helper as mod_upload_helper;
use html_writer;

/**
 * Advanced Video Upload form.
 *
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_opencast_uploadvideoadvanced_form extends moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $PAGE;
        // Get the renderer to use its methods.
        $renderer = $PAGE->get_renderer('mod_opencast');

        $cmid = $this->_customdata['cmid'];
        $ocmoduleinstance = $this->_customdata['moduleinstance'];
        $uploadoptions = json_decode($ocmoduleinstance->uploadoptionsjson);
        $selectedocinstanceid = (int) $uploadoptions->selectedocinstanceid;
        $tabs = $this->_customdata['tabs'];

        $mform = $this->_form;

        $renderer->render_advanced_mode_header($mform, $cmid);

        // First, we render tabs navigation.
        $renderer->render_advanced_upload_form_tabs_navigation($mform, array_keys($tabs));

        // Secondly, we start the tab content.
        $renderer->tab_content_start($mform, true);

        // Then we generate the tabs.
        foreach ($tabs as $tabid => $tabcontent) {
            // Make sure the first tab is marked activated.
            $renderer->tab_pane_start($mform, $tabid, ($tabindex == 0), true);

            // Here we look for the values already set in the uploadoptionsjson.

            foreach ($tabcontent as $field) {
                // Add default title to the metadata field when it is set.
                if ($field->get_id() === mod_upload_helper::METADATA_ID_PREFIX . 'title') {
                    $modulename = str_replace(get_string('uploadtitledisplay', 'mod_opencast'), '', $ocmoduleinstance->name);
                    $field->set_default(trim($modulename));
                }
                $renderer->render_advanced_upload_form_tab_field($mform, $field);
            }

            $renderer->tab_pane_end($mform);
            $tabindex++;
        }

        // Finally, we close the tab content.
        $renderer->tab_content_end($mform);

        $mform->addElement('hidden', 'ocinstanceid', $selectedocinstanceid);
        $mform->setType('ocinstanceid', PARAM_INT);

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);

        $mform->closeHeaderBefore('buttonar');
        $this->add_action_buttons(true, get_string('uploadform_submit', 'mod_opencast'));
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
        // Return errors.
        return $errors;
    }
}
