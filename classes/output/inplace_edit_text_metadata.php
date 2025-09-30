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
 * Inplace editable text metadata class for Opencast Activity.
 *
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_opencast\output;

/**
 * Inplace editable text metadata class for Opencast Activity.
 *
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inplace_edit_text_metadata extends \core\output\inplace_editable {
    /**
     * Constructor.
     *
     * @param object $data
     */
    public function __construct($data) {
        $itemid = $data->id . '_' . $data->moduleid;
        parent::__construct(
            component: 'mod_opencast',
            itemtype: 'inplace_edit_text_metadata',
            itemid: $itemid,
            editable: has_capability(
                'tool/opencast:addvideo',
                \context_system::instance(),
            ),
            displayvalue: format_string($data->value),
            value: $data->value,
            edithint: get_string('uploadform_inplace_edit_text_hint', 'mod_opencast'),
            editlabel: get_string('uploadform_inplace_edit_text_label', 'mod_opencast')
        );
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
        $newvalue = clean_param($newvalue, PARAM_TEXT);
        list($id, $moduleid) = explode('_', $itemid, 2);

        $ocmoduleinstance = $DB->get_record('opencast', ['id' => $moduleid]);

        $uploadoptionsjson = json_decode($ocmoduleinstance->uploadoptionsjson);

        if (!isset($uploadoptionsjson->options->{$uploadoptionsjson->selectedocinstanceid}->metadata)) {
            $uploadoptionsjson->options->{$uploadoptionsjson->selectedocinstanceid}->metadata = new \stdClass();
        }
        $uploadoptionsjson->options->{$uploadoptionsjson->selectedocinstanceid}->metadata->{$id} = (object) [
            'id' => $id,
            'value' => $newvalue,
        ];

        $ocmoduleinstance->uploadoptionsjson = json_encode($uploadoptionsjson);

        // For title, we also change the name of the module instance.
        if ($id === 'title') {
            $ocmoduleinstance->name = get_string('uploadtitledisplay', 'mod_opencast') . $newvalue;
        }

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
