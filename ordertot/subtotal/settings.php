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
 * subtotal ordertotals plugin settings and presets.
 *
 * @package    ordertot_subtotal
 * @copyright  2010 Eugene Venter
 * @author     Eugene Venter - based on code by Petr Skoda and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/adminlib.php');

if ($ADMIN->fulltree) {

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('ordertot_subtotal_settings', '', get_string('pluginname_desc', 'ordertot_subtotal')));

    //$settings->add(new admin_setting_configfile('ordertot_subtotal/location', get_string('location', 'ordertot_subtotal'), get_string('location_desc', 'ordertot_subtotal'), ''));

    $options = core_text::get_encodings();
    $settings->add(new admin_setting_configselect('ordertot_subtotal/encoding', get_string('encoding', 'ordertot_subtotal'), '', 'UTF-8', $options));

    $settings->add(new admin_setting_configcheckbox('ordertot_subtotal/mailstudents', get_string('notifyordertotled', 'ordertot_subtotal'), '', 0));

    $settings->add(new admin_setting_configcheckbox('ordertot_subtotal/mailteachers', get_string('notifyordertotler', 'ordertot_subtotal'), '', 0));

    $settings->add(new admin_setting_configcheckbox('ordertot_subtotal/mailadmins', get_string('notifyadmin', 'ordertot_subtotal'), '', 0));

/*
    $options = array(ordertot_EXT_REMOVED_UNordertot        => get_string('extremovedunordertot', 'ordertot'),
                     ordertot_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'ordertot'),
                     ordertot_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'ordertot'));
    $settings->add(new admin_setting_configselect('ordertot_subtotal/unordertotaction', get_string('extremovedaction', 'ordertot'), get_string('extremovedaction_help', 'ordertot'), ordertot_EXT_REMOVED_SUSPENDNOROLES, $options));
*/
    // Note: let's reuse the ext sync constants and strings here, internally it is very similar,
    //       it describes what should happen when users are not supposed to be ordertotled any more.
/* 
	$options = array(
        ordertot_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'ordertot'),
        ordertot_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'ordertot'),
        ordertot_EXT_REMOVED_UNordertot        => get_string('extremovedunordertot', 'ordertot'),
    );

    $settings->add(new admin_setting_configselect('ordertot_subtotal/expiredaction', get_string('expiredaction', 'ordertot_subtotal'), get_string('expiredaction_help', 'ordertot_subtotal'), ordertot_EXT_REMOVED_SUSPENDNOROLES, $options));
*/
    //--- mapping -------------------------------------------------------------------------------------------
/*
    if (!during_initial_install()) {
        $settings->add(new admin_setting_heading('ordertot_subtotal_mapping', get_string('mapping', 'ordertot_subtotal'), ''));

        $roles = role_fix_names(get_all_roles());

        foreach ($roles as $role) {
            $settings->add(new ordertot_subtotal_role_setting($role));
        }
        unset($roles);
    }
*/
}
