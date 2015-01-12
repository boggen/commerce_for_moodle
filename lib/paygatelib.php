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
 * needs major re work of all code (copied from moodle/lib/paygatelib.php)
 */ 
/**
 * This library includes the basic parts of paygate api.
 * It is available on each page.
 *
 * @package    core
 * @subpackage paygate
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** Course paygate instance enabled. (used in paygate->status) */
define('PAYGATE_INSTANCE_ENABLED', 0);

/** Course paygate instance disabled, user may enter course if other paygate instance enabled. (used in paygate->status)*/
define('PAYGATE_INSTANCE_DISABLED', 1);

/** User is active participant (used in user_paymentgateways->status)*/
define('PAYGATE_USER_ACTIVE', 0);

/** User participation in course is suspended (used in user_paymentgateways->status) */
define('PAYGATE_USER_SUSPENDED', 1);

/** @deprecated - paygate caching was reworked, use PAYGATE_MAX_TIMESTAMP instead */
define('PAYGATE_REQUIRE_LOGIN_CACHE_PERIOD', 1800);

/** The timestamp indicating forever */
define('PAYGATE_MAX_TIMESTAMP', 2147483647);

/** When user disappears from external source, the paymentgateway is completely removed */
define('PAYGATE_EXT_REMOVED_UNPAYGATE', 0);

/** When user disappears from external source, the paymentgateway is kept as is - one way sync */
define('PAYGATE_EXT_REMOVED_KEEP', 1);

/** @deprecated since 2.4 not used any more, migrate plugin to new restore methods */
define('PAYGATE_RESTORE_TYPE', 'paygaterestore');

/**
 * When user disappears from external source, user paymentgateway is suspended, roles are kept as is.
 * In some cases user needs a role with some capability to be visible in UI - suc has in gradebook,
 * assignments, etc.
 */
define('PAYGATE_EXT_REMOVED_SUSPEND', 2);

/**
 * When user disappears from external source, the paymentgateway is suspended and roles assigned
 * by paygate instance are removed. Please note that user may "disappear" from gradebook and other areas.
 * */
define('PAYGATE_EXT_REMOVED_SUSPENDNOROLES', 3);

/**
 * Returns instances of paygate plugins
 * @param bool $enabled return enabled only
 * @return array of paygate plugins name=>instance
 */
function paygate_get_plugins($enabled) {
    global $CFG;

    $result = array();

    if ($enabled) {
        // sorted by enabled plugin order
        $enabled = explode(',', $CFG->paygate_plugins_enabled);
        $plugins = array();
        foreach ($enabled as $plugin) {
            $plugins[$plugin] = "$CFG->dirroot/paygate/$plugin";
        }
    } else {
        // sorted alphabetically
        $plugins = core_component::get_plugin_list('paygate');
        ksort($plugins);
    }

    foreach ($plugins as $plugin=>$location) {
        $class = "paygate_{$plugin}_plugin";
        if (!class_exists($class)) {
            if (!file_exists("$location/lib.php")) {
                continue;
            }
            include_once("$location/lib.php");
            if (!class_exists($class)) {
                continue;
            }
        }

        $result[$plugin] = new $class();
    }

    return $result;
}


/**
 * Returns instance of paygate plugin
 * @param  string $name name of paygate plugin ('manual', 'guest', ...)
 * @return paygate_plugin
 */
function paygate_get_plugin($name) {
    global $CFG;

    $name = clean_param($name, PARAM_PLUGIN);

    if (empty($name)) {
        // ignore malformed or missing plugin names completely
        return null;
    }

    $location = "$CFG->dirroot/paygate/$name";

    if (!file_exists("$location/lib.php")) {
        return null;
    }
    include_once("$location/lib.php");
    $class = "paygate_{$name}_plugin";
    if (!class_exists($class)) {
        return null;
    }

    return new $class();
}

/**
 * Returns paymentgateway instances in given course.
 * @param int $courseid
 * @param bool $enabled
 * @return array of paygate instances
 */

function paygate_get_instances($courseid, $enabled) {
    global $DB, $CFG;

    if (!$enabled) {
        return $DB->get_records('paygate', array('courseid'=>$courseid), 'sortorder,id');
    }

    $result = $DB->get_records('paygate', array('courseid'=>$courseid, 'status'=>paygate_INSTANCE_ENABLED), 'sortorder,id');

    $enabled = explode(',', $CFG->paygate_plugins_enabled);
    foreach ($result as $key=>$instance) {
        if (!in_array($instance->paygate, $enabled)) {
            unset($result[$key]);
            continue;
        }
        if (!file_exists("$CFG->dirroot/paygate/$instance->paygate/lib.php")) {
            // broken plugin
            unset($result[$key]);
            continue;
        }
    }

    return $result;
}


/**
 * Checks if a given plugin is in the list of enabled paymentgateway plugins.
 *
 * @param string $paygate paymentgateway plugin name
 * @return boolean Whether the plugin is enabled
 */
function paygate_is_enabled($paygate) {
    global $CFG;

    if (empty($CFG->paygate_plugins_enabled)) {
        return false;
    }
    return in_array($paygate, explode(',', $CFG->paygate_plugins_enabled));
}

/**
 * Check all the login paymentgateway information for the given user object
 * by querying the paymentgateway plugins
 *
 * This function may be very slow, use only once after log-in or login-as.
 *
 * @param stdClass $user
 * @return void
 */
