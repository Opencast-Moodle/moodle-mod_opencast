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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * The main mod_opencast configuration form.
 *
 * @package     mod_opencast
 * @copyright   2020 Tobias Reischmann <tobias.reischmann@wi.uni-muenster.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_opencast\local\apibridge;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package    mod_opencast
 * @copyright  2020 Tobias Reischmann <tobias.reischmann@wi.uni-muenster.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_opencast_mod_form extends moodleform_mod
{

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        if (property_exists($this->current, 'ocinstanceid')) {
            $ocinstanceid = $this->current->ocinstanceid;
        } else {
            $ocinstanceid = \tool_opencast\local\settings_api::get_default_ocinstance()->id;
        }

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('modulename', 'mod_opencast'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $this->standard_intro_elements();

        $mform->addElement('text', 'opencastid', get_string('opencastid', 'mod_opencast'),
            array('size' => 64));
        $mform->setType('opencastid', PARAM_ALPHANUMEXT);
        $mform->addRule('opencastid', get_string('required'), 'required');

        if (get_config('mod_opencast', 'global_download_' . $ocinstanceid)) {
            $mform->addElement('hidden', 'allowdownload');
            $mform->setType('allowdownload', PARAM_INT);
            $mform->setDefault('allowdownload', '1');
        } else {
            $mform->addElement('advcheckbox', 'allowdownload', get_string('allowdownload', 'mod_opencast'));
            $mform->setType('allowdownload', PARAM_INT);
            $mform->setDefault('allowdownload', get_config('mod_opencast', 'download_default_' . $ocinstanceid));
        }

        $mform->addElement('hidden', 'ocinstanceid');
        $mform->setType('ocinstanceid', PARAM_INT);
        $mform->setDefault('ocinstanceid', \tool_opencast\local\settings_api::get_default_ocinstance()->id);

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_INT);

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Validates the form.
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!array_key_exists('opencastid', $errors)) {
            $api = apibridge::get_instance($data['ocinstanceid']);
            $type = $api->find_opencast_type_for_id($data['opencastid']);
            if ($type === \mod_opencast\local\opencasttype::UNDEFINED) {
                $errors['opencastid'] = get_string('opencastidnotrecognized', 'mod_opencast');
            } else {
                $this->_form->setConstant('type', $type);
            }
        }
        return $errors;
    }
}
