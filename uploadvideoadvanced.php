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
 * Handles advanced video upload via opencast mod.
 *
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once(__DIR__ . '/uploadvideoadvanced_form.php');

use block_opencast\local\upload_helper;
use mod_opencast\local\upload_helper as mod_upload_helper;
use mod_opencast\settings\upload_settings_helper as mod_upload_settings_helper;
use block_opencast\local\workflowconfiguration_helper;

global $PAGE, $OUTPUT, $DB;

$cmid = required_param('cmid', PARAM_INT);
$ocinstanceid = required_param('ocinstanceid', PARAM_INT);

$cm = get_coursemodule_from_id('opencast', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record('opencast', ['id' => $cm->instance], '*', MUST_EXIST);

$baseurl = new moodle_url('/mod/opencast/uploadvideoadvanced.php', ['cmid' => $cm->id, 'ocinstanceid' => $ocinstanceid]);
$PAGE->set_url($baseurl);
$redirecturl = new moodle_url('/course/view.php', ['id' => $course->id]);
$simpleuploadurl = new moodle_url('/mod/opencast/uploadvideo.php', ['cmid' => $cm->id]);
require_login($course, false, $cm);

$context = context_module::instance($cm->id);
$coursecontext = context_course::instance($course->id);
// As we are doing the upload using the block_opencast, we have to use its capability as well.
require_capability('block/opencast:addvideo', $coursecontext);

$PAGE->set_pagelayout('popup');
$PAGE->set_context($context);

$PAGE->requires->css('/mod/opencast/styles.css');

if (empty($moduleinstance->uploaddraftitemid)) {
    throw new moodle_exception('uploadmissingfile', 'mod_opencast');
}

if (empty(get_config('mod_opencast', 'upload_enable_advanced_mode_' . $ocinstanceid))) {
    throw new moodle_exception('uploaddeactivatedadvancedmode', 'mod_opencast');
}

$formdata = mod_upload_helper::get_advanced_upload_form_data($ocinstanceid, $moduleinstance->id);

if (empty($formdata['tabs'])) {
    redirect($simpleuploadurl, get_string('uploadnoadvancedtabs', 'mod_opencast'), null, \core\output\notification::NOTIFY_ERROR);
}

$formdata['cmid'] = $cmid;
$formdata['moduleinstance'] = $moduleinstance;

$advanceduploadform = new mod_opencast_uploadvideoadvanced_form(null , $formdata);

if ($advanceduploadform->is_cancelled()) {
    redirect($redirecturl);
} else if ($data = $advanceduploadform->get_data()) {
    $ocinstanceid = intval($data->ocinstanceid);
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

    // Extracting metadata from the form data.
    $metadata = [];
    if (mod_upload_helper::advanced_tab_enabled($ocinstanceid, mod_upload_settings_helper::METADATA_TAB)) {
        $metadataunfiltered = array_filter((array) $data, function ($key) {
            return str_starts_with($key, mod_upload_helper::METADATA_ID_PREFIX);
        }, ARRAY_FILTER_USE_KEY);
        if (!empty($metadataunfiltered)) {
            foreach ($metadataunfiltered as $key => $value) {
                $metadata[] = [
                    'id' => str_replace(mod_upload_helper::METADATA_ID_PREFIX, '', $key),
                    'value' => $value,
                ];
            }
        } else {
            // If the metadata from the advanced mode is empty, we fallback to the simple mode metadata.
            $metadata = array_values((array) $uploadoptions->metadata);
            $metadata = array_map(function ($item) {
                return (array) $item;
            }, $metadata);
        }
    }

    // Taking care of the series id.
    $metadata[] = [
        'id' => 'isPartOf',
        'value' => $uploadoptions->seriesid,
    ];
    // Taking care of start date and time.
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

    // Visibility, by default we use the one from simple page, then we look for it in the form data.
    $formvisibility = $uploadoptions->visibility;
    if (mod_upload_helper::advanced_tab_enabled($ocinstanceid, mod_upload_settings_helper::VISIBILITY_TAB) &&
        property_exists($data, mod_upload_helper::VISIBILITY_ID)) {
        $formvisibility = $data->{mod_upload_helper::VISIBILITY_ID};
    }
    $visibility = new stdClass();
    $visibility->initialvisibilitystatus = $formvisibility;

    // Presentation file.
    if (mod_upload_helper::advanced_tab_enabled($ocinstanceid, mod_upload_settings_helper::PRESENTATION_TAB) &&
        (isset($data->{mod_upload_helper::PRESENTATION_CHUNKUPLOAD_ID}) ||
        isset($data->{mod_upload_helper::PRESENTATION_FILEPICKER_ID}))) {
        if (isset($data->{mod_upload_helper::PRESENTATION_CHUNKUPLOAD_ID})) {
            $presentationfileitemid = $data->{mod_upload_helper::PRESENTATION_CHUNKUPLOAD_ID};
        } else {
            $storedfilepresentation = $advanceduploadform->save_stored_file(
                mod_upload_helper::PRESENTATION_FILEPICKER_ID,
                $coursecontext->id,
                'block_opencast',
                upload_helper::OC_FILEAREA,
                $data->{mod_upload_helper::PRESENTATION_FILEPICKER_ID}
            );
            if ($storedfilepresentation) {
                $presentationfileitemid = $storedfilepresentation->get_itemid();
                \block_opencast\local\file_deletionmanager::track_draftitemid($coursecontext->id, $presentationfileitemid);
            }
        }
    }

    // Extracting processing options from the form data.
    $workflowconfiguration = null;
    if (mod_upload_helper::advanced_tab_enabled($ocinstanceid, mod_upload_settings_helper::PROCESSING_TAB)) {
        $wfconfighelper = workflowconfiguration_helper::get_instance($ocinstanceid);
        if ($configpaneldata = $wfconfighelper->get_userdefined_configuration_data($data)) {
            $workflowconfiguration = json_encode($configpaneldata);
        }
    }

    // Preparing subtitles.
    $transcriptions = [];
    $transcriptionuploadenabled = (bool) get_config('block_opencast', 'enableuploadtranscription_' . $ocinstanceid);
    if (mod_upload_helper::advanced_tab_enabled($ocinstanceid, mod_upload_settings_helper::SUBTITLE_TAB) &&
        $transcriptionuploadenabled) {
        $transcriptionlanguagesconfig = get_config('block_opencast', 'transcriptionlanguages_' . $ocinstanceid);
        $transcriptionlanguagesarray = json_decode($transcriptionlanguagesconfig) ?? [];
        foreach ($transcriptionlanguagesarray as $index => $language) {
            if (empty($language->key)) {
                continue;
            }
            $enabledelm = mod_upload_helper::TRANSCRIPTION_ID_PREFIX . $language->key .
                mod_upload_helper::TRANSCRIPTION_ENABLED_ID_SUFFIX;
            if (!property_exists($data, $enabledelm) || empty($data->{$enabledelm})) {
                continue;
            }
            $fileelm = mod_upload_helper::TRANSCRIPTION_ID_PREFIX . $language->key;
            if (property_exists($data, $fileelm)) {
                $storedfile = $advanceduploadform->save_stored_file($fileelm, $coursecontext->id,
                    'block_opencast', block_opencast\local\attachment_helper::OC_FILEAREA_ATTACHMENT, $data->{$fileelm});
                if (isset($storedfile) && $storedfile) {
                    $transcriptions[] = [
                        'file_itemid' => $storedfile->get_itemid(),
                        'file_id' => $storedfile->get_id(),
                        'file_contenhash' => $storedfile->get_contenthash(),
                        'lang' => $language->key,
                    ];
                }
            }
        }
    }

    $options = new \stdClass();
    $options->metadata = json_encode($metadata);
    $options->presenter = $savedvideofile->get_itemid();
    $options->presentation = isset($storedfilepresentation) && $storedfilepresentation ?
        $storedfilepresentation->get_itemid() : '';
    $options->chunkupload_presentation = isset($presentationfileitemid) ? $presentationfileitemid : '';

    $attachments = new stdClass();
    if (!empty($transcriptions)) {
        $attachments->transcriptions = $transcriptions;
    }
    // Adding attachment object to the options.
    $options->attachments = $attachments;

    upload_helper::save_upload_jobs($ocinstanceid, $course->id, $options, $visibility, $workflowconfiguration);

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

$advanceduploadform->display();

echo $OUTPUT->footer();
