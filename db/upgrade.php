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
 * Plugin upgrade steps are defined here.
 *
 * @package     mod_opencast
 * @category    upgrade
 * @copyright   2020 Tobias Reischmann <tobias.reischmann@wi.uni-muenster.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute mod_opencast upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_opencast_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // For further information please read the Upgrade API documentation:
    // https://docs.moodle.org/dev/Upgrade_API
    //
    // You will also have to create the db/install.xml file by using the XMLDB Editor.
    // Documentation for the XMLDB Editor can be found at:
    // https://docs.moodle.org/dev/XMLDB_editor .

    if ($oldversion < 2021072000) {
        // Check if settings were upgraded without upgrading the plugin.
        if ($DB->get_record('config_plugins', ['plugin' => 'mod_opencast', 'name' => 'channel']) &&
            $DB->get_record('config_plugins', ['plugin' => 'mod_opencast', 'name' => 'channel_1'])) {
            // Remove already upgraded settings and only keep old ones.
            $DB->execute("DELETE FROM {config_plugins} WHERE plugin='mod_opencast' AND name = 'channel' OR name = 'configurl'");
        }

        // Update configs to use default tenant (id=1).
        $DB->execute("UPDATE {config_plugins} SET name=CONCAT(name,'_1') WHERE plugin='mod_opencast' " .
            "AND name = 'channel' OR name = 'configurl'");

        // Add new instance field to upload job table.
        $table = new xmldb_table('opencast');
        $field = new xmldb_field('ocinstanceid', XMLDB_TYPE_INTEGER, '10');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->set_field('opencast', 'ocinstanceid', 1);

        $field = new xmldb_field('ocinstanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $dbman->change_field_notnull($table, $field);

        // Opencast savepoint reached.
        upgrade_mod_savepoint(true, 2021072000, 'opencast');
    }

    if ($oldversion < 2021110900) {
        // Add new instance field to upload job table.
        $table = new xmldb_table('opencast');
        $field = new xmldb_field('allowdownload', XMLDB_TYPE_INTEGER, '1');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->set_field('opencast', 'allowdownload', '0');

        $field = new xmldb_field('allowdownload', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL);
        $dbman->change_field_notnull($table, $field);

        // Opencast savepoint reached.
        upgrade_mod_savepoint(true, 2021110900, 'opencast');
    }

    if ($oldversion < 2021111600) {
        $table = new xmldb_table('opencast');

        // Changing the default of field allowdownload on table opencast to 0.
        $field = new xmldb_field('allowdownload', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'ocinstanceid');

        // Launch change of default for field allowdownload.
        $dbman->change_field_default($table, $field);

        // Opencast savepoint reached.
        upgrade_mod_savepoint(true, 2021111600, 'opencast');
    }

    if ($oldversion < 2023052300) {
        // Define field uploaddraftitemid to be added to opencast.
        $table = new xmldb_table('opencast');
        $field = new xmldb_field('uploaddraftitemid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'opencastid');

        // Conditionally launch add field uploaddraftitemid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field uploadjobid to be added to opencast.
        $uploadjobidfield = new xmldb_field('uploadjobid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'uploaddraftitemid');
        // Conditionally launch add field uploadjobid.
        if (!$dbman->field_exists($table, $uploadjobidfield)) {
            $dbman->add_field($table, $uploadjobidfield);
        }

        // Opencast savepoint reached.
        upgrade_mod_savepoint(true, 2023052300, 'opencast');
    }

    if ($oldversion < 2023100900) {

        // Define field sortseriesby to be added to opencast.
        $table = new xmldb_table('opencast');
        $field = new xmldb_field('sortseriesby', XMLDB_TYPE_INTEGER, '2', null, null, null, null, 'allowdownload');

        // Conditionally launch add field sortseriesby.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Opencast savepoint reached.
        upgrade_mod_savepoint(true, 2023100900, 'opencast');
    }

    return true;
}
