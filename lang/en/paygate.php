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
 * this is a copy of moodle/lang/en/paygate.php modified for paygate (payment gateways)
 *  * @copyright  Ryan Sanders
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */ 
/**
 * Strings for component 'core_paygate', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    core_paygate
 * @subpackage paygate
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['actpaygateshhdr'] = 'Available course payment gateway plugins';
$string['addinstance'] = 'Add method';
$string['ajaxoneuserfound'] = '1 user found';
$string['ajaxxusersfound'] = '{$a} users found';
$string['ajaxnext25'] = 'Next 25...';
$string['assignnotpermitted'] = 'You do not have permission or can not assign roles in this course.';
$string['bulkuseroperation'] = 'Bulk user operation';
$string['configpaygateplugins'] = 'Please select all required plugins and arrange then in appropriate order.';
$string['custominstancename'] = 'Custom instance name';
$string['defaultpaygate'] = 'Add instance to new courses';
$string['defaultpaygate_desc'] = 'It is possible to add this plugin to all new courses by default.';
$string['deleteinstanceconfirm'] = 'You are about to delete the payment gateway method "{$a->name}". All {$a->users} users currently paygateled using this method will be unpaygateled and any course-related data such as users\' grades, group membership or forum subscriptions will be deleted.

Are you sure you want to continue?';
$string['deleteinstanceconfirmself'] = 'Are you really sure you want to delete instance "{$a->name}" that gives you access to this course? It is possible that you will not be able to access this course if you continue.';
$string['deleteinstancenousersconfirm'] = 'You are about to delete the payment gatway method "{$a->name}". Are you sure you want to continue?';
$string['disableinstanceconfirmself'] = 'Are you really sure you want to disable instance "{$a->name}" that gives you access to this course? It is possible that you will not be able to access this course if you continue.';
$string['durationdays'] = '{$a} days';
$string['editpaymentgateway'] = 'Edit payment gateawy';
$string['paygate'] = 'Payment Gateways';
$string['paygatecandidates'] = 'Not paygateled users';
$string['paygatecandidatesmatching'] = 'Matching not paygateled users';
$string['paygatecohort'] = 'paygate cohort';
$string['paygatecohortusers'] = 'paygate users';
$string['paygatelednewusers'] = 'Successfully paygateled {$a} new users';
$string['paygateledusers'] = 'paygateled users';
$string['paygateledusersmatching'] = 'Matching paygateled users';
$string['paygateme'] = 'paygate me in this course';
$string['paymentgatewayinstances'] = 'payment gateway methods';
$string['paymentgatewaynew'] = 'New payment gateway in {$a}';
$string['paymentgatewaynewuser'] = '{$a->user} has paygateled in course "{$a->course}"';
$string['paymentgateways'] = 'payment gateways';
$string['paymentgatewayoptions'] = 'payment gateway options';
$string['paygatenotpermitted'] = 'You do not have permission or are not allowed to paygate someone in this course';
$string['paygateperiod'] = 'payment gateway duration';
$string['paygateusage'] = 'Instances / paymentgateways';
$string['paygateusers'] = 'paygate users';
$string['paygatetimecreated'] = 'payment gateway created';
$string['paygatetimeend'] = 'payment gateway ends';
$string['paygatetimestart'] = 'payment gateway starts';
$string['errajaxfailedpaygate'] = 'Failed to paygate user';
$string['errajaxsearch'] = 'Error when searching users';
$string['erroreditpaymentgateway'] = 'An error occurred while trying to edit a users payment gateway';
$string['errorpaygatecohort'] = 'Error creating cohort sync payment gateway instance in this course.';
$string['errorpaygatecohortusers'] = 'Error paygateling cohort members in this course.';
$string['errorthresholdlow'] = 'Notification threshold must be at least 1 day.';
$string['errorwithbulkoperation'] = 'There was an error while processing your bulk payment gateway change.';
$string['eventuserpaymentgatewaycreated'] = 'User paygateled in course';
$string['eventuserpaymentgatewaydeleted'] = 'User unpaygateled from course';
$string['eventuserpaymentgatewayupdated'] = 'User unpayment gateway updated';
$string['expirynotify'] = 'Notify before payment gateway expires';
$string['expirynotify_help'] = 'This setting determines whether payment gateway expiry notification messages are sent.';
$string['expirynotifyall'] = 'paygateler and paygateled user';
$string['expirynotifypaygateler'] = 'paygateler only';
$string['expirynotifyhour'] = 'Hour to send payment gateway expiry notifications';
$string['expirythreshold'] = 'Notification threshold';
$string['expirythreshold_help'] = 'How long before payment gateway expiry should users be notified?';
$string['finishpaygatelingusers'] = 'Finish paygateling users';
$string['instanceeditselfwarning'] = 'Warning:';
$string['instanceeditselfwarningtext'] = 'You are paygateled into this course through this payment gateway method, changes may affect your access to this course.';
$string['invalidpaygateinstance'] = 'Invalid payment gateway instance';
$string['invalidrole'] = 'Invalid role';
//is used
$string['managepaygates'] = 'Manage payment gateway plugins';
$string['manageinstance'] = 'Manage';
$string['migratetomanual'] = 'Migrate to manual paymentgateways';
$string['nochange'] = 'No change';
$string['noexistingparticipants'] = 'No existing participants';
$string['noguestaccess'] = 'Guests can not access this course, please try to log in.';
$string['none'] = 'None';
$string['notpaygatelable'] = 'You can not paygate yourself in this course.';
$string['notpaygateledusers'] = 'Other users';
$string['otheruserdesc'] = 'The following users are not paygateled in this course but do have roles, inherited or assigned within it.';
$string['participationactive'] = 'Active';
$string['participationstatus'] = 'Status';
$string['participationsuspended'] = 'Suspended';
$string['periodend'] = 'until {$a}';
$string['periodnone'] = 'paygateled {$a}';
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
$string['testsettingsheading'] = 'Test paygate settings - {$a}';
$string['totalpaygateledusers'] = '{$a} paygateled users';
$string['totalotherusers'] = '{$a} other users';
$string['unassignnotpermitted'] = 'You do not have permission to unassign roles in this course';
$string['unpaygate'] = 'Unpaygate';
$string['unpaygateconfirm'] = 'Do you really want to unpaygate user "{$a->user}" from course "{$a->course}"?';
$string['unpaygateme'] = 'Unpaygate me from {$a}';
$string['unpaygatenotpermitted'] = 'You do not have permission or can not unpaygate this user from this course.';
$string['unpaygateroleusers'] = 'Unpaygate users';
$string['uninstallmigrating'] = 'Migrating "{$a}" paymentgateways';
$string['unknowajaxaction'] = 'Unknown action requested';
$string['unlimitedduration'] = 'Unlimited';
$string['usersearch'] = 'Search ';
$string['withselectedusers'] = 'With selected users';
$string['extremovedaction'] = 'External unpaygate action';
$string['extremovedaction_help'] = 'Select action to carry out when user payment gateway disappears from external payment gateway source. Please note that some user data and settings are purged from course during course unpayment gateway.';
$string['extremovedsuspend'] = 'Disable course payment gateway';
$string['extremovedsuspendnoroles'] = 'Disable course payment gateway and remove roles';
$string['extremovedkeep'] = 'Keep user paygateled';
$string['extremovedunpaygate'] = 'Unpaygate user from course';
