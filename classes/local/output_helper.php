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
 * Helper for generating page output for series and episodes.
 * @package    mod_opencast
 * @copyright  2021 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_opencast\local;

use action_menu;
use action_menu_link_secondary;
use core_date;
use DateTime;
use mod_opencast\output\renderer;
use pix_icon;
use stdClass;

/**
 * Helper for generating page output for series and episodes.
 * @package    mod_opencast
 * @copyright  2021 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class output_helper {

    /**
     * Prints output for series view.
     * @param int $ocinstanceid Opencast instance id
     * @param string $seriesid opencast id of series.
     * @param string $activityname name of Activity.
     * @throws \coding_exception
     */
    public static function output_series($ocinstanceid, $seriesid, $activityname): void {
        global $OUTPUT, $PAGE;

        $api = apibridge::get_instance($ocinstanceid);
        $response = $api->get_episodes_in_series($seriesid);

        if ($response === false) {
            throw new \exception('There was a problem reaching opencast!');
        }

        $context = self::create_template_context_for_series($ocinstanceid, $response);

        if (!$context) {
            throw new \coding_exception('There was a problem processing the series.');
        }

        echo $OUTPUT->header();

        if (count($context->episodes) == 0) {
            echo $OUTPUT->heading($activityname);
            echo '</br></br>';
            echo \html_writer::tag('h4', get_string('seriesisempty', 'mod_opencast'));
        } else {
            echo $OUTPUT->heading($activityname);

            $listviewactive = get_user_preferences('mod_opencast/list', false);
            /** @var renderer $renderer */
            $renderer = $PAGE->get_renderer('mod_opencast');
            echo $renderer->render_listview_toggle($listviewactive);
            if ($listviewactive) {
                $table = new table_series_list_view();
                $table->define_baseurl($PAGE->url);
                $table->set_data($context->episodes);
                $table->finish_output();
            } else {
                echo $OUTPUT->render_from_template('mod_opencast/series', $context);
            }
        }

        echo $OUTPUT->footer();
    }

    /**
     * Prints output for episode.
     *
     * @param int $ocinstanceid Opencast instance id
     * @param string $episodeid The opencast episode id
     * @param int $modinstanceid Module instance id
     * @param null|string $seriesid If given, it will be ensured that the episode is part of the series.
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function output_episode($ocinstanceid, $episodeid, $modinstanceid, $seriesid = null): void {
        global $PAGE, $OUTPUT, $DB;

        $data = paella_transform::get_paella_data_json($ocinstanceid, $episodeid, $seriesid);

        if (!$data) {
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('errorfetchingvideo', 'mod_opencast'));
            echo $OUTPUT->footer();
            return;
        }

        $title = $data['metadata']['title'];

        if ($seriesid) {
            // If episode is viewed as part of a series, add episode title to navbar.
            if (strlen($title) > 50) {
                $title = substr($title, 0, 50) . '...';
            }
            $PAGE->navbar->add($title);
        }

        echo $OUTPUT->header();

        $configurl = new \moodle_url(get_config('mod_opencast', 'configurl_' . $ocinstanceid));
        $themeurl = new \moodle_url(get_config('mod_opencast', 'themeurl_' . $ocinstanceid));
        echo \html_writer::script('window.episode = ' . json_encode($data));

        echo $OUTPUT->heading($title);

        echo '<br>';

        // Find aspect-ratio if there is only one video track.
        if (count($data['streams']) === 1) {
            $sources = $data['streams'][0]['sources'];
            $res = $sources[array_key_first($sources)][0]['res'];
            $resolution = $res['w'] . '/' . $res['h'];
            echo \html_writer::start_div('player-wrapper', ['style' => '--aspect-ratio:' . $resolution]);
        } else {
            echo \html_writer::start_div('player-wrapper');
        }

        echo '<iframe src="player.html" id="player-iframe" class="mod-opencast-paella-player" allowfullscreen"></iframe>';
        echo \html_writer::end_div();

        $PAGE->requires->js_call_amd('mod_opencast/opencast_player', 'init',
                [$configurl->out(false), $themeurl->out(false)]);

        $moduleinstance = $DB->get_record('opencast', array('id' => $modinstanceid), '*', MUST_EXIST);
        if (get_config('mod_opencast', 'global_download_' . $ocinstanceid) || $moduleinstance->allowdownload) {
            self::output_download_menu($ocinstanceid, $episodeid, $modinstanceid);
        }

        echo $OUTPUT->footer();
    }

    /**
     * Prints output for the student download menu.
     * @param int $ocinstanceid Opencast instance id
     * @param string $episodeid The opencast episode id
     * @param string $modinstanceid Module instance id
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function output_download_menu($ocinstanceid, $episodeid, $modinstanceid) {
        global $PAGE;

        $api = apibridge::get_instance($ocinstanceid);
        if (($video = $api->get_episode($episodeid)) !== false) {

            // Get the action menu options.
            $actionmenu = new action_menu();
            $actionmenu->set_alignment(action_menu::TL, action_menu::BL);
            $actionmenu->prioritise = true;
            $actionmenu->actionicon = new pix_icon('t/down', get_string('downloadvideo', 'mod_opencast'));
            $actionmenu->actiontext = 'Download';
            $actionmenu->set_menu_trigger(' ');
            $actionmenu->attributes['class'] .= ' download-action-menu float-right pt-1';

            foreach ($video->publications as $publication) {
                if ($publication->channel == get_config('mod_opencast', 'download_channel_' . $ocinstanceid)) {
                    foreach ($publication->media as $media) {
                        $name = ucwords(explode('/', $media->flavor)[0]) . ' (' . $media->width . 'x' . $media->height . ')';
                        $actionmenu->add(new action_menu_link_secondary(
                            new \moodle_url('/mod/opencast/downloadvideo.php',
                                array('e' => $video->identifier, 'o' => $modinstanceid,
                                    'mediaid' => $media->id, 'ocinstanceid' => $ocinstanceid)),
                            null,
                            $name
                        ));
                    }
                }
            }

            $output = $PAGE->get_renderer('mod_opencast');
            echo $output->render($actionmenu);
        }
    }

    /**
     * Creates Context for use in series template.
     * @param int $ocinstanceid Opencast instance id
     * @param array $seriesjson Response from /api/events/
     * @return stdClass (example in series.mustache)
     */
    public static function create_template_context_for_series($ocinstanceid, $seriesjson): stdClass {
        global $PAGE;

        $result = [];

        $channel = get_config('mod_opencast', 'channel_' . $ocinstanceid);
        foreach ($seriesjson as $event) {
            $findduration = !$event->duration;
            $video = new \stdClass();
            $url = null;
            foreach ($event->publications as $publication) {
                if ($publication->channel == $channel) {
                    foreach ($publication->attachments as $attachment) {
                        // If presentation preview available, use that, else use presenter preview.
                        if ($attachment->flavor == 'presentation/search+preview') {
                            $url = $attachment->url;
                            break;
                        }
                        if ($attachment->flavor == 'presenter/search+preview') {
                            $url = $attachment->url;
                        }
                    }
                    $video->haspresenter = false;
                    $video->haspresentation = false;
                    foreach ($publication->media as $media) {
                        if ($media->flavor === 'presenter/delivery') {
                            $video->haspresenter = true;
                        } else if ($media->flavor === 'presentation/delivery') {
                            $video->haspresentation = true;
                        }
                    }
                    if ($findduration) {
                        $event->duration = 0;
                        foreach ($publication->media as $media) {
                            if ($media->duration > $event->duration) {
                                $event->duration = $media->duration;
                            }
                        }
                    }
                    break;
                }
            }
            if (!$url) {
                continue;
            }
            $video->date = self::format_date($event->start);
            $video->title = $event->title;
            $video->duration = $event->duration ? self::format_duration($event->duration) : null;
            $video->thumbnail = $url;
            $video->link = $PAGE->url->out(false, ['e' => $event->identifier]);
            $video->description = $event->description;
            $result[] = $video;
        }
        $context = new stdClass();
        $context->episodes = $result;
        return $context;
    }

    /**
     * Formats a duration in seconds into h:mm:ss or m:ss
     * @param int $duration
     * @return string
     */
    private static function format_duration($duration): string {
        $duration = intval($duration / 1000);
        $secs = $duration % 60;
        $duration = intdiv($duration, 60);
        $mins = $duration % 60;
        $hours = intdiv($duration, 60);

        if ($hours) {
            return sprintf("%d:%02d:%02d", $hours, $mins, $secs);
        } else {
            return sprintf("%d:%02d", $mins, $secs);
        }
    }

    /**
     * Formats a date.
     * @param string $startdate
     * @return string
     */
    private static function format_date($startdate): string {
        $dt = new DateTime($startdate, core_date::get_server_timezone_object());
        return userdate($dt->getTimestamp(), get_string('strftimedatefullshort', 'core_langconfig'),
            99, false);
    }
}
