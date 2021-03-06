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
 * klarnafee ordertotals plugin settings and presets.
 *
 * @package    ordertot_klarnafee
 * @copyright  2010 Eugene Venter
 * @author     Eugene Venter - based on code by Petr Skoda and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/adminlib.php');

if ($ADMIN->fulltree) {

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('ordertot_klarnafee_settings', '', get_string('pluginname_desc', 'ordertot_klarnafee')));

    //$settings->add(new admin_setting_configfile('ordertot_klarnafee/location', get_string('location', 'ordertot_klarnafee'), get_string('location_desc', 'ordertot_klarnafee'), ''));

    $options = core_text::get_encodings();
    $settings->add(new admin_setting_configselect('ordertot_klarnafee/encoding', get_string('encoding', 'ordertot_klarnafee'), '', 'UTF-8', $options));

    $settings->add(new admin_setting_configcheckbox('ordertot_klarnafee/mailstudents', get_string('notifyordertotled', 'ordertot_klarnafee'), '', 0));

    $settings->add(new admin_setting_configcheckbox('ordertot_klarnafee/mailteachers', get_string('notifyordertotler', 'ordertot_klarnafee'), '', 0));

    $settings->add(new admin_setting_configcheckbox('ordertot_klarnafee/mailadmins', get_string('notifyadmin', 'ordertot_klarnafee'), '', 0));

/*
    $options = array(ordertot_EXT_REMOVED_UNordertot        => get_string('extremovedunordertot', 'ordertot'),
                     ordertot_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'ordertot'),
                     ordertot_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'ordertot'));
    $settings->add(new admin_setting_configselect('ordertot_klarnafee/unordertotaction', get_string('extremovedaction', 'ordertot'), get_string('extremovedaction_help', 'ordertot'), ordertot_EXT_REMOVED_SUSPENDNOROLES, $options));
*/
    // Note: let's reuse the ext sync constants and strings here, internally it is very similar,
    //       it describes what should happen when users are not supposed to be ordertotled any more.
/* 
	$options = array(
        ordertot_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'ordertot'),
        ordertot_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'ordertot'),
        ordertot_EXT_REMOVED_UNordertot        => get_string('extremovedunordertot', 'ordertot'),
    );

    $settings->add(new admin_setting_configselect('ordertot_klarnafee/expiredaction', get_string('expiredaction', 'ordertot_klarnafee'), get_string('expiredaction_help', 'ordertot_klarnafee'), ordertot_EXT_REMOVED_SUSPENDNOROLES, $options));
*/
    //--- mapping -------------------------------------------------------------------------------------------
/*
    if (!during_initial_install()) {
        $settings->add(new admin_setting_heading('ordertot_klarnafee_mapping', get_string('mapping', 'ordertot_klarnafee'), ''));

        $roles = role_fix_names(get_all_roles());

        foreach ($roles as $role) {
            $settings->add(new ordertot_klarnafee_role_setting($role));
        }
        unset($roles);
    }
*/
}