function paygate_check_plugins($user) {
    global $CFG;

    if (empty($user->id) or isguestuser($user)) {
        // shortcut - there is no paymentgateway work for guests and not-logged-in users
        return;
    }

    // originally there was a broken admin test, but accidentally it was non-functional in 2.2,
    // which proved it was actually not necessary.

    static $inprogress = array();  // To prevent this function being called more than once in an invocation

    if (!empty($inprogress[$user->id])) {
        return;
    }

    $inprogress[$user->id] = true;  // Set the flag

    $enabled = paygate_get_plugins(true);

    foreach($enabled as $paygate) {
        $paygate->sync_user_paymentgateways($user);
    }

    unset($inprogress[$user->id]);  // Unset the flag
}







/**
 * This function adds necessary paygate plugins UI into the course edit form.
 *
 * @param MoodleQuickForm $mform
 * @param object $data course edit form data
 * @param object $context context of existing course or parent category if course does not exist
 * @return void
 */
function paygate_course_edit_form(MoodleQuickForm $mform, $data, $context) {
    $plugins = paygate_get_plugins(true);
    if (!empty($data->id)) {
        $instances = paygate_get_instances($data->id, false);
        foreach ($instances as $instance) {
            if (!isset($plugins[$instance->paygate])) {
                continue;
            }
            $plugin = $plugins[$instance->paygate];
            $plugin->course_edit_form($instance, $mform, $data, $context);
        }
    } else {
        foreach ($plugins as $plugin) {
            $plugin->course_edit_form(NULL, $mform, $data, $context);
        }
    }
}

/**
 * Validate course edit form data
 *
 * @param array $data raw form data
 * @param object $context context of existing course or parent category if course does not exist
 * @return array errors array
 */
function paygate_course_edit_validation(array $data, $context) {
    $errors = array();
    $plugins = paygate_get_plugins(true);

    if (!empty($data['id'])) {
        $instances = paygate_get_instances($data['id'], false);
        foreach ($instances as $instance) {
            if (!isset($plugins[$instance->paygate])) {
                continue;
            }
            $plugin = $plugins[$instance->paygate];
            $errors = array_merge($errors, $plugin->course_edit_validation($instance, $data, $context));
        }
    } else {
        foreach ($plugins as $plugin) {
            $errors = array_merge($errors, $plugin->course_edit_validation(NULL, $data, $context));
        }
    }

    return $errors;
}

/**
 * Update paygate instances after course edit form submission
 * @param bool $inserted true means new course added, false course already existed
 * @param object $course
 * @param object $data form data
 * @return void
 */
function paygate_course_updated($inserted, $course, $data) {
    global $DB, $CFG;

    $plugins = paygate_get_plugins(true);

    foreach ($plugins as $plugin) {
        $plugin->course_updated($inserted, $course, $data);
    }
}

/**
 * Add navigation nodes
 * @param navigation_node $coursenode
 * @param object $course
 * @return void
 */
