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
 * Strings for component 'storeinfo_setsetst', language 'en'.
 *
 * @package    storeinfo_setsetst
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


$string['encoding'] = 'File encoding';
$string['expiredaction'] = 'storemultiinfo expiration action';
$string['expiredaction_help'] = 'Select action to carry out when user storemultiinfo expires. Please note that some user data and settings are purged from course during course unstoremultiinfo.';
$string['filelockedmail'] = 'The text file you are using for file-based storemultiinfos ({$a}) can not be deleted by the cron process.  This usually means the permissions are wrong on it.  Please fix the permissions so that Moodle can delete the file, otherwise it might be processed repeatedly.';
$string['filelockedmailsubject'] = 'Important error: storemultiinfo file';
$string['setsetst:manage'] = 'Manage user storemultiinfos manually';
$string['setsetst:unstoreinfo'] = 'Unstoreinfo users from the course manually';
$string['location'] = 'File location';
$string['location_desc'] = 'Specify full path to the storemultiinfo file. The file is automatically deleted after processing.';
$string['notifyadmin'] = 'Notify administrator';
$string['notifystoreinfoled'] = 'Notify storeinfoled users';
$string['notifystoreinfoler'] = 'Notify user responsible for storemultiinfos';
$string['messageprovider:setsetst_storemultiinfo'] = 'setsetst storemultiinfo messages';
$string['mapping'] = 'Flat file role mapping';
$string['pluginname'] = 'multi store settings i think';
$string['pluginname_desc'] = 'This method will repeatedly check for and process a specially-formatted text file in the location that you specify.
The file is a comma separated file assumed to have four or six fields per line:

    operation, role, user idnumber, course idnumber [, starttime [, endtime]]

where:

* operation - add | del
* role - student | teacher | teacheredit
* user idnumber - idnumber in the user table NB not id
* course idnumber - idnumber in the course table NB not id
* starttime - start time (in seconds since epoch) - optional
* endtime - end time (in seconds since epoch) - optional

It could look something like this:
<pre class="informationbox">
   add, student, 5, CF101
   add, teacher, 6, CF101
   add, teacheredit, 7, CF101
   del, student, 8, CF101
   del, student, 17, CF101
   add, student, 21, CF101, 1091115000, 1091215000
</pre>';
