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
 * currency storemultiinfos plugin settings and presets.
 *
 * @package    storeinfo_currency
 * @copyright  2010 Eugene Venter
 * @author     Eugene Venter - based on code by Petr Skoda and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/adminlib.php');

if ($ADMIN->fulltree) {

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('storeinfo_currency_settings', '', get_string('pluginname_desc', 'storeinfo_currency')));

    //$settings->add(new admin_setting_configfile('storeinfo_currency/location', get_string('location', 'storeinfo_currency'), get_string('location_desc', 'storeinfo_currency'), ''));

    $options = core_text::get_encodings();
    $settings->add(new admin_setting_configselect('storeinfo_currency/encoding', get_string('encoding', 'storeinfo_currency'), '', 'UTF-8', $options));

    $settings->add(new admin_setting_configcheckbox('storeinfo_currency/mailstudents', get_string('notifystoreinfoled', 'storeinfo_currency'), '', 0));

    $settings->add(new admin_setting_configcheckbox('storeinfo_currency/mailteachers', get_string('notifystoreinfoler', 'storeinfo_currency'), '', 0));

    $settings->add(new admin_setting_configcheckbox('storeinfo_currency/mailadmins', get_string('notifyadmin', 'storeinfo_currency'), '', 0));

/*
    $options = array(storeinfo_EXT_REMOVED_UNstoreinfo        => get_string('extremovedunstoreinfo', 'storeinfo'),
                     storeinfo_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'storeinfo'),
                     storeinfo_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'storeinfo'));
    $settings->add(new admin_setting_configselect('storeinfo_currency/unstoreinfoaction', get_string('extremovedaction', 'storeinfo'), get_string('extremovedaction_help', 'storeinfo'), storeinfo_EXT_REMOVED_SUSPENDNOROLES, $options));
*/
    // Note: let's reuse the ext sync constants and strings here, internally it is very similar,
    //       it describes what should happen when users are not supposed to be storeinfoled any more.
/* 
	$options = array(
        storeinfo_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'storeinfo'),
        storeinfo_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'storeinfo'),
        storeinfo_EXT_REMOVED_UNstoreinfo        => get_string('extremovedunstoreinfo', 'storeinfo'),
    );

    $settings->add(new admin_setting_configselect('storeinfo_currency/expiredaction', get_string('expiredaction', 'storeinfo_currency'), get_string('expiredaction_help', 'storeinfo_currency'), storeinfo_EXT_REMOVED_SUSPENDNOROLES, $options));
*/
    //--- mapping -------------------------------------------------------------------------------------------
/*
    if (!during_initial_install()) {
        $settings->add(new admin_setting_heading('storeinfo_currency_mapping', get_string('mapping', 'storeinfo_currency'), ''));

        $roles = role_fix_names(get_all_roles());

        foreach ($roles as $role) {
            $settings->add(new storeinfo_currency_role_setting($role));
        }
        unset($roles);
    }
*/
}