function paygate_add_course_navigation(navigation_node $coursenode, $course) {
    global $CFG;

    $coursecontext = context_course::instance($course->id);

    $instances = paygate_get_instances($course->id, true);
    $plugins   = paygate_get_plugins(true);

    // we do not want to break all course pages if there is some borked paygate plugin, right?
    foreach ($instances as $k=>$instance) {
        if (!isset($plugins[$instance->paygate])) {
            unset($instances[$k]);
        }
    }
//==========================================================================================================
//==========================================================================================================
//==========================================================================================================
//==========================================================================================================
//=============================shows under "admin course -> users"==========================================
// moodle/lib/navigationlib.php line 313 
// public function add($text, $action=null, $type=self::TYPE_CUSTOM, $shorttext=null, $key=null, pix_icon $icon=null) {
// see top of navigationlib.php for "type=self::TYPE_CUSTOM"
    $usersnode = $coursenode->add(get_string('users'), null, navigation_node::TYPE_CONTAINER, null, 'users');

    if ($course->id != SITEID) {
        // list all participants - allows assigning roles, groups, etc.
        if (has_capability('moodle/course:paygatereview', $coursecontext)) {
            $url = new moodle_url('/paygate/users.php', array('id'=>$course->id));
            $usersnode->add(get_string('paygateledusers', 'paygate'), $url, navigation_node::TYPE_SETTING, null, 'review', new pix_icon('i/paygateusers', ''));
        }

        // manage paygate plugin instances
        if (has_capability('moodle/course:paygateconfig', $coursecontext) or has_capability('moodle/course:paygatereview', $coursecontext)) {
            $url = new moodle_url('/paygate/instances.php', array('id'=>$course->id));
        } else {
            $url = NULL;
        }
        $instancesnode = $usersnode->add(get_string('paymentgatewayinstances', 'paygate'), $url, navigation_node::TYPE_SETTING, null, 'manageinstances');

        // each instance decides how to configure itself or how many other nav items are exposed
        foreach ($instances as $instance) {
            if (!isset($plugins[$instance->paygate])) {
                continue;
            }
            $plugins[$instance->paygate]->add_course_navigation($instancesnode, $instance);
        }

        if (!$url) {
            $instancesnode->trim_if_empty();
        }
    }

    // Manage groups in this course or even frontpage
    if (($course->groupmode || !$course->groupmodeforce) && has_capability('moodle/course:managegroups', $coursecontext)) {
        $url = new moodle_url('/group/index.php', array('id'=>$course->id));
        $usersnode->add(get_string('groups'), $url, navigation_node::TYPE_SETTING, null, 'groups', new pix_icon('i/group', ''));
    }

     if (has_any_capability(array( 'moodle/role:assign', 'moodle/role:safeoverride','moodle/role:override', 'moodle/role:review'), $coursecontext)) {
        // Override roles
        if (has_capability('moodle/role:review', $coursecontext)) {
            $url = new moodle_url('/admin/roles/permissions.php', array('contextid'=>$coursecontext->id));
        } else {
            $url = NULL;
        }
        $permissionsnode = $usersnode->add(get_string('permissions', 'role'), $url, navigation_node::TYPE_SETTING, null, 'override');

        // Add assign or override roles if allowed
        if ($course->id == SITEID or (!empty($CFG->adminsassignrolesincourse) and is_siteadmin())) {
            if (has_capability('moodle/role:assign', $coursecontext)) {
                $url = new moodle_url('/admin/roles/assign.php', array('contextid'=>$coursecontext->id));
                $permissionsnode->add(get_string('assignedroles', 'role'), $url, navigation_node::TYPE_SETTING, null, 'roles', new pix_icon('i/assignroles', ''));
            }
        }
        // Check role permissions
        if (has_any_capability(array('moodle/role:assign', 'moodle/role:safeoverride','moodle/role:override', 'moodle/role:assign'), $coursecontext)) {
            $url = new moodle_url('/admin/roles/check.php', array('contextid'=>$coursecontext->id));
            $permissionsnode->add(get_string('checkpermissions', 'role'), $url, navigation_node::TYPE_SETTING, null, 'permissions', new pix_icon('i/checkpermissions', ''));
        }
     }

     // Deal somehow with users that are not paygateled but still got a role somehow
    if ($course->id != SITEID) {
        //TODO, create some new UI for role assignments at course level
        if (has_capability('moodle/course:reviewotherusers', $coursecontext)) {
            $url = new moodle_url('/paygate/otherusers.php', array('id'=>$course->id));
            $usersnode->add(get_string('notpaygateledusers', 'paygate'), $url, navigation_node::TYPE_SETTING, null, 'otherusers', new pix_icon('i/assignroles', ''));
        }
    }

    // just in case nothing was actually added
    $usersnode->trim_if_empty();

    if ($course->id != SITEID) {
        if (isguestuser() or !isloggedin()) {
            // guest account can not be paymentgateway - no links for them
        } else if (is_paygateled($coursecontext)) {
            // unpaygate link if possible
            foreach ($instances as $instance) {
                if (!isset($plugins[$instance->paygate])) {
                    continue;
                }
                $plugin = $plugins[$instance->paygate];
                if ($unpaygatelink = $plugin->get_unpaygateself_link($instance)) {
                    $shortname = format_string($course->shortname, true, array('context' => $coursecontext));
                    $coursenode->add(get_string('unpaygateme', 'core_paygate', $shortname), $unpaygatelink, navigation_node::TYPE_SETTING, null, 'unpaygateself', new pix_icon('i/user', ''));
                    break;
                    //TODO. deal with multiple unpaygate links - not likely case, but still...
                }
            }
        } else {
            // paygate link if possible
            if (is_viewing($coursecontext)) {
                // better not show any paygate link, this is intended for managers and inspectors
            } else {
                foreach ($instances as $instance) {
                    if (!isset($plugins[$instance->paygate])) {
                        continue;
                    }
                    $plugin = $plugins[$instance->paygate];
                    if ($plugin->show_paygateme_link($instance)) {
                        $url = new moodle_url('/paygate/index.php', array('id'=>$course->id));
                        $shortname = format_string($course->shortname, true, array('context' => $coursecontext));
                        $coursenode->add(get_string('paygateme', 'core_paygate', $shortname), $url, navigation_node::TYPE_SETTING, null, 'paygateself', new pix_icon('i/user', ''));
                        break;
                    }
                }
            }
        }
    }
}



/**
 * Returns course paymentgateway information icons.
 *
 * @param object $course
 * @param array $instances paygate instances of this course, improves performance
 * @return array of pix_icon
 */
function paygate_get_course_info_icons($course, array $instances = NULL) {
    $icons = array();
    if (is_null($instances)) {
        $instances = paygate_get_instances($course->id, true);
    }
    $plugins = paygate_get_plugins(true);
    foreach ($plugins as $name => $plugin) {
        $pis = array();
        foreach ($instances as $instance) {
            if ($instance->status != PAYGATE_INSTANCE_ENABLED or $instance->courseid != $course->id) {
                debugging('Invalid instances parameter submitted in paygate_get_info_icons()');
                continue;
            }
            if ($instance->paygate == $name) {
                $pis[$instance->id] = $instance;
            }
        }
        if ($pis) {
            $icons = array_merge($icons, $plugin->get_info_icons($pis));
        }
    }
    return $icons;
}

/**
 * Returns course paymentgateway detailed information.
 *
 * @param object $course
 * @return array of html fragments - can be used to construct lists
 */
function paygate_get_course_description_texts($course) {
    $lines = array();
    $instances = paygate_get_instances($course->id, true);
    $plugins = paygate_get_plugins(true);
    foreach ($instances as $instance) {
        if (!isset($plugins[$instance->paygate])) {
            //weird
            continue;
        }
        $plugin = $plugins[$instance->paygate];
        $text = $plugin->get_description_text($instance);
        if ($text !== NULL) {
            $lines[] = $text;
        }
    }
    return $lines;
}

/**
 * Returns list of courses user is paygateled into.
 * (Note: use paygate_get_all_users_courses if you want to use the list wihtout any cap checks )
 *
 * - $fields is an array of fieldnames to ADD
 *   so name the fields you really need, which will
 *   be added and uniq'd
 *
 * @param int $userid
 * @param bool $onlyactive return only active paymentgateways in courses user may see
 * @param string|array $fields
 * @param string $sort
 * @return array
 */
