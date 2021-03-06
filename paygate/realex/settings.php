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
 * realex paymentgatways plugin settings and presets.
 *
 * @package    paygate_realex
 * @copyright  2010 Eugene Venter
 * @author     Eugene Venter - based on code by Petr Skoda and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/adminlib.php');

if ($ADMIN->fulltree) {

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('paygate_realex_settings', '', get_string('pluginname_desc', 'paygate_realex')));

    //$settings->add(new admin_setting_configfile('paygate_realex/location', get_string('location', 'paygate_realex'), get_string('location_desc', 'paygate_realex'), ''));

    $options = core_text::get_encodings();
    $settings->add(new admin_setting_configselect('paygate_realex/encoding', get_string('encoding', 'paygate_realex'), '', 'UTF-8', $options));

    $settings->add(new admin_setting_configcheckbox('paygate_realex/mailstudents', get_string('notifypaygateled', 'paygate_realex'), '', 0));

    $settings->add(new admin_setting_configcheckbox('paygate_realex/mailteachers', get_string('notifypaygateler', 'paygate_realex'), '', 0));

    $settings->add(new admin_setting_configcheckbox('paygate_realex/mailadmins', get_string('notifyadmin', 'paygate_realex'), '', 0));

/*
    $options = array(paygate_EXT_REMOVED_UNpaygate        => get_string('extremovedunpaygate', 'paygate'),
                     paygate_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'paygate'),
                     paygate_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'paygate'));
    $settings->add(new admin_setting_configselect('paygate_realex/unpaygateaction', get_string('extremovedaction', 'paygate'), get_string('extremovedaction_help', 'paygate'), paygate_EXT_REMOVED_SUSPENDNOROLES, $options));
*/
    // Note: let's reuse the ext sync constants and strings here, internally it is very similar,
    //       it describes what should happen when users are not supposed to be paygateled any more.
/* 
	$options = array(
        paygate_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'paygate'),
        paygate_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'paygate'),
        paygate_EXT_REMOVED_UNpaygate        => get_string('extremovedunpaygate', 'paygate'),
    );

    $settings->add(new admin_setting_configselect('paygate_realex/expiredaction', get_string('expiredaction', 'paygate_realex'), get_string('expiredaction_help', 'paygate_realex'), paygate_EXT_REMOVED_SUSPENDNOROLES, $options));
*/
    //--- mapping -------------------------------------------------------------------------------------------
/*
    if (!during_initial_install()) {
        $settings->add(new admin_setting_heading('paygate_realex_mapping', get_string('mapping', 'paygate_realex'), ''));

        $roles = role_fix_names(get_all_roles());

        foreach ($roles as $role) {
            $settings->add(new paygate_realex_role_setting($role));
        }
        unset($roles);
    }
*/
}
