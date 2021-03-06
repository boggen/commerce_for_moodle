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
 * Test ordertot plugin settings.
 *
 * @package    core_ordertot
 * @copyright  2013 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'../../config.php');
require_once("$CFG->libdir/adminlib.php");

$ordertot = optional_param('ordertot', '', PARAM_RAW);
if (!core_component::is_valid_plugin_name('ordertot', $ordertot)) {
    $ordertot = '';
} else if (!file_exists("$CFG->dirroot/ordertot/$ordertot/lib.php")) {
    $ordertot = '';
}

require_login();
require_capability('moodle/site:config', context_system::instance());

navigation_node::override_active_url(new moodle_url('/admin/settings.php', array('section'=>'manageordertots')));
admin_externalpage_setup('ordertottestsettings');

$returnurl = new moodle_url('/admin/settings.php', array('section'=>'manageordertots'));

echo $OUTPUT->header();

if (!$ordertot) {
    $options = array();
    $plugins = core_component::get_plugin_list('ordertot');
    foreach ($plugins as $name => $fulldir) {
        $plugin = ordertot_get_plugin($name);
        if (!$plugin or !method_exists($plugin, 'test_settings')) {
            continue;
        }
        $options[$name] = get_string('pluginname', 'ordertot_'.$name);
    }

    if (!$options) {
        redirect($returnurl);
    }

    echo $OUTPUT->heading(get_string('testsettings', 'core_ordertot'));

    $url = new moodle_url('/ordertot/test_settings.php', array('sesskey'=>sesskey()));
    echo $OUTPUT->single_select($url, 'ordertot', $options);

    echo $OUTPUT->footer();
}

$plugin = ordertot_get_plugin($ordertot);
if (!$plugin or !method_exists($plugin, 'test_settings')) {
    redirect($returnurl);
}

echo $OUTPUT->heading(get_string('testsettingsheading', 'core_ordertot', get_string('pluginname', 'ordertot_'.$ordertot)));

$plugin->test_settings();

echo $OUTPUT->continue_button($returnurl);
echo $OUTPUT->footer();