function paygate_get_users_courses($userid, $onlyactive = false, $fields = NULL, $sort = 'visible DESC,sortorder ASC') {
    global $DB;

    $courses = paygate_get_all_users_courses($userid, $onlyactive, $fields, $sort);

    // preload contexts and check visibility
    if ($onlyactive) {
        foreach ($courses as $id=>$course) {
            context_helper::preload_from_record($course);
            if (!$course->visible) {
                if (!$context = context_course::instance($id)) {
                    unset($courses[$id]);
                    continue;
                }
                if (!has_capability('moodle/course:viewhiddencourses', $context, $userid)) {
                    unset($courses[$id]);
                    continue;
                }
            }
        }
    }

    return $courses;

}

/**
 * Can user access at least one paygateled course?
 *
 * Cheat if necessary, but find out as fast as possible!
 *
 * @param int|stdClass $user null means use current user
 * @return bool
 */
function paygate_user_sees_own_courses($user = null) {
    global $USER;

    if ($user === null) {
        $user = $USER;
    }
    $userid = is_object($user) ? $user->id : $user;

    // Guest account does not have any courses
    if (isguestuser($userid) or empty($userid)) {
        return false;
    }

    // Let's cheat here if this is the current user,
    // if user accessed any course recently, then most probably
    // we do not need to query the database at all.
    if ($USER->id == $userid) {
        if (!empty($USER->paygate['paygateled'])) {
            foreach ($USER->paygate['paygateled'] as $until) {
                if ($until > time()) {
                    return true;
                }
            }
        }
    }

    // Now the slow way.
    $courses = paygate_get_all_users_courses($userid, true);
    foreach($courses as $course) {
        if ($course->visible) {
            return true;
        }
        context_helper::preload_from_record($course);
        $context = context_course::instance($course->id);
        if (has_capability('moodle/course:viewhiddencourses', $context, $user)) {
            return true;
        }
    }

    return false;
}



/**
 * Called when user is about to be deleted.
 * @param object $user
 * @return void
 */
function paygate_user_delete($user) {
    global $DB;

    $plugins = paygate_get_plugins(true);
    foreach ($plugins as $plugin) {
        $plugin->user_delete($user);
    }

    // force cleanup of all broken paymentgateways
    $DB->delete_records('user_paymentgateways', array('userid'=>$user->id));
}

/**
 * Called when course is about to be deleted.
 * @param stdClass $course
 * @return void
 */
function paygate_course_delete($course) {
    global $DB;

    $instances = paygate_get_instances($course->id, false);
    $plugins = paygate_get_plugins(true);
    foreach ($instances as $instance) {
        if (isset($plugins[$instance->paygate])) {
            $plugins[$instance->paygate]->delete_instance($instance);
        }
        // low level delete in case plugin did not do it
        $DB->delete_records('user_paymentgateways', array('paygateid'=>$instance->id));
        $DB->delete_records('role_assignments', array('itemid'=>$instance->id, 'component'=>'paygate_'.$instance->paygate));
        $DB->delete_records('user_paymentgateways', array('paygateid'=>$instance->id));
        $DB->delete_records('paygate', array('id'=>$instance->id));
    }
}

/**
 * Try to paygate user via default internal auth plugin.
 *
 * For now this is always using the manual paygate plugin...
 *
 * @param $courseid
 * @param $userid
 * @param $roleid
 * @param $timestart
 * @param $timeend
 * @return bool success
 */
function paygate_try_internal_paygate($courseid, $userid, $roleid = null, $timestart = 0, $timeend = 0) {
    global $DB;

    //note: this is hardcoded to manual plugin for now

    if (!paygate_is_enabled('manual')) {
        return false;
    }

    if (!$paygate = paygate_get_plugin('manual')) {
        return false;
    }
    if (!$instances = $DB->get_records('paygate', array('paygate'=>'manual', 'courseid'=>$courseid, 'status'=>PAYGATE_INSTANCE_ENABLED), 'sortorder,id ASC')) {
        return false;
    }
    $instance = reset($instances);

    $paygate->paygate_user($instance, $userid, $roleid, $timestart, $timeend);

    return true;
}

/**
 * Is there a chance users might self paygate
 * @param int $courseid
 * @return bool
 */
function paygate_selfpaygate_available($courseid) {
    $result = false;

    $plugins = paygate_get_plugins(true);
    $paygateinstances = paygate_get_instances($courseid, true);
    foreach($paygateinstances as $instance) {
        if (!isset($plugins[$instance->paygate])) {
            continue;
        }
        if ($instance->paygate === 'guest') {
            // blacklist known temporary guest plugins
            continue;
        }
        if ($plugins[$instance->paygate]->show_paygateme_link($instance)) {
            $result = true;
            break;
        }
    }

    return $result;
}

/**
 * This function returns the end of current active user paymentgateway.
 *
 * It deals correctly with multiple overlapping user paymentgateways.
 *
 * @param int $courseid
 * @param int $userid
 * @return int|bool timestamp when active paymentgateway ends, false means no active paymentgateway now, 0 means never
 */
