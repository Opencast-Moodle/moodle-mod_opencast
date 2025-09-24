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
 * Inplace editable toggle visibility class for Opencast Activity.
 *
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_opencast\output;

/**
 * Inplace editable toggle visibility class for Opencast Activity.
 *
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inplace_edit_toggle_visibility extends \core\output\inplace_editable {
    /**
     * Constructor.
     *
     * @param object $data
     */
    public function __construct($data) {
        $itemid = $data->id . '_' . $data->moduleid;
        parent::__construct(
            component: 'mod_opencast',
            itemtype: 'inplace_edit_toggle_visibility',
            itemid: $itemid,
            editable: has_capability(
                'block/opencast:addvideo',
                \context_system::instance(),
            ),
            displayvalue: format_string($data->value),
            value: $data->value,
            edithint: get_string('uploadform_inplace_edit_visibility_hint', 'mod_opencast'),
            editlabel: get_string('uploadform_inplace_edit_visibility_label', 'mod_opencast'),
        );
        $this->set_type_toggle();
    }


    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output) {
        if ($this->value) {
            $this->edithint = get_string('uploadform_inplace_edit_visibility_hidden', 'mod_opencast');
            $this->displayvalue = $output->pix_icon('i/hide', $this->edithint, '', ['class' => 'text-primary']);
        } else {
            $this->edithint = get_string('uploadform_inplace_edit_visibility_visible', 'mod_opencast');
            $this->displayvalue = $output->pix_icon('i/show', $this->edithint, '', ['class' => 'text-primary']);
        }

        return parent::export_for_template($output);
    }

    /**
     * Updates the value in database and returns itself.
     *
     * Called from inplace_editable callback
     *
     * @param int $itemid
     * @param mixed $newvalue
     * @return \self
     */
    public static function update($itemid, $newvalue) {
        global $DB;
        // Clean the new value.
        $newvalue = clean_param($newvalue, PARAM_INT);
        list($id, $moduleid) = explode('_', $itemid, 2);

        $ocmoduleinstance = $DB->get_record('opencast', ['id' => $moduleid]);

        $uploadoptionsjson = json_decode($ocmoduleinstance->uploadoptionsjson);

        $uploadoptionsjson->options->{$uploadoptionsjson->selectedocinstanceid}->visibility = $newvalue;

        $ocmoduleinstance->uploadoptionsjson = json_encode($uploadoptionsjson);

        $DB->update_record('opencast', $ocmoduleinstance);

        // Finally return itself.
        $data = (object) [
            'moduleid' => $ocmoduleinstance->id,
            'ocinstanceid' => $uploadoptionsjson->selectedocinstanceid,
            'id' => $id,
            'value' => $newvalue,
        ];

        return new self($data);
    }
}
