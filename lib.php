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
 * Library of interface functions and constants.
 *
 * @package     mod_opencast
 * @copyright   2020 Tobias Reischmann <tobias.reischmann@wi.uni-muenster.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return mixed True if the feature is supported, null otherwise.
 */
function opencast_supports($feature) {
    global $CFG;

    if ($CFG->branch >= 400) {
        if ($feature == FEATURE_MOD_PURPOSE) {
            return MOD_PURPOSE_CONTENT;
        }
    }
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_opencast into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_opencast_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function opencast_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('opencast', $moduleinstance);

    \core_completion\api::update_completion_date_event($moduleinstance->coursemodule, 'opencast', $id,
            $moduleinstance->completionexpected ?? null);

    return $id;
}

/**
 * Updates an instance of the mod_opencast in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_opencast_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function opencast_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    if (!property_exists($moduleinstance, 'id') && property_exists($moduleinstance, 'instance')) {
        $moduleinstance->id = $moduleinstance->instance;
    }

    \core_completion\api::update_completion_date_event($moduleinstance->coursemodule, 'opencast', $moduleinstance->id,
            $moduleinstance->completionexpected ?? null);

    return $DB->update_record('opencast', $moduleinstance);
}

/**
 * Removes an instance of the mod_opencast from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function opencast_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('opencast', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $cm = get_coursemodule_from_instance('opencast', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'opencast', $id, null);

    $DB->delete_records('opencast', array('id' => $id));

    return true;
}

/**
 * Get icon mapping for font-awesome.
 */
function mod_opencast_get_fontawesome_icon_map() {
    return [
        'mod_opencast:i/grid' => 'fa-th-large',
        'mod_opencast:i/list' => 'fa-list-ul',
        'mod_opencast:i/tv' => 'fa-tv'
    ];
}

/**
 * Register the ability to handle drag and drop file uploads
 * @return array containing details of the files / types the mod can handle
 */
function opencast_dndupload_register() {
    // Getting default opencast instance.
    $defaultocinstanceid = \tool_opencast\local\settings_api::get_default_ocinstance()->id;
    // Getting file extensions from the block_opencast configuration using default ocinstanceid.
    $videotypescfg = get_config('block_opencast', 'uploadfileextensions_' . $defaultocinstanceid);
    $videoexts = empty($videotypescfg) || $videotypescfg == 'video' ?
        file_get_typegroup('extension', 'video') :
        array_map('trim', explode(',', $videotypescfg));
    $extensionsarray = [];
    foreach ($videoexts as $videoext) {
        $videoext = trim($videoext, '.');
        $extensionsarray[] = ['extension' => $videoext, 'message' => get_string('dnduploadvideofile', 'mod_opencast')];
    }
    $files = ['files' => $extensionsarray];
    return $files;
}

/**
 * Handle a file that has been uploaded
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function opencast_dndupload_handle($uploadinfo) {
    // Gather the required info.
    $data = new stdClass();
    $data->course = $uploadinfo->course->id;
    $data->name = get_string('uploadtitledisplay', 'mod_opencast', $uploadinfo->displayname);
    $data->ocinstanceid = \tool_opencast\local\settings_api::get_default_ocinstance()->id;
    $data->type = \mod_opencast\local\opencasttype::UPLOAD;
    $data->uploaddraftitemid = $uploadinfo->draftitemid;
    $data->opencastid = 'newfileupload';

    $data->id = opencast_add_instance($data, null);
    return $data->id ? $data->id : false;
}