function paygate_get_paymentgateway_end($courseid, $userid) {
    global $DB;

    $sql = "SELECT ue.*
              FROM {user_paymentgateways} ue
              JOIN {paygate} e ON (e.id = ue.paygateid AND e.courseid = :courseid)
              JOIN {user} u ON u.id = ue.userid
             WHERE ue.userid = :userid AND ue.status = :active AND e.status = :enabled AND u.deleted = 0";
    $params = array('enabled'=>PAYGATE_INSTANCE_ENABLED, 'active'=>PAYGATE_USER_ACTIVE, 'userid'=>$userid, 'courseid'=>$courseid);

    if (!$paymentgateways = $DB->get_records_sql($sql, $params)) {
        return false;
    }

    $changes = array();

    foreach ($paymentgateways as $ue) {
        $start = (int)$ue->timestart;
        $end = (int)$ue->timeend;
        if ($end != 0 and $end < $start) {
            debugging('Invalid paymentgateway start or end in user_paymentgateway id:'.$ue->id);
            continue;
        }
        if (isset($changes[$start])) {
            $changes[$start] = $changes[$start] + 1;
        } else {
            $changes[$start] = 1;
        }
        if ($end === 0) {
            // no end
        } else if (isset($changes[$end])) {
            $changes[$end] = $changes[$end] - 1;
        } else {
            $changes[$end] = -1;
        }
    }

    // let's sort then paymentgateway starts&ends and go through them chronologically,
    // looking for current status and the next future end of paymentgateway
    ksort($changes);

    $now = time();
    $current = 0;
    $present = null;

    foreach ($changes as $time => $change) {
        if ($time > $now) {
            if ($present === null) {
                // we have just went past current time
                $present = $current;
                if ($present < 1) {
                    // no paymentgateway active
                    return false;
                }
            }
            if ($present !== null) {
                // we are already in the future - look for possible end
                if ($current + $change < 1) {
                    return $time;
                }
            }
        }
        $current += $change;
    }

    if ($current > 0) {
        return 0;
    } else {
        return false;
    }
}

/**
 * Is current user accessing course via this paymentgateway method?
 *
 * This is intended for operations that are going to affect paygate instances.
 *
 * @param stdClass $instance paygate instance
 * @return bool
 */
function paygate_accessing_via_instance(stdClass $instance) {
    global $DB, $USER;

    if (empty($instance->id)) {
        return false;
    }

    if (is_siteadmin()) {
        // Admins may go anywhere.
        return false;
    }

    return $DB->record_exists('user_paymentgateways', array('userid'=>$USER->id, 'paygateid'=>$instance->id));
}


/**
 * All paygate plugins should be based on this class,
 * this is also the main source of documentation.
 */
abstract class paygate_plugin {
    protected $config = null;

    /**
     * Returns name of this paygate plugin
     * @return string
     */
    public function get_name() {
        // second word in class is always paygate name, sorry, no fancy plugin names with _
        $words = explode('_', get_class($this));
        return $words[1];
    }

    /**
     * Returns localised name of paygate instance
     *
     * @param object $instance (null is accepted too)
     * @return string
     */
    public function get_instance_name($instance) {
        if (empty($instance->name)) {
            $paygate = $this->get_name();
            return get_string('pluginname', 'paygate_'.$paygate);
        } else {
            $context = context_course::instance($instance->courseid);
            return format_string($instance->name, true, array('context'=>$context));
        }
    }

    /**
     * Returns optional paymentgateway information icons.
     *
     * This is used in course list for quick overview of paymentgateway options.
     *
     * We are not using single instance parameter because sometimes
     * we might want to prevent icon repetition when multiple instances
     * of one type exist. One instance may also produce several icons.
     *
     * @param array $instances all paygate instances of this type in one course
     * @return array of pix_icon
     */
    public function get_info_icons(array $instances) {
        return array();
    }

    /**
     * Returns optional paymentgateway instance description text.
     *
     * This is used in detailed course information.
     *
     *
     * @param object $instance
     * @return string short html text
     */
    public function get_description_text($instance) {
        return null;
    }

    /**
     * Makes sure config is loaded and cached.
     * @return void
     */
    protected function load_config() {
        if (!isset($this->config)) {
            $name = $this->get_name();
            $this->config = get_config("paygate_$name");
        }
    }

    /**
     * Returns plugin config value
     * @param  string $name
     * @param  string $default value if config does not exist yet
     * @return string value or default
     */
    public function get_config($name, $default = NULL) {
        $this->load_config();
        return isset($this->config->$name) ? $this->config->$name : $default;
    }

    /**
     * Sets plugin config value
     * @param  string $name name of config
     * @param  string $value string config value, null means delete
     * @return string value
     */
    public function set_config($name, $value) {
        $pluginname = $this->get_name();
        $this->load_config();
        if ($value === NULL) {
            unset($this->config->$name);
        } else {
            $this->config->$name = $value;
        }
        set_config($name, $value, "paygate_$pluginname");
    }

    /**
     * Does this plugin assign protected roles are can they be manually removed?
     * @return bool - false means anybody may tweak roles, it does not use itemid and component when assigning roles
     */
    public function roles_protected() {
        return true;
    }

    /**
     * Does this plugin allow manual paymentgateways?
     *
     * @param stdClass $instance course paygate instance
     * All plugins allowing this must implement 'paygate/xxx:paygate' capability
     *
     * @return bool - true means user with 'paygate/xxx:paygate' may paygate others freely, false means nobody may add more paymentgateways manually
     */
    public function allow_paygate(stdClass $instance) {
        return false;
    }

    /**
     * Does this plugin allow manual unpaymentgateway of all users?
     * All plugins allowing this must implement 'paygate/xxx:unpaygate' capability
     *
     * @param stdClass $instance course paygate instance
     * @return bool - true means user with 'paygate/xxx:unpaygate' may unpaygate others freely, false means nobody may touch user_paymentgateways
     */
    public function allow_unpaygate(stdClass $instance) {
        return false;
    }

