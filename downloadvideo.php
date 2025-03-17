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
 * Provides a video file as download.
 *
 * @package    mod_opencast
 * @copyright  2021 Tamara Gunkel, University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_opencast\local\apibridge;
require_once('../../config.php');

global $PAGE, $OUTPUT, $CFG, $DB;

require_once($CFG->dirroot . '/repository/lib.php');

$o = required_param('o', PARAM_INT);
$episode = required_param('e', PARAM_ALPHANUMEXT);
$mediaid = required_param('mediaid', PARAM_ALPHANUMEXT);
$ocinstanceid = optional_param('ocinstanceid', \tool_opencast\local\settings_api::get_default_ocinstance()->id, PARAM_INT);

$moduleinstance = $DB->get_record('opencast', ['id' => $o], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('opencast', $moduleinstance->id, $course->id, false, MUST_EXIST);

require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

// Check if teacher enabled download.
if (!get_config('mod_opencast', 'global_download_' . $ocinstanceid) && !$moduleinstance->allowdownload) {
    die();
}

// Check if activity is visible for student.
if (empty($cm->visible) && !has_capability('moodle/course:viewhiddenactivities', $modulecontext)) {
    die();
}

$apibridge = apibridge::get_instance($ocinstanceid);
$result = $apibridge->get_opencast_video($episode, true);
if (!$result->error) {
    $video = $result->video;
    foreach ($video->publications as $publication) {
        if ($publication->channel == get_config('mod_opencast', 'download_channel_' . $ocinstanceid)) {
            foreach ($publication->media as $media) {
                if ($media->id === $mediaid) {
                    $downloadurl = $media->url;
                    $mimetype = $media->mediatype;
                    $size = $media->size;
                    break 2;
                }
            }
        }
    }
    if (!$downloadurl) {
        throw new coding_exception('Publication could not be found!');
    }

    $filename = $video->title . '.' . pathinfo($downloadurl, PATHINFO_EXTENSION);

    header('Content-Description: Download Video');
    header('Content-Type: ' . $mimetype);
    header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($filename));
    if (is_numeric($size) && $size > 0) {
        header('Content-Length: ' . $size);
    }

    if (is_https()) { // HTTPS sites - watch out for IE! KB812935 and KB316431.
        header('Cache-Control: private, max-age=10, no-transform');
        header('Expires: ' . gmdate('D, d M Y H:i:s', 0) . ' GMT');
        header('Pragma: ');
    } else { // Normal http - prevent caching at all cost.
        header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0, no-transform');
        header('Expires: ' . gmdate('D, d M Y H:i:s', 0) . ' GMT');
        header('Pragma: no-cache');
    }

    readfile($downloadurl);
} else {
    die();
}
