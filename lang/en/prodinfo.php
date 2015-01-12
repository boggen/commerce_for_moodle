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
 * this is a copy of moodle/lang/en/prodinfo.php modified for prodinfo (product informations)
 *  * @copyright  Ryan Sanders
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */ 
/**
 * Strings for component 'core_prodinfo', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    core_prodinfo
 * @subpackage prodinfo
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['actprodinfoshhdr'] = 'Available course product info plugins';
$string['addinstance'] = 'Add method';
$string['ajaxoneuserfound'] = '1 user found';
$string['ajaxxusersfound'] = '{$a} users found';
$string['ajaxnext25'] = 'Next 25...';
$string['assignnotpermitted'] = 'You do not have permission or can not assign roles in this course.';
$string['bulkuseroperation'] = 'Bulk user operation';
$string['configprodinfoplugins'] = 'Please select all required plugins and arrange then in appropriate order.';
$string['custominstancename'] = 'Custom instance name';
$string['defaultprodinfo'] = 'Add instance to new courses';
$string['defaultprodinfo_desc'] = 'It is possible to add this plugin to all new courses by default.';
$string['deleteinstanceconfirm'] = 'You are about to delete the product info method "{$a->name}". All {$a->users} users currently prodinfoled using this method will be unprodinfoled and any course-related data such as users\' grades, group membership or forum subscriptions will be deleted.

Are you sure you want to continue?';
$string['deleteinstanceconfirmself'] = 'Are you really sure you want to delete instance "{$a->name}" that gives you access to this course? It is possible that you will not be able to access this course if you continue.';
$string['deleteinstancenousersconfirm'] = 'You are about to delete the payment gatway method "{$a->name}". Are you sure you want to continue?';
$string['disableinstanceconfirmself'] = 'Are you really sure you want to disable instance "{$a->name}" that gives you access to this course? It is possible that you will not be able to access this course if you continue.';
$string['durationdays'] = '{$a} days';
$string['editproductinfo'] = 'Edit payment gateawy';
$string['prodinfo'] = 'product infos';
$string['prodinfocandidates'] = 'Not prodinfoled users';
$string['prodinfocandidatesmatching'] = 'Matching not prodinfoled users';
$string['prodinfocohort'] = 'prodinfo cohort';
$string['prodinfocohortusers'] = 'prodinfo users';
$string['prodinfolednewusers'] = 'Successfully prodinfoled {$a} new users';
$string['prodinfoledusers'] = 'prodinfoled users';
$string['prodinfoledusersmatching'] = 'Matching prodinfoled users';
$string['prodinfome'] = 'prodinfo me in this course';
$string['productinfoinstances'] = 'product info methods';
$string['productinfonew'] = 'New product info in {$a}';
$string['productinfonewuser'] = '{$a->user} has prodinfoled in course "{$a->course}"';
$string['productinfos'] = 'product infos';
$string['productinfooptions'] = 'product info options';
$string['prodinfonotpermitted'] = 'You do not have permission or are not allowed to prodinfo someone in this course';
$string['prodinfoperiod'] = 'product info duration';
$string['prodinfousage'] = 'Instances / productinfos';
$string['prodinfousers'] = 'prodinfo users';
$string['prodinfotimecreated'] = 'product info created';
$string['prodinfotimeend'] = 'product info ends';
$string['prodinfotimestart'] = 'product info starts';
$string['errajaxfailedprodinfo'] = 'Failed to prodinfo user';
$string['errajaxsearch'] = 'Error when searching users';
$string['erroreditproductinfo'] = 'An error occurred while trying to edit a users product info';
$string['errorprodinfocohort'] = 'Error creating cohort sync product info instance in this course.';
$string['errorprodinfocohortusers'] = 'Error prodinfoling cohort members in this course.';
$string['errorthresholdlow'] = 'Notification threshold must be at least 1 day.';
$string['errorwithbulkoperation'] = 'There was an error while processing your bulk product info change.';
$string['eventuserproductinfocreated'] = 'User prodinfoled in course';
$string['eventuserproductinfodeleted'] = 'User unprodinfoled from course';
$string['eventuserproductinfoupdated'] = 'User unproduct info updated';
$string['expirynotify'] = 'Notify before product info expires';
$string['expirynotify_help'] = 'This setting determines whether product info expiry notification messages are sent.';
$string['expirynotifyall'] = 'prodinfoler and prodinfoled user';
$string['expirynotifyprodinfoler'] = 'prodinfoler only';
$string['expirynotifyhour'] = 'Hour to send product info expiry notifications';
$string['expirythreshold'] = 'Notification threshold';
$string['expirythreshold_help'] = 'How long before product info expiry should users be notified?';
$string['finishprodinfolingusers'] = 'Finish prodinfoling users';
$string['instanceeditselfwarning'] = 'Warning:';
$string['instanceeditselfwarningtext'] = 'You are prodinfoled into this course through this product info method, changes may affect your access to this course.';
$string['invalidprodinfoinstance'] = 'Invalid product info instance';
$string['invalidrole'] = 'Invalid role';
//is used
$string['manageprodinfos'] = 'Manage product info plugins';
$string['manageinstance'] = 'Manage';
$string['migratetomanual'] = 'Migrate to manual productinfos';
$string['nochange'] = 'No change';
$string['noexistingparticipants'] = 'No existing participants';
$string['noguestaccess'] = 'Guests can not access this course, please try to log in.';
$string['none'] = 'None';
$string['notprodinfolable'] = 'You can not prodinfo yourself in this course.';
$string['notprodinfoledusers'] = 'Other users';
$string['otheruserdesc'] = 'The following users are not prodinfoled in this course but do have roles, inherited or assigned within it.';
$string['participationactive'] = 'Active';
$string['participationstatus'] = 'Status';
$string['participationsuspended'] = 'Suspended';
$string['periodend'] = 'until {$a}';
$string['periodnone'] = 'prodinfoled {$a}';
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
$string['testsettingsheading'] = 'Test prodinfo settings - {$a}';
$string['totalprodinfoledusers'] = '{$a} prodinfoled users';
$string['totalotherusers'] = '{$a} other users';
$string['unassignnotpermitted'] = 'You do not have permission to unassign roles in this course';
$string['unprodinfo'] = 'Unprodinfo';
$string['unprodinfoconfirm'] = 'Do you really want to unprodinfo user "{$a->user}" from course "{$a->course}"?';
$string['unprodinfome'] = 'Unprodinfo me from {$a}';
$string['unprodinfonotpermitted'] = 'You do not have permission or can not unprodinfo this user from this course.';
$string['unprodinforoleusers'] = 'Unprodinfo users';
$string['uninstallmigrating'] = 'Migrating "{$a}" productinfos';
$string['unknowajaxaction'] = 'Unknown action requested';
$string['unlimitedduration'] = 'Unlimited';
$string['usersearch'] = 'Search ';
$string['withselectedusers'] = 'With selected users';
$string['extremovedaction'] = 'External unprodinfo action';
$string['extremovedaction_help'] = 'Select action to carry out when user product info disappears from external product info source. Please note that some user data and settings are purged from course during course unproduct info.';
$string['extremovedsuspend'] = 'Disable course product info';
$string['extremovedsuspendnoroles'] = 'Disable course product info and remove roles';
$string['extremovedkeep'] = 'Keep user prodinfoled';
$string['extremovedunprodinfo'] = 'Unprodinfo user from course';
