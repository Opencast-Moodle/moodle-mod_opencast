moodle-mod_opencast
=====================
This activity can be used to display and view Opencast episodes and series in Moodle.
Users with respective privileges (in the following called teacher) can specify the ID of an existing Opencast 
episode/series to add it to their course. An embedded player allows students to watch the videos directly in Moodle. As with every activity, the teachers can restrict the access to the videos for students based on e.g. dates, grades or user profiles.
The <a href="https://github.com/polimediaupv/paella">Paella player</a> is used to play the videos.

The integration with the <a href="https://moodle.org/plugins/block_opencast">Opencast Videos</a> block makes the handling and access restriction of Opencast videos very simple. 
Videos can be uploaded via the block and made available via this activity. 
The block offers the functionality to add activities so that teachers don't have to explicitly specify the Opencast IDs.
Therefore, it is recommended to use the block for adding activities. Futher information can be found in the documentation of the block.

System requirements
------------------

* Min. Moodle Version: 3.8
* Opencast API level:
    * Minimum: v1.0.0
    * Recommended: v1.1.0
    * Some features might do not works as expected, when using an older API level.

* Installed plugin: <a href="https://github.com/unirz-tu-ilmenau/moodle-tool_opencast">tool_opencast</a>

Usage scenarios
---------------
In the following, the two usage scenarios, providing a series or an episode, are described.

### Use case 1 - Provide a series ###
The teacher wants to provide a series for students. For example, this series contains all lecture recordings.

Steps to add the activity:
1. Add a new "Video (Opencast)" activity. 
2. Enter the Opencast id of the series you want to display:</br>
<img src="https://user-images.githubusercontent.com/28386141/115534096-b5df3e00-a297-11eb-86c4-f69da06b0038.PNG" width="500"></br>
3. If you want, you can specify further configurations like access restrictions.
4. The videos in the series can be either displayed in a list view or in a preview view:</br>
<img src="https://user-images.githubusercontent.com/28386141/115258489-523b0080-a131-11eb-9ac1-0819c9aee5a4.png" width="250">
<img src="https://user-images.githubusercontent.com/28386141/115258708-857d8f80-a131-11eb-81a4-4bdbc295f45e.png" width="250">


### Use case 2 - Provide an episode ###
The teacher wants to provide a single episode for students. For example, this episode belongs to a specific lecture.
1. Add a new "Video (Opencast)" activity.
2. Enter the Opencast id of the episode you want to display as in the previous use case. The activity automatically recognizes that the entered id is an episode.
3. If you want, you can specify further configurations like access restrictions.
4. The activity directly displays a player that shows the video:</br>
<img src="https://user-images.githubusercontent.com/28386141/115257347-4b5fbe00-a130-11eb-92b6-b3bd2f832972.png" width="500"></br>

Configuration
-------------
The activity has two global configurations that can be modified by the administrator.
<img src="https://user-images.githubusercontent.com/28386141/115256354-6f6ecf80-a12f-11eb-8750-d5a8442d8403.png" width="500"></br>

The first configuration "Opencast Channel" specifies the Opencast channel to which the videos are published. Only videos published in this channel can be displayed with this activity.

The second configuration "URL to Paella config.json" specifies the path to the Paella player config. This config can be adapted if you want to modify the look or behavior of the Paella player.




## License ##

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
