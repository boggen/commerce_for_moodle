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
 * this is a copy of moodle/lang/en/shipping.php modified for shipping (shippings)
 *  * @copyright  Ryan Sanders
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */ 
/**
 * Strings for component 'core_shipping', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    core_shipping
 * @subpackage shipping
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['actshippingshhdr'] = 'Available course shipping plugins';
$string['addinstance'] = 'Add method';
$string['ajaxoneuserfound'] = '1 user found';
$string['ajaxxusersfound'] = '{$a} users found';
$string['ajaxnext25'] = 'Next 25...';
$string['assignnotpermitted'] = 'You do not have permission or can not assign roles in this course.';
$string['bulkuseroperation'] = 'Bulk user operation';
$string['configshippingplugins'] = 'Please select all required plugins and arrange then in appropriate order.';
$string['custominstancename'] = 'Custom instance name';
$string['defaultshipping'] = 'Add instance to new courses';
$string['defaultshipping_desc'] = 'It is possible to add this plugin to all new courses by default.';
$string['deleteinstanceconfirm'] = 'You are about to delete the shipping method "{$a->name}". All {$a->users} users currently shippingled using this method will be unshippingled and any course-related data such as users\' grades, group membership or forum subscriptions will be deleted.

Are you sure you want to continue?';
$string['deleteinstanceconfirmself'] = 'Are you really sure you want to delete instance "{$a->name}" that gives you access to this course? It is possible that you will not be able to access this course if you continue.';
$string['deleteinstancenousersconfirm'] = 'You are about to delete the payment gatway method "{$a->name}". Are you sure you want to continue?';
$string['disableinstanceconfirmself'] = 'Are you really sure you want to disable instance "{$a->name}" that gives you access to this course? It is possible that you will not be able to access this course if you continue.';
$string['durationdays'] = '{$a} days';
$string['editshipment'] = 'Edit payment gateawy';
$string['shipping'] = 'shippings';
$string['shippingcandidates'] = 'Not shippingled users';
$string['shippingcandidatesmatching'] = 'Matching not shippingled users';
$string['shippingcohort'] = 'shipping cohort';
$string['shippingcohortusers'] = 'shipping users';
$string['shippinglednewusers'] = 'Successfully shippingled {$a} new users';
$string['shippingledusers'] = 'shippingled users';
$string['shippingledusersmatching'] = 'Matching shippingled users';
$string['shippingme'] = 'shipping me in this course';
$string['shipmentinstances'] = 'shipping methods';
$string['shipmentnew'] = 'New shipping in {$a}';
$string['shipmentnewuser'] = '{$a->user} has shippingled in course "{$a->course}"';
$string['shipments'] = 'shippings';
$string['shipmentoptions'] = 'shipping options';
$string['shippingnotpermitted'] = 'You do not have permission or are not allowed to shipping someone in this course';
$string['shippingperiod'] = 'shipping duration';
$string['shippingusage'] = 'Instances / shipments';
$string['shippingusers'] = 'shipping users';
$string['shippingtimecreated'] = 'shipping created';
$string['shippingtimeend'] = 'shipping ends';
$string['shippingtimestart'] = 'shipping starts';
$string['errajaxfailedshipping'] = 'Failed to shipping user';
$string['errajaxsearch'] = 'Error when searching users';
$string['erroreditshipment'] = 'An error occurred while trying to edit a users shipping';
$string['errorshippingcohort'] = 'Error creating cohort sync shipping instance in this course.';
$string['errorshippingcohortusers'] = 'Error shippingling cohort members in this course.';
$string['errorthresholdlow'] = 'Notification threshold must be at least 1 day.';
$string['errorwithbulkoperation'] = 'There was an error while processing your bulk shipping change.';
$string['eventusershipmentcreated'] = 'User shippingled in course';
$string['eventusershipmentdeleted'] = 'User unshippingled from course';
$string['eventusershipmentupdated'] = 'User unshipping updated';
$string['expirynotify'] = 'Notify before shipping expires';
$string['expirynotify_help'] = 'This setting determines whether shipping expiry notification messages are sent.';
$string['expirynotifyall'] = 'shippingler and shippingled user';
$string['expirynotifyshippingler'] = 'shippingler only';
$string['expirynotifyhour'] = 'Hour to send shipping expiry notifications';
$string['expirythreshold'] = 'Notification threshold';
$string['expirythreshold_help'] = 'How long before shipping expiry should users be notified?';
$string['finishshippinglingusers'] = 'Finish shippingling users';
$string['instanceeditselfwarning'] = 'Warning:';
$string['instanceeditselfwarningtext'] = 'You are shippingled into this course through this shipping method, changes may affect your access to this course.';
$string['invalidshippinginstance'] = 'Invalid shipping instance';
$string['invalidrole'] = 'Invalid role';
//is used
$string['manageshippings'] = 'Manage shipping plugins';
$string['manageinstance'] = 'Manage';
$string['migratetomanual'] = 'Migrate to manual shipments';
$string['nochange'] = 'No change';
$string['noexistingparticipants'] = 'No existing participants';
$string['noguestaccess'] = 'Guests can not access this course, please try to log in.';
$string['none'] = 'None';
$string['notshippinglable'] = 'You can not shipping yourself in this course.';
$string['notshippingledusers'] = 'Other users';
$string['otheruserdesc'] = 'The following users are not shippingled in this course but do have roles, inherited or assigned within it.';
$string['participationactive'] = 'Active';
$string['participationstatus'] = 'Status';
$string['participationsuspended'] = 'Suspended';
$string['periodend'] = 'until {$a}';
$string['periodnone'] = 'shippingled {$a}';
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
$string['testsettingsheading'] = 'Test shipping settings - {$a}';
$string['totalshippingledusers'] = '{$a} shippingled users';
$string['totalotherusers'] = '{$a} other users';
$string['unassignnotpermitted'] = 'You do not have permission to unassign roles in this course';
$string['unshipping'] = 'Unshipping';
$string['unshippingconfirm'] = 'Do you really want to unshipping user "{$a->user}" from course "{$a->course}"?';
$string['unshippingme'] = 'Unshipping me from {$a}';
$string['unshippingnotpermitted'] = 'You do not have permission or can not unshipping this user from this course.';
$string['unshippingroleusers'] = 'Unshipping users';
$string['uninstallmigrating'] = 'Migrating "{$a}" shipments';
$string['unknowajaxaction'] = 'Unknown action requested';
$string['unlimitedduration'] = 'Unlimited';
$string['usersearch'] = 'Search ';
$string['withselectedusers'] = 'With selected users';
$string['extremovedaction'] = 'External unshipping action';
$string['extremovedaction_help'] = 'Select action to carry out when user shipping disappears from external shipping source. Please note that some user data and settings are purged from course during course unshipping.';
$string['extremovedsuspend'] = 'Disable course shipping';
$string['extremovedsuspendnoroles'] = 'Disable course shipping and remove roles';
$string['extremovedkeep'] = 'Keep user shippingled';
$string['extremovedunshipping'] = 'Unshipping user from course';