    /**
     * Does this plugin allow manual unpaymentgateway of a specific user?
     * All plugins allowing this must implement 'paygate/xxx:unpaygate' capability
     *
     * This is useful especially for synchronisation plugins that
     * do suspend instead of full unpaymentgateway.
     *
     * @param stdClass $instance course paygate instance
     * @param stdClass $ue record from user_paymentgateways table, specifies user
     *
     * @return bool - true means user with 'paygate/xxx:unpaygate' may unpaygate this user, false means nobody may touch this user paymentgateway
     */
    public function allow_unpaygate_user(stdClass $instance, stdClass $ue) {
        return $this->allow_unpaygate($instance);
    }

    /**
     * Does this plugin support some way to user to self paygate?
     *
     * @param stdClass $instance course paygate instance
     *
     * @return bool - true means show "paygate me in this course" link in course UI
     */
    public function show_paygateme_link(stdClass $instance) {
        return false;
    }

    /**
     * Attempt to automatically paygate current user in course without any interaction,
     * calling code has to make sure the plugin and instance are active.
     *
     * This should return either a timestamp in the future or false.
     *
     * @param stdClass $instance course paygate instance
     * @return bool|int false means not paygateled, integer means timeend
     */
    public function try_autopaygate(stdClass $instance) {
        global $USER;

        return false;
    }

    /**
     * Attempt to automatically gain temporary guest access to course,
     * calling code has to make sure the plugin and instance are active.
     *
     * This should return either a timestamp in the future or false.
     *
     * @param stdClass $instance course paygate instance
     * @return bool|int false means no guest access, integer means timeend
     */
    public function try_guestaccess(stdClass $instance) {
        global $USER;

        return false;
    }

    /**
     * Returns link to page which may be used to add new instance of paymentgateway plugin in course.
     * @param int $courseid
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
        // override for most plugins, check if instance already exists in cases only one instance is supported
        return NULL;
    }

    /**
     * Is it possible to delete paygate instance via standard UI?
     *
     * @deprecated since Moodle 2.8 MDL-35864 - please use can_delete_instance() instead.
     * @todo MDL-46479 This will be deleted in Moodle 3.0.
     * @see class_name::can_delete_instance()
     * @param object $instance
     * @return bool
     */
    public function instance_deleteable($instance) {
        debugging('Function paygate_plugin::instance_deleteable() is deprecated', DEBUG_DEVELOPER);
        return $this->can_delete_instance($instance);
    }

    /**
     * Is it possible to delete paygate instance via standard UI?
     *
     * @param stdClass  $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        return false;
    }

    /**
     * Is it possible to hide/show paygate instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        debugging("The paymentgateway plugin '".$this->get_name()."' should override the function can_hide_show_instance().", DEBUG_DEVELOPER);
        return true;
    }

    /**
     * Returns link to manual paygate UI if exists.
     * Does the access control tests automatically.
     *
     * @param object $instance
     * @return moodle_url
     */
    public function get_manual_paygate_link($instance) {
        return NULL;
    }

    /**
     * Returns list of unpaygate links for all paygate instances in course.
     *
     * @param int $instance
     * @return moodle_url or NULL if self unpaymentgateway not supported
     */
    public function get_unpaygateself_link($instance) {
        global $USER, $CFG, $DB;

        $name = $this->get_name();
        if ($instance->paygate !== $name) {
            throw new coding_exception('invalid paygate instance!');
        }

        if ($instance->courseid == SITEID) {
            return NULL;
        }

        if (!paygate_is_enabled($name)) {
            return NULL;
        }

        if ($instance->status != PAYGATE_INSTANCE_ENABLED) {
            return NULL;
        }

        if (!file_exists("$CFG->dirroot/paygate/$name/unpaygateself.php")) {
            return NULL;
        }

        $context = context_course::instance($instance->courseid, MUST_EXIST);

        if (!has_capability("paygate/$name:unpaygateself", $context)) {
            return NULL;
        }

/*
        if (!$DB->record_exists('user_paymentgateways', array('paygateid'=>$instance->id, 'userid'=>$USER->id, 'status'=>PAYGATE_USER_ACTIVE))) {
            return NULL;
        }
*/

        return new moodle_url("/paygate/$name/unpaygateself.php", array('paygateid'=>$instance->id));
    }

    /**
     * Adds paygate instance UI to course edit form
     *
     * @param object $instance paygate instance or null if does not exist yet
     * @param MoodleQuickForm $mform
     * @param object $data
     * @param object $context context of existing course or parent category if course does not exist
     * @return void
     */
    public function course_edit_form($instance, MoodleQuickForm $mform, $data, $context) {
        // override - usually at least enable/disable switch, has to add own form header
    }

    /**
     * Validates course edit form data
     *
     * @param object $instance paygate instance or null if does not exist yet
     * @param array $data
     * @param object $context context of existing course or parent category if course does not exist
     * @return array errors array
     */
    public function course_edit_validation($instance, array $data, $context) {
        return array();
    }

    /**
     * Called after updating/inserting course.
     *
     * @param bool $inserted true if course just inserted
     * @param object $course
     * @param object $data form data
     * @return void
     */
    public function course_updated($inserted, $course, $data) {
        if ($inserted) {
            if ($this->get_config('defaultpaygate')) {
                $this->add_default_instance($course);
            }
        }
    }

