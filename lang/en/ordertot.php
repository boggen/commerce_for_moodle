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
 * this is a copy of moodle/lang/en/ordertot.php modified for ordertot (order totals)
 *  * @copyright  Ryan Sanders
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */ 
/**
 * Strings for component 'core_ordertot', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    core_ordertot
 * @subpackage ordertot
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['actordertotshhdr'] = 'Available course order total plugins';
$string['addinstance'] = 'Add method';
$string['ajaxoneuserfound'] = '1 user found';
$string['ajaxxusersfound'] = '{$a} users found';
$string['ajaxnext25'] = 'Next 25...';
$string['assignnotpermitted'] = 'You do not have permission or can not assign roles in this course.';
$string['bulkuseroperation'] = 'Bulk user operation';
$string['configordertotplugins'] = 'Please select all required plugins and arrange then in appropriate order.';
$string['custominstancename'] = 'Custom instance name';
$string['defaultordertot'] = 'Add instance to new courses';
$string['defaultordertot_desc'] = 'It is possible to add this plugin to all new courses by default.';
$string['deleteinstanceconfirm'] = 'You are about to delete the order total method "{$a->name}". All {$a->users} users currently ordertotled using this method will be unordertotled and any course-related data such as users\' grades, group membership or forum subscriptions will be deleted.

Are you sure you want to continue?';
$string['deleteinstanceconfirmself'] = 'Are you really sure you want to delete instance "{$a->name}" that gives you access to this course? It is possible that you will not be able to access this course if you continue.';
$string['deleteinstancenousersconfirm'] = 'You are about to delete the payment gatway method "{$a->name}". Are you sure you want to continue?';
$string['disableinstanceconfirmself'] = 'Are you really sure you want to disable instance "{$a->name}" that gives you access to this course? It is possible that you will not be able to access this course if you continue.';
$string['durationdays'] = '{$a} days';
$string['editordertotal'] = 'Edit payment gateawy';
$string['ordertot'] = 'order totals';
$string['ordertotcandidates'] = 'Not ordertotled users';
$string['ordertotcandidatesmatching'] = 'Matching not ordertotled users';
$string['ordertotcohort'] = 'ordertot cohort';
$string['ordertotcohortusers'] = 'ordertot users';
$string['ordertotlednewusers'] = 'Successfully ordertotled {$a} new users';
$string['ordertotledusers'] = 'ordertotled users';
$string['ordertotledusersmatching'] = 'Matching ordertotled users';
$string['ordertotme'] = 'ordertot me in this course';
$string['ordertotalinstances'] = 'order total methods';
$string['ordertotalnew'] = 'New order total in {$a}';
$string['ordertotalnewuser'] = '{$a->user} has ordertotled in course "{$a->course}"';
$string['ordertotals'] = 'order totals';
$string['ordertotaloptions'] = 'order total options';
$string['ordertotnotpermitted'] = 'You do not have permission or are not allowed to ordertot someone in this course';
$string['ordertotperiod'] = 'order total duration';
$string['ordertotusage'] = 'Instances / ordertotals';
$string['ordertotusers'] = 'ordertot users';
$string['ordertottimecreated'] = 'order total created';
$string['ordertottimeend'] = 'order total ends';
$string['ordertottimestart'] = 'order total starts';
$string['errajaxfailedordertot'] = 'Failed to ordertot user';
$string['errajaxsearch'] = 'Error when searching users';
$string['erroreditordertotal'] = 'An error occurred while trying to edit a users order total';
$string['errorordertotcohort'] = 'Error creating cohort sync order total instance in this course.';
$string['errorordertotcohortusers'] = 'Error ordertotling cohort members in this course.';
$string['errorthresholdlow'] = 'Notification threshold must be at least 1 day.';
$string['errorwithbulkoperation'] = 'There was an error while processing your bulk order total change.';
$string['eventuserordertotalcreated'] = 'User ordertotled in course';
$string['eventuserordertotaldeleted'] = 'User unordertotled from course';
$string['eventuserordertotalupdated'] = 'User unorder total updated';
$string['expirynotify'] = 'Notify before order total expires';
$string['expirynotify_help'] = 'This setting determines whether order total expiry notification messages are sent.';
$string['expirynotifyall'] = 'ordertotler and ordertotled user';
$string['expirynotifyordertotler'] = 'ordertotler only';
$string['expirynotifyhour'] = 'Hour to send order total expiry notifications';
$string['expirythreshold'] = 'Notification threshold';
$string['expirythreshold_help'] = 'How long before order total expiry should users be notified?';
$string['finishordertotlingusers'] = 'Finish ordertotling users';
$string['instanceeditselfwarning'] = 'Warning:';
$string['instanceeditselfwarningtext'] = 'You are ordertotled into this course through this order total method, changes may affect your access to this course.';
$string['invalidordertotinstance'] = 'Invalid order total instance';
$string['invalidrole'] = 'Invalid role';
//is used
$string['manageordertots'] = 'Manage order total plugins';
$string['manageinstance'] = 'Manage';
$string['migratetomanual'] = 'Migrate to manual ordertotals';
$string['nochange'] = 'No change';
$string['noexistingparticipants'] = 'No existing participants';
$string['noguestaccess'] = 'Guests can not access this course, please try to log in.';
$string['none'] = 'None';
$string['notordertotlable'] = 'You can not ordertot yourself in this course.';
$string['notordertotledusers'] = 'Other users';
$string['otheruserdesc'] = 'The following users are not ordertotled in this course but do have roles, inherited or assigned within it.';
$string['participationactive'] = 'Active';
$string['participationstatus'] = 'Status';
$string['participationsuspended'] = 'Suspended';
$string['periodend'] = 'until {$a}';
$string['periodnone'] = 'ordertotled {$a}';
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
$string['testsettingsheading'] = 'Test ordertot settings - {$a}';
$string['totalordertotledusers'] = '{$a} ordertotled users';
$string['totalotherusers'] = '{$a} other users';
$string['unassignnotpermitted'] = 'You do not have permission to unassign roles in this course';
$string['unordertot'] = 'Unordertot';
$string['unordertotconfirm'] = 'Do you really want to unordertot user "{$a->user}" from course "{$a->course}"?';
$string['unordertotme'] = 'Unordertot me from {$a}';
$string['unordertotnotpermitted'] = 'You do not have permission or can not unordertot this user from this course.';
$string['unordertotroleusers'] = 'Unordertot users';
$string['uninstallmigrating'] = 'Migrating "{$a}" ordertotals';
$string['unknowajaxaction'] = 'Unknown action requested';
$string['unlimitedduration'] = 'Unlimited';
$string['usersearch'] = 'Search ';
$string['withselectedusers'] = 'With selected users';
$string['extremovedaction'] = 'External unordertot action';
$string['extremovedaction_help'] = 'Select action to carry out when user order total disappears from external order total source. Please note that some user data and settings are purged from course during course unorder total.';
$string['extremovedsuspend'] = 'Disable course order total';
$string['extremovedsuspendnoroles'] = 'Disable course order total and remove roles';
$string['extremovedkeep'] = 'Keep user ordertotled';
$string['extremovedunordertot'] = 'Unordertot user from course';
