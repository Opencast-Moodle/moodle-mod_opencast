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
 * Handles video upload via opencast mod
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

use block_opencast\local\apibridge;
use tool_opencast\local\settings_api;
use block_opencast\local\upload_helper;

global $PAGE, $OUTPUT, $USER, $DB;

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

$PAGE->set_pagelayout('frametop');
$PAGE->set_context($context);

if (empty($moduleinstance->uploaddraftitemid)) {
    throw new moodle_exception('uploadmissingfile', 'mod_opencast');
}

// Preparing the content to be shown in the form.
// Getting all the oc instances.
$ocinstances = settings_api::get_ocinstances();
// Getting user defaults.
$userdefaultsrecord = $DB->get_record('block_opencast_user_default', ['userid' => $USER->id]);
$userdefaults = $userdefaultsrecord ? json_decode($userdefaultsrecord->defaults, true) : [];
$usereventdefaults = (!empty($userdefaults['event'])) ? $userdefaults['event'] : [];
// Getting medatadata catalogs based on the ocinstances.
$metadatacatalogs = [];
// Getting serires of the course based on ocinstances.
$allseries = [];
foreach ($ocinstances as $ocinstance) {
    $apibridge = apibridge::get_instance($ocinstance->id);
    // Metadatacatalogs.
    $metadatacatalog = upload_helper::get_opencast_metadata_catalog($ocinstance->id);
    if (!empty($metadatacatalog)) {
        // At thus point we only provide those metadatacatalogs that are required.
        $requiredmetadata = array_filter($metadatacatalog, function ($metadata) {
            return $metadata->required == 1;
        });
        $metadatacatalogs[$ocinstance->id] = $requiredmetadata;
    }
    // Series.
    $seriesrecords = $DB->get_records('tool_opencast_series',
        ['courseid' => $course->id, 'ocinstanceid' => $ocinstance->id]);
    if ($seriesrecords) {
        $defaultseries = array_search('1', array_column($seriesrecords, 'isdefault', 'series'));
        $seriesoption = [];

        try {
            $seriesrecords = $apibridge->get_multiple_series_by_identifier($seriesrecords);
            foreach ($seriesrecords as $series) {
                $seriesobj = new \stdClass();
                $seriesobj->id = $series->identifier;
                $seriesobj->name = $series->title;
                $seriesobj->isdefault = $series->identifier == $defaultseries ? 1 : 0;
                $seriesoption[$series->identifier] = $seriesobj;
            }
        } catch (\tool_opencast\exception\opencast_api_response_exception $e) {
            \core\notification::warning($e->getMessage());
            foreach ($seriesrecords as $series) {
                $seriesobj = new \stdClass();
                $seriesobj->id = $series->series;
                $seriesobj->name = $series->series;
                $seriesobj->isdefault = $series->series == $defaultseries ? 1 : 0;
                $seriesoption[$series->series] = $seriesobj;
            }
        }
        $allseries[$ocinstance->id] = $seriesoption;
    }
}

$formdata = [
    'cmid' => $cmid,
    'moduleinstance' => $moduleinstance,
    'ocinstances' => $ocinstances,
    'allseries' => $allseries,
    'metadatacatalogs' => $metadatacatalogs,
    'eventdefaults' => $usereventdefaults,
];

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
        $coursecontext = context_course::instance($course->id);
        \block_opencast\local\file_deletionmanager::track_draftitemid($coursecontext->id, $savedvideofile->get_itemid());
    }

    $flavorfieldname = 'flavor_' . $ocinstanceid;
    $videoflavor = intval($data->$flavorfieldname);
    $seiresfieldname = 'series_' . $ocinstanceid;
    $metadata = [];
    $metadata[] = [
        'id' => 'isPartOf',
        'value' => $data->$seiresfieldname,
    ];
    $gettitle = true; // Make sure title (required) is added into metadata.
    foreach ($metadatacatalogs[$ocinstanceid] as $field) {
        $id = $field->name . '_' . $ocinstanceid;
        if (property_exists($data, $id) && $data->$id) {
            if ($field->name == 'title') { // Make sure the title is received!
                $gettitle = false;
            }
            if ($field->name == 'subjects') {
                !is_array($data->$id) ? $data->$id = [$data->$id] : $data->$id = $data->$id;
            }
            $obj = [
                'id' => $field->name,
                'value' => $data->$id,
            ];
            $metadata[] = $obj;
        }
    }

    if ($gettitle) {
        $id = 'title_' . $ocinstanceid;
        $titleobj = [
            'id' => 'title',
            'value' => $data->$id ? $data->$id : 'upload-task',
        ];
        $metadata[] = $titleobj;
    }

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
    if ($videoflavor === 0) { // Presenter video.
        $storedfilepresenter = $savedvideofile;
    } else if ($videoflavor === 1) { // Presentation video.
        $storedfilepresentation = $savedvideofile;
    }
    $options->presenter = isset($storedfilepresenter) && $storedfilepresenter ? $storedfilepresenter->get_itemid() : '';
    $options->presentation = isset($storedfilepresentation) && $storedfilepresentation ? $storedfilepresentation->get_itemid() : '';
    upload_helper::save_upload_jobs($ocinstanceid, $course->id, $options);

    // Get the id of new added record for that upload job.
    $uploadjobid = 0;
    $uploadjobs = upload_helper::get_upload_jobs($ocinstanceid, $course->id);
    foreach ($uploadjobs as $uploadjob) {
        if ($videoflavor === 0 && $uploadjob->presenter_fileid == $storedfilepresenter->get_id() ||
            $videoflavor === 1 && $uploadjob->presentation_fileid == $storedfilepresentation->get_id()) {
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

$PAGE->set_title(get_string('uploadformtitle', 'mod_opencast'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('uploadformtitle', 'mod_opencast'));

$mform->display();

echo $OUTPUT->footer();
