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
 * Contains Functions to transform the opencast /api/events/{id} response into a data.json
 * as accepted by paella player.
 *
 * @package    mod_opencast
 * @copyright  2021 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_opencast\local;


defined('MOODLE_INTERNAL') || die();

class paella_transform {

    private static function get_api_publication($episode) {
        $channel = get_config('mod_opencast', 'channel');
        foreach($episode->publications as $publication) {
            if ($publication->channel == $channel) {
                return $publication;
            }
        }
        return false;
    }

    private static function get_preview_image($publication) {
        $presenterpreview = null;
        $presentationpreview = null;
        $otherpreview = null;

        foreach ($publication->attachments as $attachment) {
            if ($attachment->flavor === 'presenter/player+preview') {
                $presenterpreview = $attachment->url;
            } else if ($attachment->flavor === 'presentation/player+preview') {
                $presentationpreview = $attachment->url;
            } else if (substr($attachment->flavor, -15) === '/player+preview') {
                $otherpreview = $attachment->url;
            }
        }

        return $presentationpreview ?? $presenterpreview ?? $otherpreview;
    }

    private static function get_duration($publication) {
        $duration = 0;

        foreach ($publication->media as $media) {
            if ($media->duration > $duration) {
                $duration = $media->duration;
            }
        }
        return $duration / 1000;
    }

    private static function get_frame_list($publication) {
        $framelist = [];

        foreach ($publication->attachments as $attachment) {
            if ($attachment->flavor === 'presentation/segment+preview' ||
                $attachment->flavor === 'presentation/segment+preview+hires') {
                if (preg_match('/time=T(\d+):(\d+):(\d+)/', $attachment->ref, $matches)) {
                    $time = intval($matches[1]) * 60 * 60 + intval($matches[2]) * 60 + intval($matches[3]);
                    if (!array_key_exists($time, $framelist)) {
                        $framelist[$time] = [
                            'id' => 'frame_' . $time,
                            'mimetype' => $attachment->mediatype,
                            'time' => $time,
                            'url' => $attachment->url,
                            'thumb' => $attachment->url
                        ];
                    } else {
                        if (substr($attachment->flavor, -5) === 'hires') {
                            $framelist[$time]->url = $attachment->url;
                        } else {
                            $framelist[$time]->thumb = $attachment->url;
                        }

                    }
                }
            }
        }
        return $framelist;
    }

    private static function get_streams($publication, $framelist = []) {
        $streams = [];

        foreach ($publication->media as $media) {
            list($type, $mime) = explode('/', $media->mediatype, 2);
            $content = explode('/', $media->flavor, 2)[0];
            if (!array_key_exists($content, $streams)) {
                $streams[$content] = [
                    'sources' => [],
                    'content' => $content,
                    'type' => $type
                ];
            }
            if (!array_key_exists($mime, $streams[$content]['sources'])) {
                $streams[$content]['sources'][$mime] = [];
            }
            $streams[$content]['sources'][$mime][] = [
                'src' => $media->url,
                // 'isLiveStream' => false, // TODO
                'mimetype' => $media->mediatype,
                'type' => $media->mediatype,
                'res' => [
                    'w' => $media->width,
                    'h' => $media->height
                ]
            ];
        }
        return array_values($streams);
    }

    public static function get_paella_data_json($episodeid, $seriesid = null) {
        $api = new apibridge();
        if (($episode = $api->get_episode($episodeid, $seriesid)) === false) {
            return false;
        }

        if (($publication = self::get_api_publication($episode)) === false) {
            return false;
        }

        return [
            'metadata' => [
                'title' => $episode->title,
                'duration' => self::get_duration($publication),
                'preview' => self::get_preview_image($publication)
            ],
            'streams' => self::get_streams($publication),
            'frameList' => self::get_frame_list($publication),
            'captions' => null
        ];
    }

}