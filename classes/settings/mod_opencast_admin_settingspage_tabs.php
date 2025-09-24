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
 * Admin settings page with tabbed navigation for the Opencast activity module.
 *
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_opencast\settings;

use admin_settingpage;

/**
 * Admin settings page with tabbed navigation for the Opencast activity module.
 *
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni <zamani@elan-ev.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_opencast_admin_settingspage_tabs extends admin_settingpage {

    /** @var The tabs */
    protected $tabs = [];

    /**
     * Add a tab.
     *
     * @param admin_settingpage $tab A tab.
     */
    public function add_tab(admin_settingpage $tab) {
        foreach ($tab->settings as $setting) {
            $this->settings->{$setting->name} = $setting;
        }
        $this->tabs[] = $tab;
        return true;
    }

    /**
     * Add a tab (alias of add_tab).
     *
     * @param admin_settingpage $tab A tab.
     */
    public function add($tab) {
        return $this->add_tab($tab);
    }

    /**
     * Get tabs.
     *
     * @return array
     */
    public function get_tabs() {
        return $this->tabs;
    }

    /**
     * Generate the HTML output.
     *
     * @return string
     */
    public function output_html() {
        global $OUTPUT;

        $activetab = optional_param('activetab', '', PARAM_TEXT);
        $context = ['tabs' => []];
        $havesetactive = false;

        foreach ($this->get_tabs() as $tab) {
            $active = false;

            // Default to first tab it not told otherwise.
            if (empty($activetab) && !$havesetactive) {
                $active = true;
                $havesetactive = true;
            } else if ($activetab === $tab->name) {
                $active = true;
            }

            $context['tabs'][] = [
                'name' => $tab->name,
                'displayname' => $tab->visiblename,
                'html' => $tab->output_html(),
                'active' => $active,
            ];
        }

        if (empty($context['tabs'])) {
            return '';
        }

        return $OUTPUT->render_from_template('mod_opencast/admin_settings_tabs', $context);
    }
}

