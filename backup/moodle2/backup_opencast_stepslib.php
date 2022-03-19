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
 * Define all the backup steps that will be used by the backup_opencast_activity_task
 *
 * @package    mod_opencast
 * @copyright  2021 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * Define the complete opencast structure for backup, with file and id annotations
  */
class backup_opencast_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define the structure to be processed by this backup step.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        // The Opencast module stores no user info.

        // Define each element separated.
        $opencast = new backup_nested_element('opencast', array('id'), array(
            'name', 'timecreated', 'timemodified', 'intro', 'introformat',
                'opencastid', 'ocinstanceid', 'type'));

        // Define sources.
        $opencast->set_source_table('opencast', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations.
        // Module has no id annotations.

        // Return the root element (opencast), wrapped into standard activity structure.
        return $this->prepare_activity_structure($opencast);

    }
}
