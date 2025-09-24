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
 * Handles simple video upload via opencast mod
 *
 * @package    mod_opencast
 * @copyright  2023 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once(__DIR__ . '/uploadvideo_form.php');

use block_opencast\local\upload_helper;

global $PAGE, $OUTPUT, $DB;

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('opencast', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record('opencast', ['id' => $cm->instance], '*', MUST_EXIST);

$baseurl = new moodle_url('/mod/opencast/uploadvideo.php', ['cmid' => $cm->id]);
$PAGE->set_url($baseurl);
$redirecturl = new moodle_url('/course/view.php', ['id' => $course->id]);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
$coursecontext = context_course::instance($course->id);
// As we are doing the upload using the block_opencast, we have to use its capability as well.
require_capability('block/opencast:addvideo', $coursecontext);

$PAGE->set_pagelayout('popup');
$PAGE->set_context($context);

if (empty($moduleinstance->uploaddraftitemid)) {
    throw new moodle_exception('uploadmissingfile', 'mod_opencast');
}

$PAGE->requires->js_call_amd('mod_opencast/simple_upload_form', 'init', ['ocinstance_' . $moduleinstance->id]);

$formdata['cmid'] = $cmid;
$formdata['moduleinstance'] = $moduleinstance;

$mform = new mod_opencast_uploadvideo_form(null , $formdata);

if ($mform->is_cancelled()) {
    redirect($redirecturl);
} else if ($data = $mform->get_data()) {
    $ocinstanceid = intval($data->ocinstance);
    // Saving and getting the file.
    $newitemid = rand(1000000, 9999999);
    file_save_draft_area_files($moduleinstance->uploaddraftitemid, $coursecontext->id,
        'block_opencast', upload_helper::OC_FILEAREA, $newitemid);
    $fs = get_file_storage();
    $files = $fs->get_area_files($coursecontext->id, 'block_opencast', upload_helper::OC_FILEAREA, $newitemid, '', false);
    $savedvideofile = null;
    if ($files) {
        $savedvideofile = reset($files);
    }
    if (empty($savedvideofile)) {
        // In case the file is gone, we remove the instance, becuase it is no use of it anymore.
        course_delete_module($cm->id);
        opencast_delete_instance($moduleinstance->id);
        redirect($redirecturl, get_string('uploadmissingfile', 'mod_opencast'), null, \core\output\notification::NOTIFY_ERROR);
    } else {
        \block_opencast\local\file_deletionmanager::track_draftitemid($coursecontext->id, $savedvideofile->get_itemid());
    }

    $alluploadoptions = json_decode($moduleinstance->uploadoptionsjson);

    if ($alluploadoptions->selectedocinstanceid != $ocinstanceid) {
        redirect($redirecturl,
            get_string('uploadmismatchedocinstanceids', 'mod_opencast'), null, \core\output\notification::NOTIFY_ERROR);
    }

    $uploadoptions = $alluploadoptions?->options->{$ocinstanceid};

    $metadata = array_values((array) $uploadoptions->metadata);
    $metadata = array_map(function ($item) {
        return (array) $item;
    }, $metadata);
    $metadata[] = [
        'id' => 'isPartOf',
        'value' => $uploadoptions->seriesid,
    ];

    $sd = new DateTime("now", new DateTimeZone("UTC"));
    $sd->setTimestamp(time());
    $startdate = [
        'id' => 'startDate',
        'value' => $sd->format('Y-m-d'),
    ];
    $starttime = [
        'id' => 'startTime',
        'value' => $sd->format('H:i:s') . 'Z',
    ];
    $metadata[] = $startdate;
    $metadata[] = $starttime;

    $options = new \stdClass();
    $options->metadata = json_encode($metadata);
    $options->presenter = $savedvideofile->get_itemid();

    // Visibility.
    $visibility = new stdClass();
    $visibility->initialvisibilitystatus = $uploadoptions->visibility;

    // Workflow Configuration Panel.
    $wfconfigpanel = [];
    if (isset($uploadoptions->processing)) {
        $wfconfigpanel = json_encode((array) $uploadoptions->processing);
    }

    upload_helper::save_upload_jobs($ocinstanceid, $course->id, $options, $visibility, $wfconfigpanel);

    // Get the id of new added record for that upload job.
    $uploadjobid = 0;
    $uploadjobs = upload_helper::get_upload_jobs($ocinstanceid, $course->id);
    foreach ($uploadjobs as $uploadjob) {
        if ($uploadjob->presenter_fileid == $savedvideofile->get_id()) {
            $uploadjobid = $uploadjob->id;
            break;
        }
    }

    if (!empty($uploadjobid)) {
        try {
            $title = $metadata[array_search('title', array_column($metadata, 'id'))]['value'];
            // Gather more information about this module so that we can update the module info in the end.
            list($unusedcm, $unusedcontext, $unusedmodule, $opencastmoduledata, $unusedcw) =
                get_moduleinfo_data($cm , $course);

            // Replace the module info to update its type and other info.
            $opencastmoduledata->name = $title ? $title : get_string('defaultuploadedvideotitle', 'mod_opencast');
            $opencastmoduledata->uploadjobid = $uploadjobid;
            // Using a dummy parameter 'opencastmodtype' to be replaced with type at when updating record in db.
            $opencastmoduledata->opencastmodtype = \mod_opencast\local\opencasttype::UPLOADED;
            $opencastmoduledata->ocinstanceid = $ocinstanceid;
            // Update the module info directly.
            update_module($opencastmoduledata);
        } catch (\Exception $e) {
            \core\notification::warning($e->getMessage());
        }
    }
    $blockopencastlink = new moodle_url('/blocks/opencast/index.php',
        ['courseid' => $course->id, 'ocinstanceid' => $ocinstanceid]);
    redirect($redirecturl,
        get_string('uploadsaved', 'mod_opencast', $blockopencastlink->out()), null, \core\output\notification::NOTIFY_SUCCESS);
}

$PAGE->set_title(get_string('uploadform_simple_page_title', 'mod_opencast'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('uploadform_simple_header', 'mod_opencast'));

$mform->display();

echo $OUTPUT->footer();
