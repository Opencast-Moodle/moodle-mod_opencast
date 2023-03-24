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
 * Types of mod_opencast instances. Either SERIES, EPISODE or UNDEFINED.
 *
 * @package    mod_opencast
 * @copyright  2020 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_opencast\local;

/**
 * Types of mod_opencast instances. Either SERIES, EPISODE or UNDEFINED.
 *
 * @package    mod_opencast
 * @copyright  2020 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class opencasttype {

    /** @var int It is not known, what is specified by the ID */
    const UNDEFINED = 0;
    /** @var int The ID specifies a Episode */
    const EPISODE = 1;
    /** @var int The ID specifies a Series */
    const SERIES = 2;
    /** @var int The ID specifies an Upload */
    const UPLOAD = 3;
}
