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
 * statstoc storemultiinfos plugin settings and presets.
 *
 * @package    storinfo_statstoc
 * @copyright  2010 Eugene Venter
 * @author     Eugene Venter - based on code by Petr Skoda and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/adminlib.php');

if ($ADMIN->fulltree) {

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('storinfo_statstoc_settings', '', get_string('pluginname_desc', 'storinfo_statstoc')));

    //$settings->add(new admin_setting_configfile('storinfo_statstoc/location', get_string('location', 'storinfo_statstoc'), get_string('location_desc', 'storinfo_statstoc'), ''));

    $options = core_text::get_encodings();
    $settings->add(new admin_setting_configselect('storinfo_statstoc/encoding', get_string('encoding', 'storinfo_statstoc'), '', 'UTF-8', $options));

    $settings->add(new admin_setting_configcheckbox('storinfo_statstoc/mailstudents', get_string('notifystorinfoled', 'storinfo_statstoc'), '', 0));

    $settings->add(new admin_setting_configcheckbox('storinfo_statstoc/mailteachers', get_string('notifystorinfoler', 'storinfo_statstoc'), '', 0));

    $settings->add(new admin_setting_configcheckbox('storinfo_statstoc/mailadmins', get_string('notifyadmin', 'storinfo_statstoc'), '', 0));

/*
    $options = array(storinfo_EXT_REMOVED_UNstorinfo        => get_string('extremovedunstorinfo', 'storinfo'),
                     storinfo_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'storinfo'),
                     storinfo_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'storinfo'));
    $settings->add(new admin_setting_configselect('storinfo_statstoc/unstorinfoaction', get_string('extremovedaction', 'storinfo'), get_string('extremovedaction_help', 'storinfo'), storinfo_EXT_REMOVED_SUSPENDNOROLES, $options));
*/
    // Note: let's reuse the ext sync constants and strings here, internally it is very similar,
    //       it describes what should happen when users are not supposed to be storinfoled any more.
/* 
	$options = array(
        storinfo_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'storinfo'),
        storinfo_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'storinfo'),
        storinfo_EXT_REMOVED_UNstorinfo        => get_string('extremovedunstorinfo', 'storinfo'),
    );

    $settings->add(new admin_setting_configselect('storinfo_statstoc/expiredaction', get_string('expiredaction', 'storinfo_statstoc'), get_string('expiredaction_help', 'storinfo_statstoc'), storinfo_EXT_REMOVED_SUSPENDNOROLES, $options));
*/
    //--- mapping -------------------------------------------------------------------------------------------
/*
    if (!during_initial_install()) {
        $settings->add(new admin_setting_heading('storinfo_statstoc_mapping', get_string('mapping', 'storinfo_statstoc'), ''));

        $roles = role_fix_names(get_all_roles());

        foreach ($roles as $role) {
            $settings->add(new storinfo_statstoc_role_setting($role));
        }
        unset($roles);
    }
*/
}
