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
 * Inplace editable select metadata class for Opencast Activity.
 *
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_opencast\output;

use mod_opencast\local\upload_helper;

/**
 * Inplace editable select metadata class for Opencast Activity.
 *
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inplace_edit_select_metadata extends \core\output\inplace_editable {
    /**
     * Constructor.
     *
     * @param object $data
     */
    public function __construct($data) {
        $options = [];
        $metadatacatalogs = upload_helper::get_metadatacatalogs($data->ocinstanceid, true);
        $name = $data->id;
        $itemid = $name . '_' . $data->moduleid;
        $metadatacatalogobj = array_filter($metadatacatalogs, function ($catalog) use ($name) {
            return $catalog->name === $name;
        });
        if (!empty($metadatacatalogobj)) {
            $metadatacatalog = reset(array_values($metadatacatalogobj));
            $options = !empty($metadatacatalog->param_json) ? json_decode($metadatacatalog->param_json, true) : [];
        }
        $displayvalue = null;
        if (!empty($options) && isset($options[$data->value])) {
            $displayvalue = format_string($options[$data->value]);
        }
        parent::__construct(
            component: 'mod_opencast',
            itemtype: 'inplace_edit_select_metadata',
            itemid: $itemid,
            editable: has_capability(
                'block/opencast:addvideo',
                \context_system::instance(),
            ),
            displayvalue: $displayvalue,
            value: $data->value,
            edithint: get_string('uploadform_inplace_edit_select_hint', 'mod_opencast'),
            editlabel: get_string('uploadform_inplace_edit_select_label', 'mod_opencast')
        );
        $this->set_type_select($options);
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
