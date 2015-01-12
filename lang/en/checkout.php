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
 * this is a copy of moodle/lang/en/checkout.php modified for checkout (checkouts)
 *  * @copyright  Ryan Sanders
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */ 
/**
 * Strings for component 'core_checkout', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    core_checkout
 * @subpackage checkout
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['actcheckoutshhdr'] = 'Available course checkout plugins';
$string['addinstance'] = 'Add method';
$string['ajaxoneuserfound'] = '1 user found';
$string['ajaxxusersfound'] = '{$a} users found';
$string['ajaxnext25'] = 'Next 25...';
$string['assignnotpermitted'] = 'You do not have permission or can not assign roles in this course.';
$string['bulkuseroperation'] = 'Bulk user operation';
$string['configcheckoutplugins'] = 'Please select all required plugins and arrange then in appropriate order.';
$string['custominstancename'] = 'Custom instance name';
$string['defaultcheckout'] = 'Add instance to new courses';
$string['defaultcheckout_desc'] = 'It is possible to add this plugin to all new courses by default.';
$string['deleteinstanceconfirm'] = 'You are about to delete the checkout method "{$a->name}". All {$a->users} users currently checkoutled using this method will be uncheckoutled and any course-related data such as users\' grades, group membership or forum subscriptions will be deleted.

Are you sure you want to continue?';
$string['deleteinstanceconfirmself'] = 'Are you really sure you want to delete instance "{$a->name}" that gives you access to this course? It is possible that you will not be able to access this course if you continue.';
$string['deleteinstancenousersconfirm'] = 'You are about to delete the payment gatway method "{$a->name}". Are you sure you want to continue?';
$string['disableinstanceconfirmself'] = 'Are you really sure you want to disable instance "{$a->name}" that gives you access to this course? It is possible that you will not be able to access this course if you continue.';
$string['durationdays'] = '{$a} days';
$string['editcheckingout'] = 'Edit payment gateawy';
$string['checkout'] = 'checkouts';
$string['checkoutcandidates'] = 'Not checkoutled users';
$string['checkoutcandidatesmatching'] = 'Matching not checkoutled users';
$string['checkoutcohort'] = 'checkout cohort';
$string['checkoutcohortusers'] = 'checkout users';
$string['checkoutlednewusers'] = 'Successfully checkoutled {$a} new users';
$string['checkoutledusers'] = 'checkoutled users';
$string['checkoutledusersmatching'] = 'Matching checkoutled users';
$string['checkoutme'] = 'checkout me in this course';
$string['checkingoutinstances'] = 'checkout methods';
$string['checkingoutnew'] = 'New checkout in {$a}';
$string['checkingoutnewuser'] = '{$a->user} has checkoutled in course "{$a->course}"';
$string['checkingouts'] = 'checkouts';
$string['checkingoutoptions'] = 'checkout options';
$string['checkoutnotpermitted'] = 'You do not have permission or are not allowed to checkout someone in this course';
$string['checkoutperiod'] = 'checkout duration';
$string['checkoutusage'] = 'Instances / checkingouts';
$string['checkoutusers'] = 'checkout users';
$string['checkouttimecreated'] = 'checkout created';
$string['checkouttimeend'] = 'checkout ends';
$string['checkouttimestart'] = 'checkout starts';
$string['errajaxfailedcheckout'] = 'Failed to checkout user';
$string['errajaxsearch'] = 'Error when searching users';
$string['erroreditcheckingout'] = 'An error occurred while trying to edit a users checkout';
$string['errorcheckoutcohort'] = 'Error creating cohort sync checkout instance in this course.';
$string['errorcheckoutcohortusers'] = 'Error checkoutling cohort members in this course.';
$string['errorthresholdlow'] = 'Notification threshold must be at least 1 day.';
$string['errorwithbulkoperation'] = 'There was an error while processing your bulk checkout change.';
$string['eventusercheckingoutcreated'] = 'User checkoutled in course';
$string['eventusercheckingoutdeleted'] = 'User uncheckoutled from course';
$string['eventusercheckingoutupdated'] = 'User uncheckout updated';
$string['expirynotify'] = 'Notify before checkout expires';
$string['expirynotify_help'] = 'This setting determines whether checkout expiry notification messages are sent.';
$string['expirynotifyall'] = 'checkoutler and checkoutled user';
$string['expirynotifycheckoutler'] = 'checkoutler only';
$string['expirynotifyhour'] = 'Hour to send checkout expiry notifications';
$string['expirythreshold'] = 'Notification threshold';
$string['expirythreshold_help'] = 'How long before checkout expiry should users be notified?';
$string['finishcheckoutlingusers'] = 'Finish checkoutling users';
$string['instanceeditselfwarning'] = 'Warning:';
$string['instanceeditselfwarningtext'] = 'You are checkoutled into this course through this checkout method, changes may affect your access to this course.';
$string['invalidcheckoutinstance'] = 'Invalid checkout instance';
$string['invalidrole'] = 'Invalid role';
//is used
$string['managecheckouts'] = 'Manage checkout plugins';
$string['manageinstance'] = 'Manage';
$string['migratetomanual'] = 'Migrate to manual checkingouts';
$string['nochange'] = 'No change';
$string['noexistingparticipants'] = 'No existing participants';
$string['noguestaccess'] = 'Guests can not access this course, please try to log in.';
$string['none'] = 'None';
$string['notcheckoutlable'] = 'You can not checkout yourself in this course.';
$string['notcheckoutledusers'] = 'Other users';
$string['otheruserdesc'] = 'The following users are not checkoutled in this course but do have roles, inherited or assigned within it.';
$string['participationactive'] = 'Active';
$string['participationstatus'] = 'Status';
$string['participationsuspended'] = 'Suspended';
$string['periodend'] = 'until {$a}';
$string['periodnone'] = 'checkoutled {$a}';
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
$string['testsettingsheading'] = 'Test checkout settings - {$a}';
$string['totalcheckoutledusers'] = '{$a} checkoutled users';
$string['totalotherusers'] = '{$a} other users';
$string['unassignnotpermitted'] = 'You do not have permission to unassign roles in this course';
$string['uncheckout'] = 'Uncheckout';
$string['uncheckoutconfirm'] = 'Do you really want to uncheckout user "{$a->user}" from course "{$a->course}"?';
$string['uncheckoutme'] = 'Uncheckout me from {$a}';
$string['uncheckoutnotpermitted'] = 'You do not have permission or can not uncheckout this user from this course.';
$string['uncheckoutroleusers'] = 'Uncheckout users';
$string['uninstallmigrating'] = 'Migrating "{$a}" checkingouts';
$string['unknowajaxaction'] = 'Unknown action requested';
$string['unlimitedduration'] = 'Unlimited';
$string['usersearch'] = 'Search ';
$string['withselectedusers'] = 'With selected users';
$string['extremovedaction'] = 'External uncheckout action';
$string['extremovedaction_help'] = 'Select action to carry out when user checkout disappears from external checkout source. Please note that some user data and settings are purged from course during course uncheckout.';
$string['extremovedsuspend'] = 'Disable course checkout';
$string['extremovedsuspendnoroles'] = 'Disable course checkout and remove roles';
$string['extremovedkeep'] = 'Keep user checkoutled';
$string['extremoveduncheckout'] = 'Uncheckout user from course';
