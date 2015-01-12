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
 * retuacti storemultiinfos plugin settings and presets.
 *
 * @package    storeinfo_retuacti
 * @copyright  2010 Eugene Venter
 * @author     Eugene Venter - based on code by Petr Skoda and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/adminlib.php');

if ($ADMIN->fulltree) {

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('storeinfo_retuacti_settings', '', get_string('pluginname_desc', 'storeinfo_retuacti')));

    //$settings->add(new admin_setting_configfile('storeinfo_retuacti/location', get_string('location', 'storeinfo_retuacti'), get_string('location_desc', 'storeinfo_retuacti'), ''));

    $options = core_text::get_encodings();
    $settings->add(new admin_setting_configselect('storeinfo_retuacti/encoding', get_string('encoding', 'storeinfo_retuacti'), '', 'UTF-8', $options));

    $settings->add(new admin_setting_configcheckbox('storeinfo_retuacti/mailstudents', get_string('notifystoreinfoled', 'storeinfo_retuacti'), '', 0));

    $settings->add(new admin_setting_configcheckbox('storeinfo_retuacti/mailteachers', get_string('notifystoreinfoler', 'storeinfo_retuacti'), '', 0));

    $settings->add(new admin_setting_configcheckbox('storeinfo_retuacti/mailadmins', get_string('notifyadmin', 'storeinfo_retuacti'), '', 0));

/*
    $options = array(storeinfo_EXT_REMOVED_UNstoreinfo        => get_string('extremovedunstoreinfo', 'storeinfo'),
                     storeinfo_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'storeinfo'),
                     storeinfo_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'storeinfo'));
    $settings->add(new admin_setting_configselect('storeinfo_retuacti/unstoreinfoaction', get_string('extremovedaction', 'storeinfo'), get_string('extremovedaction_help', 'storeinfo'), storeinfo_EXT_REMOVED_SUSPENDNOROLES, $options));
*/
    // Note: let's reuse the ext sync constants and strings here, internally it is very similar,
    //       it describes what should happen when users are not supposed to be storeinfoled any more.
/* 
	$options = array(
        storeinfo_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'storeinfo'),
        storeinfo_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'storeinfo'),
        storeinfo_EXT_REMOVED_UNstoreinfo        => get_string('extremovedunstoreinfo', 'storeinfo'),
    );

    $settings->add(new admin_setting_configselect('storeinfo_retuacti/expiredaction', get_string('expiredaction', 'storeinfo_retuacti'), get_string('expiredaction_help', 'storeinfo_retuacti'), storeinfo_EXT_REMOVED_SUSPENDNOROLES, $options));
*/
    //--- mapping -------------------------------------------------------------------------------------------
/*
    if (!during_initial_install()) {
        $settings->add(new admin_setting_heading('storeinfo_retuacti_mapping', get_string('mapping', 'storeinfo_retuacti'), ''));

        $roles = role_fix_names(get_all_roles());

        foreach ($roles as $role) {
            $settings->add(new storeinfo_retuacti_role_setting($role));
        }
        unset($roles);
    }
*/
}
