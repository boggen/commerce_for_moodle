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
 * Defines classes used for plugin info.
 *
 * @package    core
 * @copyright  2013 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\plugininfo;

use moodle_url, part_of_admin_tree, admin_settingpage, admin_externalpage;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for checkout plugins
 */
class checkout extends base {
    /**
     * Finds all enabled plugins, the result may include missing plugins.
     * @return array|null of enabled plugins $pluginname=>$pluginname, null means unknown
     */
    public static function get_enabled_plugins() {
        global $CFG;

        $enabled = array();
        foreach (explode(',', $CFG->checkout_plugins_enabled) as $checkout) {
            $enabled[$checkout] = $checkout;
        }

        return $enabled;
    }

    public function get_settings_section_name() {
        if (file_exists($this->full_path('settings.php'))) {
            return 'checkoutsettings' . $this->name;
        } else {
            return null;
        }
    }

    public function load_settings(part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE; // In case settings.php wants to refer to them.
        $ADMIN = $adminroot; // May be used in settings.php.
        $plugininfo = $this; // Also can be used inside settings.php.
        $checkout = $this;      // Also can be used inside settings.php.

        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        if (!$hassiteconfig or !file_exists($this->full_path('settings.php'))) {
            return;
        }

        $section = $this->get_settings_section_name();

        $settings = new admin_settingpage($section, $this->displayname, 'moodle/site:config', $this->is_enabled() === false);

        include($this->full_path('settings.php')); // This may also set $settings to null!

        if ($settings) {
            $ADMIN->add($parentnodename, $settings);
        }
    }

    public function is_uninstall_allowed() {
        if ($this->name === 'manual') {
            return false;
        }
        return true;
    }

    /**
     * Return URL used for management of plugins of this type.
     * @return moodle_url
     */
    public static function get_manage_url() {
        return new moodle_url('/admin/settings.php', array('section'=>'managecheckouts'));
    }

    /**
     * Return warning with number of activities and number of affected courses.
     *
     * @return string
     */
    public function get_uninstall_extra_warning() {
        global $DB, $OUTPUT;
//=====================================================================================
//=====================================================================================
//=====================================================================================
//=====================================================================================
// referencing to a non existing table user_checkingouts...err user_checkingouts
        $sql = "SELECT COUNT('x')
                  FROM {user_checkingouts} ue
                  JOIN {checkout} e ON e.id = ue.checkoutid
                 WHERE e.checkout = :plugin";
        $count = $DB->count_records_sql($sql, array('plugin'=>$this->name));

        if (!$count) {
            return '';
        }

        $migrateurl = new moodle_url('/admin/checkout.php', array('action'=>'migrate', 'checkout'=>$this->name, 'sesskey'=>sesskey()));
        $migrate = new \single_button($migrateurl, get_string('migratetomanual', 'core_checkout'));
        $button = $OUTPUT->render($migrate);

        $result = '<p>'.get_string('uninstallextraconfirmcheckout', 'core_plugin', array('checkingouts'=>$count)).'</p>';
        $result .= $button;

        return $result;
    }

    /**
     * Pre-uninstall hook.
     *
     * This is intended for disabling of plugin, some DB table purging, etc.
     *
     * NOTE: to be called from uninstall_plugin() only.
     * @private
     */
    public function uninstall_cleanup() {
        global $DB, $CFG;

        // NOTE: this is a bit brute force way - it will not trigger events and hooks properly.

        // Nuke all role assignments.
        role_unassign_all(array('component'=>'checkout_'.$this->name));

        // Purge participants.
        $DB->delete_records_select('user_checkingouts', "checkoutid IN (SELECT id FROM {checkout} WHERE checkout = ?)", array($this->name));

        // Purge checkout instances.
        $DB->delete_records('checkout', array('checkout'=>$this->name));

        // Tweak checkout settings.
        if (!empty($CFG->checkout_plugins_enabled)) {
            $enabledcheckouts = explode(',', $CFG->checkout_plugins_enabled);
            $enabledcheckouts = array_unique($enabledcheckouts);
            $enabledcheckouts = array_flip($enabledcheckouts);
            unset($enabledcheckouts[$this->name]);
            $enabledcheckouts = array_flip($enabledcheckouts);
            if (is_array($enabledcheckouts)) {
                set_config('checkout_plugins_enabled', implode(',', $enabledcheckouts));
            }
        }

        parent::uninstall_cleanup();
    }
}
