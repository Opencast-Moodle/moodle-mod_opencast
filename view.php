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
 * Prints an instance of mod_opencast.
 *
 * @package     mod_opencast
 * @copyright   2020 Tobias Reischmann <tobias.reischmann@wi.uni-muenster.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_opencast\local\opencasttype;
use mod_opencast\output\renderer;

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

global $OUTPUT, $DB, $PAGE;

// Course_module ID, or
$id = optional_param('id', 0, PARAM_INT);

// ... module instance id.
$o  = optional_param('o', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('opencast', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('opencast', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($o) {
    $moduleinstance = $DB->get_record('opencast', array('id' => $o), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('opencast', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', 'mod_opencast'));
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$PAGE->set_url('/mod/opencast/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

$viewlist = optional_param('list', null, PARAM_BOOL);
if ($viewlist !== null) {
    require_sesskey();
    if ($viewlist) {
        set_user_preference('mod_opencast/list', '1');
    } else {
        unset_user_preference('mod_opencast/list');
    }
    redirect($PAGE->url);
    die();
}

$event = \mod_opencast\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('opencast', $moduleinstance);
$event->trigger();

echo $OUTPUT->header();

if ($moduleinstance->type == opencasttype::EPISODE) {
    echo "<iframe src=" . (new moodle_url("/mod/opencast/player.php?id=" . $cm->id))->out() .
        " allowfullscreen " . "style='width: 100%; height: 50vw'></iframe>";
} else if ($moduleinstance->type == opencasttype::SERIES) {
    $episode = optional_param('e', null, PARAM_ALPHANUMEXT);
    if ($episode) {
        echo "<iframe src=" . (new moodle_url("/mod/opencast/player.php?id=$cm->id&e=$episode"))->out() .
            " allowfullscreen " . "style='width: 100%; height: 50vw'></iframe>";
    } else {
        echo $OUTPUT->heading($moduleinstance->name);
        $api = \mod_opencast\local\apibridge::get_instance();
        $context = new stdClass();
        $context->episodes = $api->get_episodes_in_series($moduleinstance->opencastid);

        $listviewactive = get_user_preferences('mod_opencast/list', false);
        /** @var renderer $renderer */
        $renderer = $PAGE->get_renderer('mod_opencast');
        echo $renderer->render_listview_toggle($listviewactive);
        if ($listviewactive) {
            $table = new \mod_opencast\local\table_series_list_view();
            $table->define_baseurl($PAGE->url);
            $table->set_data($context->episodes);
            $table->finish_output();
        } else {
            echo $OUTPUT->render_from_template('mod_opencast/series', $context);
        }
    }
} else {
    echo "Not yet fetched.";
}

echo $OUTPUT->footer();
