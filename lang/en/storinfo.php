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
 * this is a copy of moodle/lang/en/storinfo.php modified for storinfo (store informations)
 *  * @copyright  Ryan Sanders
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */ 
/**
 * Strings for component 'core_storinfo', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    core_storinfo
 * @subpackage storinfo
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['actstorinfoshhdr'] = 'Available course store information plugins';
$string['addinstance'] = 'Add method';
$string['ajaxoneuserfound'] = '1 user found';
$string['ajaxxusersfound'] = '{$a} users found';
$string['ajaxnext25'] = 'Next 25...';
$string['assignnotpermitted'] = 'You do not have permission or can not assign roles in this course.';
$string['bulkuseroperation'] = 'Bulk user operation';
$string['configstorinfoplugins'] = 'Please select all required plugins and arrange then in appropriate order.';
$string['custominstancename'] = 'Custom instance name';
$string['defaultstorinfo'] = 'Add instance to new courses';
$string['defaultstorinfo_desc'] = 'It is possible to add this plugin to all new courses by default.';
$string['deleteinstanceconfirm'] = 'You are about to delete the store information method "{$a->name}". All {$a->users} users currently storinfoled using this method will be unstorinfoled and any course-related data such as users\' grades, group membership or forum subscriptions will be deleted.

Are you sure you want to continue?';
$string['deleteinstanceconfirmself'] = 'Are you really sure you want to delete instance "{$a->name}" that gives you access to this course? It is possible that you will not be able to access this course if you continue.';
$string['deleteinstancenousersconfirm'] = 'You are about to delete the payment gatway method "{$a->name}". Are you sure you want to continue?';
$string['disableinstanceconfirmself'] = 'Are you really sure you want to disable instance "{$a->name}" that gives you access to this course? It is possible that you will not be able to access this course if you continue.';
$string['durationdays'] = '{$a} days';
$string['editstoremultiinfo'] = 'Edit payment gateawy';
$string['storinfo'] = 'store informations';
$string['storinfocandidates'] = 'Not storinfoled users';
$string['storinfocandidatesmatching'] = 'Matching not storinfoled users';
$string['storinfocohort'] = 'storinfo cohort';
$string['storinfocohortusers'] = 'storinfo users';
$string['storinfolednewusers'] = 'Successfully storinfoled {$a} new users';
$string['storinfoledusers'] = 'storinfoled users';
$string['storinfoledusersmatching'] = 'Matching storinfoled users';
$string['storinfome'] = 'storinfo me in this course';
$string['storemultiinfoinstances'] = 'store information methods';
$string['storemultiinfonew'] = 'New store information in {$a}';
$string['storemultiinfonewuser'] = '{$a->user} has storinfoled in course "{$a->course}"';
$string['storemultiinfos'] = 'store informations';
$string['storemultiinfooptions'] = 'store information options';
$string['storinfonotpermitted'] = 'You do not have permission or are not allowed to storinfo someone in this course';
$string['storinfoperiod'] = 'store information duration';
$string['storinfousage'] = 'Instances / storemultiinfos';
$string['storinfousers'] = 'storinfo users';
$string['storinfotimecreated'] = 'store information created';
$string['storinfotimeend'] = 'store information ends';
$string['storinfotimestart'] = 'store information starts';
$string['errajaxfailedstorinfo'] = 'Failed to storinfo user';
$string['errajaxsearch'] = 'Error when searching users';
$string['erroreditstoremultiinfo'] = 'An error occurred while trying to edit a users store information';
$string['errorstorinfocohort'] = 'Error creating cohort sync store information instance in this course.';
$string['errorstorinfocohortusers'] = 'Error storinfoling cohort members in this course.';
$string['errorthresholdlow'] = 'Notification threshold must be at least 1 day.';
$string['errorwithbulkoperation'] = 'There was an error while processing your bulk store information change.';
$string['eventuserstoremultiinfocreated'] = 'User storinfoled in course';
$string['eventuserstoremultiinfodeleted'] = 'User unstorinfoled from course';
$string['eventuserstoremultiinfoupdated'] = 'User unstore information updated';
$string['expirynotify'] = 'Notify before store information expires';
$string['expirynotify_help'] = 'This setting determines whether store information expiry notification messages are sent.';
$string['expirynotifyall'] = 'storinfoler and storinfoled user';
$string['expirynotifystorinfoler'] = 'storinfoler only';
$string['expirynotifyhour'] = 'Hour to send store information expiry notifications';
$string['expirythreshold'] = 'Notification threshold';
$string['expirythreshold_help'] = 'How long before store information expiry should users be notified?';
$string['finishstorinfolingusers'] = 'Finish storinfoling users';
$string['instanceeditselfwarning'] = 'Warning:';
$string['instanceeditselfwarningtext'] = 'You are storinfoled into this course through this store information method, changes may affect your access to this course.';
$string['invalidstorinfoinstance'] = 'Invalid store information instance';
$string['invalidrole'] = 'Invalid role';
//is used
$string['managestorinfos'] = 'Manage store information plugins';
$string['manageinstance'] = 'Manage';
$string['migratetomanual'] = 'Migrate to manual storemultiinfos';
$string['nochange'] = 'No change';
$string['noexistingparticipants'] = 'No existing participants';
$string['noguestaccess'] = 'Guests can not access this course, please try to log in.';
$string['none'] = 'None';
$string['notstorinfolable'] = 'You can not storinfo yourself in this course.';
$string['notstorinfoledusers'] = 'Other users';
$string['otheruserdesc'] = 'The following users are not storinfoled in this course but do have roles, inherited or assigned within it.';
$string['participationactive'] = 'Active';
$string['participationstatus'] = 'Status';
$string['participationsuspended'] = 'Suspended';
$string['periodend'] = 'until {$a}';
$string['periodnone'] = 'storinfoled {$a}';
$string['periodstart'] = 'from {$a}';
$string['periodstartend'] = 'from {$a->start} until {$a->end}';
$string['recovergrades'] = 'Recover user\'s old grades if possible';
$string['rolefromthiscourse'] = '{$a->role} (Assigned in this course)';
$string['rolefrommetacourse'] = '{$a->role} (Inherited from parent course)';
$string['rolefromcategory'] = '{$a->role} (Inherited from course category)';
$string['rolefromsystem'] = '{$a->role} (Assigned at site level)';
$string['startdatetoday'] = 'Today';
$string['synced'] = 'Synced';
$string['testsettings'] = 'Test settings';
$string['testsettingsheading'] = 'Test storinfo settings - {$a}';
$string['totalstorinfoledusers'] = '{$a} storinfoled users';
$string['totalotherusers'] = '{$a} other users';
$string['unassignnotpermitted'] = 'You do not have permission to unassign roles in this course';
$string['unstorinfo'] = 'Unstorinfo';
$string['unstorinfoconfirm'] = 'Do you really want to unstorinfo user "{$a->user}" from course "{$a->course}"?';
$string['unstorinfome'] = 'Unstorinfo me from {$a}';
$string['unstorinfonotpermitted'] = 'You do not have permission or can not unstorinfo this user from this course.';
$string['unstorinforoleusers'] = 'Unstorinfo users';
$string['uninstallmigrating'] = 'Migrating "{$a}" storemultiinfos';
$string['unknowajaxaction'] = 'Unknown action requested';
$string['unlimitedduration'] = 'Unlimited';
$string['usersearch'] = 'Search ';
$string['withselectedusers'] = 'With selected users';
$string['extremovedaction'] = 'External unstorinfo action';
$string['extremovedaction_help'] = 'Select action to carry out when user store information disappears from external store information source. Please note that some user data and settings are purged from course during course unstore information.';
$string['extremovedsuspend'] = 'Disable course store information';
$string['extremovedsuspendnoroles'] = 'Disable course store information and remove roles';
$string['extremovedkeep'] = 'Keep user storinfoled';
$string['extremovedunstorinfo'] = 'Unstorinfo user from course';
