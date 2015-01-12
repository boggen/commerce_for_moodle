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
 * storeinfo config manipulation script.
 *
 * @package    core
 * @subpackage storeinfo
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require_once('../config.php');
require_once($CFG->libdir.'/adminlib.php');

$action  = required_param('action', PARAM_ALPHANUMEXT);
$storeinfo   = required_param('storeinfo', PARAM_PLUGIN);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$PAGE->set_url('/admin/storeinfo.php');
$PAGE->set_context(context_system::instance());

require_login();
require_capability('moodle/site:config', context_system::instance());
require_sesskey();

$enabled = storeinfo_get_plugins(true);
$all     = storeinfo_get_plugins(false);

$return = new moodle_url('/admin/settings.php', array('section'=>'managestoreinfos'));

$syscontext = context_system::instance();

switch ($action) {
    case 'disable':
        unset($enabled[$storeinfo]);
        set_config('storeinfo_plugins_enabled', implode(',', array_keys($enabled)));
        core_plugin_manager::reset_caches();
        $syscontext->mark_dirty(); // resets all storeinfo caches
        break;

    case 'enable':
        if (!isset($all[$storeinfo])) {
            break;
        }
        $enabled = array_keys($enabled);
        $enabled[] = $storeinfo;
        set_config('storeinfo_plugins_enabled', implode(',', $enabled));
        core_plugin_manager::reset_caches();
        $syscontext->mark_dirty(); // resets all storeinfo caches
        break;

    case 'up':
        if (!isset($enabled[$storeinfo])) {
            break;
        }
        $enabled = array_keys($enabled);
        $enabled = array_flip($enabled);
        $current = $enabled[$storeinfo];
        if ($current == 0) {
            break; //already at the top
        }
        $enabled = array_flip($enabled);
        $enabled[$current] = $enabled[$current - 1];
        $enabled[$current - 1] = $storeinfo;
        set_config('storeinfo_plugins_enabled', implode(',', $enabled));
        break;

    case 'down':
        if (!isset($enabled[$storeinfo])) {
            break;
        }
        $enabled = array_keys($enabled);
        $enabled = array_flip($enabled);
        $current = $enabled[$storeinfo];
        if ($current == count($enabled) - 1) {
            break; //already at the end
        }
        $enabled = array_flip($enabled);
        $enabled[$current] = $enabled[$current + 1];
        $enabled[$current + 1] = $storeinfo;
        set_config('storeinfo_plugins_enabled', implode(',', $enabled));
        break;

    default:
		break;       
}


redirect($return);