    /**
     * Add new instance of paygate plugin.
     * @param object $course
     * @param array instance fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, array $fields = NULL) {
        global $DB;

        if ($course->id == SITEID) {
            throw new coding_exception('Invalid request to add paygate instance to frontpage.');
        }

        $instance = new stdClass();
        $instance->paygate          = $this->get_name();
        $instance->status         = PAYGATE_INSTANCE_ENABLED;
        $instance->courseid       = $course->id;
        $instance->paygatestartdate = 0;
        $instance->paygateenddate   = 0;
        $instance->timemodified   = time();
        $instance->timecreated    = $instance->timemodified;
        $instance->sortorder      = $DB->get_field('paygate', 'COALESCE(MAX(sortorder), -1) + 1', array('courseid'=>$course->id));

        $fields = (array)$fields;
        unset($fields['paygate']);
        unset($fields['courseid']);
        unset($fields['sortorder']);
        foreach($fields as $field=>$value) {
            $instance->$field = $value;
        }

        return $DB->insert_record('paygate', $instance);
    }

    /**
     * Add new instance of paygate plugin with default settings,
     * called when adding new instance manually or when adding new course.
     *
     * Not all plugins support this.
     *
     * @param object $course
     * @return int id of new instance or null if no default supported
     */
    public function add_default_instance($course) {
        return null;
    }

    /**
     * Update instance status
     *
     * Override when plugin needs to do some action when enabled or disabled.
     *
     * @param stdClass $instance
     * @param int $newstatus PAYGATE_INSTANCE_ENABLED, PAYGATE_INSTANCE_DISABLED
     * @return void
     */
    public function update_status($instance, $newstatus) {
        global $DB;

        $instance->status = $newstatus;
        $DB->update_record('paygate', $instance);

        // invalidate all paygate caches
        $context = context_course::instance($instance->courseid);
        $context->mark_dirty();
    }

    /**
     * Creates course paygate form, checks if form submitted
     * and paygates user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    public function paygate_page_hook(stdClass $instance) {
        return null;
    }

    /**
     * Checks if user can self paygate.
     *
     * @param stdClass $instance paymentgateway instance
     * @param bool $checkuserpaymentgateway if true will check if user paymentgateway is inactive.
     *             used by navigation to improve performance.
     * @return bool|string true if successful, else error message or false
     */
    public function can_self_paygate(stdClass $instance, $checkuserpaymentgateway = true) {
        return false;
    }

    /**
     * Return information for paymentgateway instance containing list of parameters required
     * for paymentgateway, name of paymentgateway plugin etc.
     *
     * @param stdClass $instance paymentgateway instance
     * @return array instance info.
     */
    public function get_paygate_info(stdClass $instance) {
        return null;
    }

    /**
     * Adds navigation links into course admin block.
     *
     * By defaults looks for manage links only.
     *
     * @param navigation_node $instancesnode
     * @param stdClass $instance
     * @return void
     */
    public function add_course_navigation($instancesnode, stdClass $instance) {
        // usually adds manage users
    }

    /**
     * Returns edit icons for the page with list of instances
     * @param stdClass $instance
     * @return array
     */
    public function get_action_icons(stdClass $instance) {
        return array();
    }

