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
 * Test paygate plugin settings.
 *
 * @package    core_paygate
 * @copyright  2013 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'../../config.php');
require_once("$CFG->libdir/adminlib.php");

$paygate = optional_param('paygate', '', PARAM_RAW);
if (!core_component::is_valid_plugin_name('paygate', $paygate)) {
    $paygate = '';
} else if (!file_exists("$CFG->dirroot/paygate/$paygate/lib.php")) {
    $paygate = '';
}

require_login();
require_capability('moodle/site:config', context_system::instance());

navigation_node::override_active_url(new moodle_url('/admin/settings.php', array('section'=>'managepaygates')));
admin_externalpage_setup('paygatetestsettings');

$returnurl = new moodle_url('/admin/settings.php', array('section'=>'managepaygates'));

echo $OUTPUT->header();

if (!$paygate) {
    $options = array();
    $plugins = core_component::get_plugin_list('paygate');
    foreach ($plugins as $name => $fulldir) {
        $plugin = paygate_get_plugin($name);
        if (!$plugin or !method_exists($plugin, 'test_settings')) {
            continue;
        }
        $options[$name] = get_string('pluginname', 'paygate_'.$name);
    }

    if (!$options) {
        redirect($returnurl);
    }

    echo $OUTPUT->heading(get_string('testsettings', 'core_paygate'));

    $url = new moodle_url('/paygate/test_settings.php', array('sesskey'=>sesskey()));
    echo $OUTPUT->single_select($url, 'paygate', $options);

    echo $OUTPUT->footer();
}

$plugin = paygate_get_plugin($paygate);
if (!$plugin or !method_exists($plugin, 'test_settings')) {
    redirect($returnurl);
}

echo $OUTPUT->heading(get_string('testsettingsheading', 'core_paygate', get_string('pluginname', 'paygate_'.$paygate)));

$plugin->test_settings();

echo $OUTPUT->continue_button($returnurl);
echo $OUTPUT->footer();
