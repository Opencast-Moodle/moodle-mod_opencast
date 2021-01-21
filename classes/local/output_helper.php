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

namespace mod_opencast\local;

use mod_opencast\output\renderer;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class output_helper {

    public static function output_series($seriesid) {
        global $OUTPUT, $PAGE;
        $context = self::create_template_context_for_series($seriesid);

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

    public static function output_episode($episodeid, $seriesid = null) {
        global $PAGE;

        $api = apibridge::get_instance();
        $response = $api->get_episode_json($episodeid, $seriesid);

        if (!property_exists($response, 'episode')) {
            return;
        }

        echo \html_writer::script('window.episode = ' . json_encode($response->episode));
        echo \html_writer::start_div('player-wrapper');
        echo '<iframe src="player.html" id="player-iframe" allowfullscreen"></iframe>';
        echo \html_writer::end_div();
        $PAGE->requires->js_call_amd('mod_opencast/opencast_player', 'init');
    }

    public static function create_template_context_for_series($seriesid) {
        $api = apibridge::get_instance();
        $response = $api->get_episodes_in_series($seriesid);

        $result = [];
        global $PAGE;

        foreach ($response as $event) {
            $find_duration = !$event->duration;

            $url = null;
            foreach ($event->publications as $publication) {
                if ($publication->channel == 'api') {
                    foreach ($publication->attachments as $attachment) {
                        if ($attachment->flavor == 'presenter/search+preview') {
                            $url = $attachment->url;
                            break 2;
                        }
                        if ($attachment->flavor == 'presentation/search+preview') {
                            $url = $attachment->url;
                        }
                    }
                    if ($find_duration) {
                        foreach ($publication->media as $media) {
                            if ($media->duration > $event->duration) {
                                $event->duration = $media->duration;
                            }
                        }
                    }
                }
            }
            $video = new \stdClass();
            $video->start = $event->start;
            $video->title = $event->title;
            $video->created = $event->created;
            $video->duration = $event->duration;
            $video->thumbnail = $url;
            $video->link = $PAGE->url->out(false, ['e' => $event->identifier]);
            $video->description = $event->description;
            $result[] = $video;
        }
        $context = new stdClass();
        $context->episodes = $result;
        return $context;
    }
}
