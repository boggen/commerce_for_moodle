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
 * pickup shipments plugin settings and presets.
 *
 * @package    shipping_pickup
 * @copyright  2010 Eugene Venter
 * @author     Eugene Venter - based on code by Petr Skoda and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/adminlib.php');

if ($ADMIN->fulltree) {

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('shipping_pickup_settings', '', get_string('pluginname_desc', 'shipping_pickup')));

    //$settings->add(new admin_setting_configfile('shipping_pickup/location', get_string('location', 'shipping_pickup'), get_string('location_desc', 'shipping_pickup'), ''));

    $options = core_text::get_encodings();
    $settings->add(new admin_setting_configselect('shipping_pickup/encoding', get_string('encoding', 'shipping_pickup'), '', 'UTF-8', $options));

    $settings->add(new admin_setting_configcheckbox('shipping_pickup/mailstudents', get_string('notifyshippingled', 'shipping_pickup'), '', 0));

    $settings->add(new admin_setting_configcheckbox('shipping_pickup/mailteachers', get_string('notifyshippingler', 'shipping_pickup'), '', 0));

    $settings->add(new admin_setting_configcheckbox('shipping_pickup/mailadmins', get_string('notifyadmin', 'shipping_pickup'), '', 0));

/*
    $options = array(shipping_EXT_REMOVED_UNshipping        => get_string('extremovedunshipping', 'shipping'),
                     shipping_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'shipping'),
                     shipping_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'shipping'));
    $settings->add(new admin_setting_configselect('shipping_pickup/unshippingaction', get_string('extremovedaction', 'shipping'), get_string('extremovedaction_help', 'shipping'), shipping_EXT_REMOVED_SUSPENDNOROLES, $options));
*/
    // Note: let's reuse the ext sync constants and strings here, internally it is very similar,
    //       it describes what should happen when users are not supposed to be shippingled any more.
/* 
	$options = array(
        shipping_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'shipping'),
        shipping_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'shipping'),
        shipping_EXT_REMOVED_UNshipping        => get_string('extremovedunshipping', 'shipping'),
    );

    $settings->add(new admin_setting_configselect('shipping_pickup/expiredaction', get_string('expiredaction', 'shipping_pickup'), get_string('expiredaction_help', 'shipping_pickup'), shipping_EXT_REMOVED_SUSPENDNOROLES, $options));
*/
    //--- mapping -------------------------------------------------------------------------------------------
/*
    if (!during_initial_install()) {
        $settings->add(new admin_setting_heading('shipping_pickup_mapping', get_string('mapping', 'shipping_pickup'), ''));

        $roles = role_fix_names(get_all_roles());

        foreach ($roles as $role) {
            $settings->add(new shipping_pickup_role_setting($role));
        }
        unset($roles);
    }
*/
}
