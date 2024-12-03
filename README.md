# moodle-mod_opencast

This activity can be used to display and view Opencast episodes and series in Moodle.

This file is part of the mod_opencast plugin for Moodle - <http://moodle.org/>

*Maintainer:*    Thomas Niedermaier (Universität Münster), Farbod Zamani (Elan e.V.)

*Copyright:* 2017 Andreas Wagner, SYNERGY LEARNING, 2024 Thomas Niedermaier, UNIVERSITÄT MÜNSTER

*License:*   [GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html)


Description
-----------

This activity can be used to display and view Opencast episodes and series in Moodle.
Users with respective privileges (in the following called teacher) can specify the ID of an existing Opencast 
episode/series to add it to their course. An embedded player allows students to watch the videos directly in Moodle. As with every activity, the teachers can restrict the access to the videos for students based on e.g. dates, grades or user profiles.
The <a href="https://github.com/polimediaupv/paella-core">Paella player</a> is used to play the videos.

The integration with the <a href="https://moodle.org/plugins/block_opencast">Opencast Videos</a> block makes the handling and access restriction of Opencast videos very simple. 
Videos can be uploaded via the block and made available via this activity.


Requirements
------------

* tool_opencast
* *Recommended:* block_opencast
* *Optional:* filter_opencast

Installation
------------

* Copy the module code directly to the mod/opencast directory.

* Log into Moodle as administrator.

* Open the administration area (http://your-moodle-site/admin) to start the installation
  automatically.


Admin Settings
--------------

View the documentation of the plugin settings [here](https://moodle.docs.opencast.org/#mod/settings/).


Documentation
-------------

The full documentation of the plugin can be found [here](https://moodle.docs.opencast.org/#mod/about/).

Bug Reports / Support
---------------------

We try our best to deliver bug-free plugins, but we can not test the plugin for every platform,
database, PHP and Moodle version. If you find any bug please report it on
[GitHub](https://github.com/Opencast-Moodle/moodle-mod_opencast/issues). Please
provide a detailed bug description, including the plugin and Moodle version and, if applicable, a
screenshot.

You may also file a request for enhancement on GitHub.


License
-------

This plugin is developed in cooperation with the WWU Münster.

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.