    /**
     * Reads version.php and determines if it is necessary
     * to execute the cron job now.
     * @return bool
     */
    public function is_cron_required() {
        global $CFG;

        $name = $this->get_name();
        $versionfile = "$CFG->dirroot/paygate/$name/version.php";
        $plugin = new stdClass();
        include($versionfile);
        if (empty($plugin->cron)) {
            return false;
        }
        $lastexecuted = $this->get_config('lastcron', 0);
        if ($lastexecuted + $plugin->cron < time()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Called for all enabled paygate plugins that returned true from is_cron_required().
     * @return void
     */
    public function cron() {
    }


    /**
     * Returns an paygate_user_button that takes the user to a page where they are able to
     * paygate users into the managers course through this plugin.
     *
     * Optional: If the plugin supports manual paymentgateways it can choose to override this
     * otherwise it shouldn't
     *
     * @param course_paymentgateway_manager $manager
     * @return paygate_user_button|false
     */
    public function get_manual_paygate_button(course_paymentgateway_manager $manager) {
        return false;
    }

    /**
     * Gets an array of the user paymentgateway actions
     *
     * @param course_paymentgateway_manager $manager
     * @param stdClass $ue
     * @return array An array of user_paymentgateway_actions
     */
    public function get_user_paymentgateway_actions(course_paymentgateway_manager $manager, $ue) {
        return array();
    }

    /**
     * Returns true if the plugin has one or more bulk operations that can be performed on
     * user paymentgateways.
     *
     * @param course_paymentgateway_manager $manager
     * @return bool
     */
    public function has_bulk_operations(course_paymentgateway_manager $manager) {
       return false;
    }

    /**
     * Return an array of paygate_bulk_paymentgateway_operation objects that define
     * the bulk actions that can be performed on user paymentgateways by the plugin.
     *
     * @param course_paymentgateway_manager $manager
     * @return array
     */
    public function get_bulk_operations(course_paymentgateway_manager $manager) {
        return array();
    }



    /**
     * Returns the user who is responsible for paymentgateways for given instance.
     *
     * Override if plugin knows anybody better than admin.
     *
     * @param int $instanceid paymentgateway instance id
     * @return stdClass user record
     */
    protected function get_paygateler($instanceid) {
        return get_admin();
    }

    /**
     * Notify user about incoming expiration of their paymentgateway,
     * it is called only if notification of paygateled users (aka students) is enabled in course.
     *
     * This is executed only once for each expiring paymentgateway right
     * at the start of the expiration threshold.
     *
     * @param stdClass $user
     * @param stdClass $ue
     * @param progress_trace $trace
     */
    protected function notify_expiry_paygateled($user, $ue, progress_trace $trace) {
        global $CFG;

        $name = $this->get_name();

        $oldforcelang = force_current_language($user->lang);

        $paygateler = $this->get_paygateler($ue->paygateid);
        $context = context_course::instance($ue->courseid);

        $a = new stdClass();
        $a->course   = format_string($ue->fullname, true, array('context'=>$context));
        $a->user     = fullname($user, true);
        $a->timeend  = userdate($ue->timeend, '', $user->timezone);
        $a->paygateler = fullname($paygateler, has_capability('moodle/site:viewfullnames', $context, $user));

        $subject = get_string('expirymessagepaygateledsubject', 'paygate_'.$name, $a);
        $body = get_string('expirymessagepaygateledbody', 'paygate_'.$name, $a);

        $message = new stdClass();
        $message->notification      = 1;
        $message->component         = 'paygate_'.$name;
        $message->name              = 'expiry_notification';
        $message->userfrom          = $paygateler;
        $message->userto            = $user;
        $message->subject           = $subject;
        $message->fullmessage       = $body;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml   = markdown_to_html($body);
        $message->smallmessage      = $subject;
        $message->contexturlname    = $a->course;
        $message->contexturl        = (string)new moodle_url('/course/view.php', array('id'=>$ue->courseid));

        if (message_send($message)) {
            $trace->output("notifying user $ue->userid that paymentgateway in course $ue->courseid expires on ".userdate($ue->timeend, '', $CFG->timezone), 1);
        } else {
            $trace->output("error notifying user $ue->userid that paymentgateway in course $ue->courseid expires on ".userdate($ue->timeend, '', $CFG->timezone), 1);
        }

        force_current_language($oldforcelang);
    }

    /**
     * Notify person responsible for paymentgateways that some user paymentgateways will be expired soon,
     * it is called only if notification of paygatelers (aka teachers) is enabled in course.
     *
     * This is called repeatedly every day for each course if there are any pending expiration
     * in the expiration threshold.
     *
     * @param int $eid
     * @param array $users
     * @param progress_trace $trace
     */
    protected function notify_expiry_paygateler($eid, $users, progress_trace $trace) {
        global $DB;

        $name = $this->get_name();

        $instance = $DB->get_record('paygate', array('id'=>$eid, 'paygate'=>$name));
        $context = context_course::instance($instance->courseid);
        $course = $DB->get_record('course', array('id'=>$instance->courseid));

        $paygateler = $this->get_paygateler($instance->id);
        $admin = get_admin();

        $oldforcelang = force_current_language($paygateler->lang);

        foreach($users as $key=>$info) {
            $users[$key] = '* '.$info['fullname'].' - '.userdate($info['timeend'], '', $paygateler->timezone);
        }

        $a = new stdClass();
        $a->course    = format_string($course->fullname, true, array('context'=>$context));
        $a->threshold = get_string('numdays', '', $instance->expirythreshold / (60*60*24));
        $a->users     = implode("\n", $users);
        $a->extendurl = (string)new moodle_url('/paygate/users.php', array('id'=>$instance->courseid));

        $subject = get_string('expirymessagepaygatelersubject', 'paygate_'.$name, $a);
        $body = get_string('expirymessagepaygatelerbody', 'paygate_'.$name, $a);

        $message = new stdClass();
        $message->notification      = 1;
        $message->component         = 'paygate_'.$name;
        $message->name              = 'expiry_notification';
        $message->userfrom          = $admin;
        $message->userto            = $paygateler;
        $message->subject           = $subject;
        $message->fullmessage       = $body;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml   = markdown_to_html($body);
        $message->smallmessage      = $subject;
        $message->contexturlname    = $a->course;
        $message->contexturl        = $a->extendurl;

        if (message_send($message)) {
            $trace->output("notifying user $paygateler->id about all expiring $name paymentgateways in course $instance->courseid", 1);
        } else {
            $trace->output("error notifying user $paygateler->id about all expiring $name paymentgateways in course $instance->courseid", 1);
        }

        force_current_language($oldforcelang);
    }

    /**
     * Backup execution step hook to annotate custom fields.
     *
     * @param backup_paymentgateways_execution_step $step
     * @param stdClass $paygate
     */
    public function backup_annotate_custom_fields(backup_paymentgateways_execution_step $step, stdClass $paygate) {
        // Override as necessary to annotate custom fields in the paygate table.
    }

    /**
     * Automatic paygate sync executed during restore.
     * Useful for automatic sync by course->idnumber or course category.
     * @param stdClass $course course record
     */
    public function restore_sync_course($course) {
        // Override if necessary.
    }

    /**
     * Restore instance and map settings.
     *
     * @param restore_paymentgateways_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
/**
    public function restore_instance(restore_paymentgateways_structure_step $step, stdClass $data, $course, $oldid) {
        // Do not call this from overridden methods, restore and set new id there.
        $step->set_mapping('paygate', $oldid, 0);
    }
*/
    /**
     * Restore user paymentgateway.
     *
     * @param restore_paymentgateways_structure_step $step
     * @param stdClass $data
     * @param stdClass $instance
     * @param int $oldinstancestatus
     * @param int $userid
     */
    public function restore_user_paymentgateway(restore_paymentgateways_structure_step $step, $data, $instance, $userid, $oldinstancestatus) {
        // Override as necessary if plugin supports restore of paymentgateways.
    }

    /**
     * Restore role assignment.
     *
     * @param stdClass $instance
     * @param int $roleid
     * @param int $userid
     * @param int $contextid
     */
    public function restore_role_assignment($instance, $roleid, $userid, $contextid) {
        // No role assignment by default, override if necessary.
    }

    /**
     * Restore user group membership.
     * @param stdClass $instance
     * @param int $groupid
     * @param int $userid
     */
    public function restore_group_member($instance, $groupid, $userid) {
        // Implement if you want to restore protected group memberships,
        // usually this is not necessary because plugins should be able to recreate the memberships automatically.
    }
}
