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
 * Contains Functions to transform the opencast /api/events/{id} response into a data.json as accepted by paella player.
 * @package    mod_opencast
 * @copyright  2021 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_opencast\local;

/**
 * Helper for preparing the data from the Opencast API for the paella player.
 * @package mod_opencast
 * @copyright  2021 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class paella_transform {

    /**
     * Returns the publication with the correct release channel for a given episode.
     * @param int $ocinstanceid Opencast instance id
     * @param string $episode Episode id
     * @return false|mixed Publication or false if no publication for the configured channel exists.
     * @throws \dml_exception
     */
    private static function get_api_publication($ocinstanceid, $episode) {
        $channel = get_config('mod_opencast', 'channel_' . $ocinstanceid);
        foreach ($episode->publications as $publication) {
            if ($publication->channel == $channel) {
                return $publication;
            }
        }
        return false;
    }

    /**
     * Returns the preview image for a publication.
     * @param string $publication Publication id
     * @return mixed|null Url to preview image or null if not existing
     */
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

        return $presentationpreview ?? $presenterpreview ?? $otherpreview
                ?? (new \moodle_url('/mod/opencast/pix/nothumbnail.png'))->out(false);
    }

    /**
     * Returns the duration of a publication.
     * @param string $publication Publication id
     * @return float|int duration in seconds
     */
    private static function get_duration($publication) {
        $duration = 0;

        foreach ($publication->media as $media) {
            if ($media->duration > $duration) {
                $duration = $media->duration;
            }
        }
        return $duration / 1000;
    }

    /**
     * Returns the frames of a publication.
     * @param string $publication Publication id
     * @return array of frames
     */
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
                            'thumb' => $attachment->url,
                        ];
                    } else {
                        if (substr($attachment->flavor, -5) === 'hires') {
                            $framelist[$time]['url'] = $attachment->url;
                        } else {
                            $framelist[$time]['thumb'] = $attachment->url;
                        }

                    }
                }
            }
        }
        return array_values($framelist);
    }

    /**
     * Return the source type for a track
     * @param string $track Track
     * @return mixed|string|null
     */
    private static function get_source_type_from_track($track) {
        $protocol = parse_url($track->url);
        $sourcetype = null;

        if ($protocol && $protocol['scheme']) {
            switch ($protocol['scheme']) {
                case 'rtmp':
                case 'rtmps':
                    if (in_array($track, ['video/mp4', 'video/ogg', 'video/webm', 'video/x-flv'])) {
                        $sourcetype = 'rtmp';
                    }
                    break;
                case 'http':
                case 'https':
                    switch ($track->mediatype) {
                        case 'video/mp4':
                        case 'video/ogg':
                        case 'video/webm':
                            list($type, $sourcetype) = explode('/', $track->mediatype, 2);
                            break;
                        case 'video/x-flv':
                            $sourcetype = 'flv';
                            break;
                        case 'application/x-mpegURL':
                            $sourcetype = 'hls';
                            break;
                        case 'application/dash+xml':
                            $sourcetype = 'mpd';
                            break;
                        case 'audio/m4a':
                            $sourcetype = 'audio';
                            break;
                    }
                    break;
            }
        }

        return $sourcetype;
    }

    /**
     * Creates the streams for a publication.
     * @param string $publication Publication id
     * @return array of streams
     */
    private static function get_streams($publication) {
        $streams = [];
        $ismainaudioset = false;

        foreach ($publication->media as $media) {
            $sourcetype = self::get_source_type_from_track($media);
            $content = explode('/', $media->flavor, 2)[0];
            // From Opencast 13, captions are shiffted to media, therefore we need to skip them here for streams.
            if ($content == 'captions') {
                continue;
            }
            if (!array_key_exists($content, $streams)) {
                $streams[$content] = [
                    'sources' => [],
                    'content' => $content,
                    'type' => 'video',
                ];
                $hasadaptivemastertrack[$content] = false;
            }

            $ismaster = false;
            if (isset($media->is_master_playlist) && $media->is_master_playlist) {
                $hasadaptivemastertrack[$content] = true;
                $ismaster = true;
            }

            if ($sourcetype == 'hls' && !$ismaster) {
                continue;
            }

            if (!array_key_exists($sourcetype, $streams[$content]['sources'])) {
                $streams[$content]['sources'][$sourcetype] = [];
            }

            $streams[$content]['sources'][$sourcetype][] = [
                'src' => $media->url,
                'mimetype' => $media->mediatype,
                'res' => [
                    'w' => isset($media->width) ? $media->width : 0,
                    'h' => isset($media->height) ? $media->height : 0,
                ],
                'master' => $ismaster,
                'isLiveStream' => isset($media->is_live) && $media->is_live,
            ];

            if (!$ismainaudioset && isset($media->has_audio) && $media->has_audio) {
                $streams[$content]['role'] = 'mainAudio';
                $ismainaudioset = true;
            }
        }

        $streams = array_values($streams);
        if (!$ismainaudioset) {
            $streams[0]['role'] = 'mainAudio';
        }

        return $streams;
    }

    /**
     * Returns the captions of a publication.
     * @param string $publication Publication id
     * @return array of captions
     */
    private static function get_captions($publication) {
        $captions = [];
        foreach ($publication->attachments as $attachment) {
            list($type1, $type2) = explode('/', $attachment->flavor, 2);
            if ($type1 === 'captions') {
                list($format, $lang) = explode('+', $type2, 2);
                $captions[] = [
                    'lang' => $lang,
                    'text' => $lang,
                    'format' => $format,
                    'url' => $attachment->url,
                ];
            }
        }
        // Opencast 13 handles captions under media, therefore we need to capture them here as well.
        foreach ($publication->media as $media) {
            list($type1, $type2) = explode('/', $media->flavor, 2);
            if ($type1 === 'captions') {
                $lang = 'undefined';
                $format = 'vtt'; // Default standard format.
                $text = 'unknown';
                // Prior to Opencast 15 or manually added subtitles in block opencast.
                if (strpos($type2, 'vtt+') !== false) {
                    list($format, $lang) = explode('+', $type2, 2);
                    $text = $lang;
                } else if (in_array($type2, ['delivery', 'prepared', 'preview', 'vtt', 'source']) && !empty($media->tags)) {
                    // Opencast 15 coverage.
                    $tagdataarr = [];
                    foreach ($media->tags as $tag) {
                        // The safety checker.
                        if (!is_string($tag)) {
                            continue;
                        }
                        if (strpos($tag, 'lang:') === 0) {
                            $lang = substr($tag, strlen('lang:'));
                            $tagdataarr['lang'] = $lang;
                        }
                        if (strpos($tag, 'generator-type:') === 0) {
                            $tagdataarr['generatortype'] = substr($tag, strlen('generator-type:'));
                        }
                        if (strpos($tag, 'generator:') === 0) {
                            $tagdataarr['generator'] = substr($tag, strlen('generator:'));
                        }
                        if (strpos($tag, 'type:') === 0) {
                            $tagdataarr['type'] = substr($tag, strlen('type:'));
                        }
                    }
                    $text = self::prepare_caption_text($tagdataarr);
                    list($mimefiletype, $format) = explode('/', $media->mediatype, 2);
                }
                $captions[] = [
                    'lang' => $lang,
                    'text' => $text,
                    'format' => $format,
                    'url' => $media->url,
                ];
            }
        }
        return $captions;
    }

    /**
     * Generates the caption text in case the caption info is included in the tags (as introduced in Opencast 15)
     * @param array $tagdataarr array of caption data.
     * @return string the complied text for the caption.
     */
    private static function prepare_caption_text($tagdataarr) {
        $titlearr = [];
        if (array_key_exists('lang', $tagdataarr)) {
            $titlearr[] = $tagdataarr['lang'];
        }
        if (array_key_exists('generator', $tagdataarr)) {
            $generator = ucfirst($tagdataarr['generator']);
            $titlearr[] = $generator;
        }
        if (array_key_exists('generatortype', $tagdataarr)) {
            $generatortype = $tagdataarr['generatortype'] === 'auto' ?
                get_string('captions_generator_type_auto', 'mod_opencast') :
                get_string('captions_generator_type_manual', 'mod_opencast');
            $titlearr[] = "($generatortype)";
        }
        if (array_key_exists('type', $tagdataarr)) {
            $type = ucfirst($tagdataarr['type']);
            $titlearr[] = "($type)";
        }
        return implode(' ', $titlearr);
    }

    /**
     * This function retrieves the episode and publication data from the Opencast API,
     * processes it to generate the required data structure for the Paella player,
     * and returns it along with any error messages.
     *
     * @param int $ocinstanceid The Opencast instance ID.
     * @param string $episodeid The episode ID.
     * @param string|null $seriesid The series ID (optional, defaults to null).
     * @return array An array containing the prepared data and any error messages.
     *               The array has the following structure: [data, errormessage].
     *               If there are no errors, the data will be an associative array with the following keys:
     *               - 'metadata': An associative array containing the video's metadata (id, title, duration, preview).
     *               - 'streams': An array containing the video's streams.
     *               - 'frameList': An array containing the video's frame list.
     *               - 'captions': An array containing the video's captions.
     *               If there are errors, the data will be false, and the errormessage will contain the error message.
     */
    public static function get_paella_data_json($ocinstanceid, $episodeid, $seriesid = null) {
        $haserror = false;
        $errormessage = '';
        $data = false;
        try {
            $api = apibridge::get_instance($ocinstanceid);
            if (($episode = $api->get_episode($episodeid, $seriesid)) === false) {
                $haserror = true;
                $errormessage = get_string('errorvideonotavailable', 'mod_opencast');
            }

            if (($publication = self::get_api_publication($ocinstanceid, $episode)) === false) {
                $haserror = true;
                $errormessage = get_string('errorvideonotready', 'mod_opencast');
            }

            if (!$haserror) {
                $data = [
                    'metadata' => [
                        'id' => $episodeid,
                        'title' => $episode->title,
                        'duration' => self::get_duration($publication),
                        'preview' => self::get_preview_image($publication),
                    ],
                    'streams' => self::get_streams($publication),
                    'frameList' => self::get_frame_list($publication),
                    'captions' => self::get_captions($publication),
                ];
            }
        } catch (\Throwable $e) {
            $errormessage = get_string('errorfetchingvideo', 'mod_opencast');
        }

        return [$data, $errormessage];
    }
}
