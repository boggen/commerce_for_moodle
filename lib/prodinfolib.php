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
 * needs major re work of all code (copied from moodle/lib/prodinfolib.php)
 */ 
/**
 * This library includes the basic parts of prodinfo api.
 * It is available on each page.
 *
 * @package    core
 * @subpackage prodinfo
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** Course prodinfo instance enabled. (used in prodinfo->status) */
define('prodinfo_INSTANCE_ENABLED', 0);

/** Course prodinfo instance disabled, user may enter course if other prodinfo instance enabled. (used in prodinfo->status)*/
define('prodinfo_INSTANCE_DISABLED', 1);

/** User is active participant (used in user_paymentgateways->status)*/
define('prodinfo_USER_ACTIVE', 0);

/** User participation in course is suspended (used in user_paymentgateways->status) */
define('prodinfo_USER_SUSPENDED', 1);

/** @deprecated - prodinfo caching was reworked, use prodinfo_MAX_TIMESTAMP instead */
define('prodinfo_REQUIRE_LOGIN_CACHE_PERIOD', 1800);

/** The timestamp indicating forever */
define('prodinfo_MAX_TIMESTAMP', 2147483647);

/** When user disappears from external source, the paymentgateway is completely removed */
define('prodinfo_EXT_REMOVED_UNprodinfo', 0);

/** When user disappears from external source, the paymentgateway is kept as is - one way sync */
define('prodinfo_EXT_REMOVED_KEEP', 1);

/** @deprecated since 2.4 not used any more, migrate plugin to new restore methods */
define('prodinfo_RESTORE_TYPE', 'prodinforestore');

/**
 * When user disappears from external source, user paymentgateway is suspended, roles are kept as is.
 * In some cases user needs a role with some capability to be visible in UI - suc has in gradebook,
 * assignments, etc.
 */
define('prodinfo_EXT_REMOVED_SUSPEND', 2);

/**
 * When user disappears from external source, the paymentgateway is suspended and roles assigned
 * by prodinfo instance are removed. Please note that user may "disappear" from gradebook and other areas.
 * */
define('prodinfo_EXT_REMOVED_SUSPENDNOROLES', 3);

/**
 * Returns instances of prodinfo plugins
 * @param bool $enabled return enabled only
 * @return array of prodinfo plugins name=>instance
 */
function prodinfo_get_plugins($enabled) {
    global $CFG;

    $result = array();

    if ($enabled) {
        // sorted by enabled plugin order
        $enabled = explode(',', $CFG->prodinfo_plugins_enabled);
        $plugins = array();
        foreach ($enabled as $plugin) {
            $plugins[$plugin] = "$CFG->dirroot/prodinfo/$plugin";
        }
    } else {
        // sorted alphabetically
        $plugins = core_component::get_plugin_list('prodinfo');
        ksort($plugins);
    }

    foreach ($plugins as $plugin=>$location) {
        $class = "prodinfo_{$plugin}_plugin";
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
 * Returns instance of prodinfo plugin
 * @param  string $name name of prodinfo plugin ('manual', 'guest', ...)
 * @return prodinfo_plugin
 */
function prodinfo_get_plugin($name) {
    global $CFG;

    $name = clean_param($name, PARAM_PLUGIN);

    if (empty($name)) {
        // ignore malformed or missing plugin names completely
        return null;
    }

    $location = "$CFG->dirroot/prodinfo/$name";

    if (!file_exists("$location/lib.php")) {
        return null;
    }
    include_once("$location/lib.php");
    $class = "prodinfo_{$name}_plugin";
    if (!class_exists($class)) {
        return null;
    }

    return new $class();
}

/**
 * Returns paymentgateway instances in given course.
 * @param int $courseid
 * @param bool $enabled
 * @return array of prodinfo instances
 */

function prodinfo_get_instances($courseid, $enabled) {
    global $DB, $CFG;

    if (!$enabled) {
        return $DB->get_records('prodinfo', array('courseid'=>$courseid), 'sortorder,id');
    }

    $result = $DB->get_records('prodinfo', array('courseid'=>$courseid, 'status'=>prodinfo_INSTANCE_ENABLED), 'sortorder,id');

    $enabled = explode(',', $CFG->prodinfo_plugins_enabled);
    foreach ($result as $key=>$instance) {
        if (!in_array($instance->prodinfo, $enabled)) {
            unset($result[$key]);
            continue;
        }
        if (!file_exists("$CFG->dirroot/prodinfo/$instance->prodinfo/lib.php")) {
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
 * @param string $prodinfo paymentgateway plugin name
 * @return boolean Whether the plugin is enabled
 */
function prodinfo_is_enabled($prodinfo) {
    global $CFG;

    if (empty($CFG->prodinfo_plugins_enabled)) {
        return false;
    }
    return in_array($prodinfo, explode(',', $CFG->prodinfo_plugins_enabled));
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
function prodinfo_check_plugins($user) {
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

    $enabled = prodinfo_get_plugins(true);

    foreach($enabled as $prodinfo) {
        $prodinfo->sync_user_paymentgateways($user);
    }

    unset($inprogress[$user->id]);  // Unset the flag
}

/**
 * Do these two students share any course?
 *
 * The courses has to be visible and paymentgateways has to be active,
 * timestart and timeend restrictions are ignored.
 *
 * This function calls {@see prodinfo_get_shared_courses()} setting checkexistsonly
 * to true.
 *
 * @param stdClass|int $user1
 * @param stdClass|int $user2
 * @return bool
 */

function prodinfo_sharing_course($user1, $user2) {
    return prodinfo_get_shared_courses($user1, $user2, false, true);
}

/**
 * Returns any courses shared by the two users
 *
 * The courses has to be visible and paymentgateways has to be active,
 * timestart and timeend restrictions are ignored.
 *
 * @global moodle_database $DB
 * @param stdClass|int $user1
 * @param stdClass|int $user2
 * @param bool $preloadcontexts If set to true contexts for the returned courses
 *              will be preloaded.
 * @param bool $checkexistsonly If set to true then this function will return true
 *              if the users share any courses and false if not.
 * @return array|bool An array of courses that both users are prodinfoled in OR if
 *              $checkexistsonly set returns true if the users share any courses
 *              and false if not.
 */

function prodinfo_get_shared_courses($user1, $user2, $preloadcontexts = false, $checkexistsonly = false) {
    global $DB, $CFG;

    $user1 = isset($user1->id) ? $user1->id : $user1;
    $user2 = isset($user2->id) ? $user2->id : $user2;

    if (empty($user1) or empty($user2)) {
        return false;
    }

    if (!$plugins = explode(',', $CFG->prodinfo_plugins_enabled)) {
        return false;
    }

    list($plugins, $params) = $DB->get_in_or_equal($plugins, SQL_PARAMS_NAMED, 'ee');
    $params['enabled'] = prodinfo_INSTANCE_ENABLED;
    $params['active1'] = prodinfo_USER_ACTIVE;
    $params['active2'] = prodinfo_USER_ACTIVE;
    $params['user1']   = $user1;
    $params['user2']   = $user2;

    $ctxselect = '';
    $ctxjoin = '';
    if ($preloadcontexts) {
        $ctxselect = ', ' . context_helper::get_preload_record_columns_sql('ctx');
        $ctxjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)";
        $params['contextlevel'] = CONTEXT_COURSE;
    }

    $sql = "SELECT c.* $ctxselect
              FROM {course} c
              JOIN (
                SELECT DISTINCT c.id
                  FROM {prodinfo} e
                  JOIN {user_paymentgateways} ue1 ON (ue1.prodinfoid = e.id AND ue1.status = :active1 AND ue1.userid = :user1)
                  JOIN {user_paymentgateways} ue2 ON (ue2.prodinfoid = e.id AND ue2.status = :active2 AND ue2.userid = :user2)
                  JOIN {course} c ON (c.id = e.courseid AND c.visible = 1)
                 WHERE e.status = :enabled AND e.prodinfo $plugins
              ) ec ON ec.id = c.id
              $ctxjoin";

    if ($checkexistsonly) {
        return $DB->record_exists_sql($sql, $params);
    } else {
        $courses = $DB->get_records_sql($sql, $params);
        if ($preloadcontexts) {
            array_map('context_helper::preload_from_record', $courses);
        }
        return $courses;
    }
}

/**
 * This function adds necessary prodinfo plugins UI into the course edit form.
 *
 * @param MoodleQuickForm $mform
 * @param object $data course edit form data
 * @param object $context context of existing course or parent category if course does not exist
 * @return void
 */
function prodinfo_course_edit_form(MoodleQuickForm $mform, $data, $context) {
    $plugins = prodinfo_get_plugins(true);
    if (!empty($data->id)) {
        $instances = prodinfo_get_instances($data->id, false);
        foreach ($instances as $instance) {
            if (!isset($plugins[$instance->prodinfo])) {
                continue;
            }
            $plugin = $plugins[$instance->prodinfo];
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
function prodinfo_course_edit_validation(array $data, $context) {
    $errors = array();
    $plugins = prodinfo_get_plugins(true);

    if (!empty($data['id'])) {
        $instances = prodinfo_get_instances($data['id'], false);
        foreach ($instances as $instance) {
            if (!isset($plugins[$instance->prodinfo])) {
                continue;
            }
            $plugin = $plugins[$instance->prodinfo];
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
 * Update prodinfo instances after course edit form submission
 * @param bool $inserted true means new course added, false course already existed
 * @param object $course
 * @param object $data form data
 * @return void
 */
function prodinfo_course_updated($inserted, $course, $data) {
    global $DB, $CFG;

    $plugins = prodinfo_get_plugins(true);

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
function prodinfo_add_course_navigation(navigation_node $coursenode, $course) {
    global $CFG;

    $coursecontext = context_course::instance($course->id);

    $instances = prodinfo_get_instances($course->id, true);
    $plugins   = prodinfo_get_plugins(true);

    // we do not want to break all course pages if there is some borked prodinfo plugin, right?
    foreach ($instances as $k=>$instance) {
        if (!isset($plugins[$instance->prodinfo])) {
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
        if (has_capability('moodle/course:prodinforeview', $coursecontext)) {
            $url = new moodle_url('/prodinfo/users.php', array('id'=>$course->id));
            $usersnode->add(get_string('prodinfoledusers', 'prodinfo'), $url, navigation_node::TYPE_SETTING, null, 'review', new pix_icon('i/prodinfousers', ''));
        }

        // manage prodinfo plugin instances
        if (has_capability('moodle/course:prodinfoconfig', $coursecontext) or has_capability('moodle/course:prodinforeview', $coursecontext)) {
            $url = new moodle_url('/prodinfo/instances.php', array('id'=>$course->id));
        } else {
            $url = NULL;
        }
        $instancesnode = $usersnode->add(get_string('paymentgatewayinstances', 'prodinfo'), $url, navigation_node::TYPE_SETTING, null, 'manageinstances');

        // each instance decides how to configure itself or how many other nav items are exposed
        foreach ($instances as $instance) {
            if (!isset($plugins[$instance->prodinfo])) {
                continue;
            }
            $plugins[$instance->prodinfo]->add_course_navigation($instancesnode, $instance);
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

     // Deal somehow with users that are not prodinfoled but still got a role somehow
    if ($course->id != SITEID) {
        //TODO, create some new UI for role assignments at course level
        if (has_capability('moodle/course:reviewotherusers', $coursecontext)) {
            $url = new moodle_url('/prodinfo/otherusers.php', array('id'=>$course->id));
            $usersnode->add(get_string('notprodinfoledusers', 'prodinfo'), $url, navigation_node::TYPE_SETTING, null, 'otherusers', new pix_icon('i/assignroles', ''));
        }
    }

    // just in case nothing was actually added
    $usersnode->trim_if_empty();

    if ($course->id != SITEID) {
        if (isguestuser() or !isloggedin()) {
            // guest account can not be paymentgateway - no links for them
        } else if (is_prodinfoled($coursecontext)) {
            // unprodinfo link if possible
            foreach ($instances as $instance) {
                if (!isset($plugins[$instance->prodinfo])) {
                    continue;
                }
                $plugin = $plugins[$instance->prodinfo];
                if ($unprodinfolink = $plugin->get_unprodinfoself_link($instance)) {
                    $shortname = format_string($course->shortname, true, array('context' => $coursecontext));
                    $coursenode->add(get_string('unprodinfome', 'core_prodinfo', $shortname), $unprodinfolink, navigation_node::TYPE_SETTING, null, 'unprodinfoself', new pix_icon('i/user', ''));
                    break;
                    //TODO. deal with multiple unprodinfo links - not likely case, but still...
                }
            }
        } else {
            // prodinfo link if possible
            if (is_viewing($coursecontext)) {
                // better not show any prodinfo link, this is intended for managers and inspectors
            } else {
                foreach ($instances as $instance) {
                    if (!isset($plugins[$instance->prodinfo])) {
                        continue;
                    }
                    $plugin = $plugins[$instance->prodinfo];
                    if ($plugin->show_prodinfome_link($instance)) {
                        $url = new moodle_url('/prodinfo/index.php', array('id'=>$course->id));
                        $shortname = format_string($course->shortname, true, array('context' => $coursecontext));
                        $coursenode->add(get_string('prodinfome', 'core_prodinfo', $shortname), $url, navigation_node::TYPE_SETTING, null, 'prodinfoself', new pix_icon('i/user', ''));
                        break;
                    }
                }
            }
        }
    }
}

/**
 * Returns list of courses current $USER is paymentgateway in and can access
 *
 * - $fields is an array of field names to ADD
 *   so name the fields you really need, which will
 *   be added and uniq'd
 *
 * @param string|array $fields
 * @param string $sort
 * @param int $limit max number of courses
 * @return array
 */
function prodinfo_get_my_courses($fields = NULL, $sort = 'visible DESC,sortorder ASC', $limit = 0) {
    global $DB, $USER;

    // Guest account does not have any courses
    if (isguestuser() or !isloggedin()) {
        return(array());
    }

    $basefields = array('id', 'category', 'sortorder',
                        'shortname', 'fullname', 'idnumber',
                        'startdate', 'visible',
                        'groupmode', 'groupmodeforce', 'cacherev');

    if (empty($fields)) {
        $fields = $basefields;
    } else if (is_string($fields)) {
        // turn the fields from a string to an array
        $fields = explode(',', $fields);
        $fields = array_map('trim', $fields);
        $fields = array_unique(array_merge($basefields, $fields));
    } else if (is_array($fields)) {
        $fields = array_unique(array_merge($basefields, $fields));
    } else {
        throw new coding_exception('Invalid $fileds parameter in prodinfo_get_my_courses()');
    }
    if (in_array('*', $fields)) {
        $fields = array('*');
    }

    $orderby = "";
    $sort    = trim($sort);
    if (!empty($sort)) {
        $rawsorts = explode(',', $sort);
        $sorts = array();
        foreach ($rawsorts as $rawsort) {
            $rawsort = trim($rawsort);
            if (strpos($rawsort, 'c.') === 0) {
                $rawsort = substr($rawsort, 2);
            }
            $sorts[] = trim($rawsort);
        }
        $sort = 'c.'.implode(',c.', $sorts);
        $orderby = "ORDER BY $sort";
    }

    $wheres = array("c.id <> :siteid");
    $params = array('siteid'=>SITEID);

    if (isset($USER->loginascontext) and $USER->loginascontext->contextlevel == CONTEXT_COURSE) {
        // list _only_ this course - anything else is asking for trouble...
        $wheres[] = "courseid = :loginas";
        $params['loginas'] = $USER->loginascontext->instanceid;
    }

    $coursefields = 'c.' .join(',c.', $fields);
    $ccselect = ', ' . context_helper::get_preload_record_columns_sql('ctx');
    $ccjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)";
    $params['contextlevel'] = CONTEXT_COURSE;
    $wheres = implode(" AND ", $wheres);

    //note: we can not use DISTINCT + text fields due to Oracle and MS limitations, that is why we have the subselect there
    $sql = "SELECT $coursefields $ccselect
              FROM {course} c
              JOIN (SELECT DISTINCT e.courseid
                      FROM {prodinfo} e
                      JOIN {user_paymentgateways} ue ON (ue.prodinfoid = e.id AND ue.userid = :userid)
                     WHERE ue.status = :active AND e.status = :enabled AND ue.timestart < :now1 AND (ue.timeend = 0 OR ue.timeend > :now2)
                   ) en ON (en.courseid = c.id)
           $ccjoin
             WHERE $wheres
          $orderby";
    $params['userid']  = $USER->id;
    $params['active']  = prodinfo_USER_ACTIVE;
    $params['enabled'] = prodinfo_INSTANCE_ENABLED;
    $params['now1']    = round(time(), -2); // improves db caching
    $params['now2']    = $params['now1'];

    $courses = $DB->get_records_sql($sql, $params, 0, $limit);

    // preload contexts and check visibility
    foreach ($courses as $id=>$course) {
        context_helper::preload_from_record($course);
        if (!$course->visible) {
            if (!$context = context_course::instance($id, IGNORE_MISSING)) {
                unset($courses[$id]);
                continue;
            }
            if (!has_capability('moodle/course:viewhiddencourses', $context)) {
                unset($courses[$id]);
                continue;
            }
        }
        $courses[$id] = $course;
    }

    //wow! Is that really all? :-D

    return $courses;
}

/**
 * Returns course paymentgateway information icons.
 *
 * @param object $course
 * @param array $instances prodinfo instances of this course, improves performance
 * @return array of pix_icon
 */
function prodinfo_get_course_info_icons($course, array $instances = NULL) {
    $icons = array();
    if (is_null($instances)) {
        $instances = prodinfo_get_instances($course->id, true);
    }
    $plugins = prodinfo_get_plugins(true);
    foreach ($plugins as $name => $plugin) {
        $pis = array();
        foreach ($instances as $instance) {
            if ($instance->status != prodinfo_INSTANCE_ENABLED or $instance->courseid != $course->id) {
                debugging('Invalid instances parameter submitted in prodinfo_get_info_icons()');
                continue;
            }
            if ($instance->prodinfo == $name) {
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
function prodinfo_get_course_description_texts($course) {
    $lines = array();
    $instances = prodinfo_get_instances($course->id, true);
    $plugins = prodinfo_get_plugins(true);
    foreach ($instances as $instance) {
        if (!isset($plugins[$instance->prodinfo])) {
            //weird
            continue;
        }
        $plugin = $plugins[$instance->prodinfo];
        $text = $plugin->get_description_text($instance);
        if ($text !== NULL) {
            $lines[] = $text;
        }
    }
    return $lines;
}

/**
 * Returns list of courses user is prodinfoled into.
 * (Note: use prodinfo_get_all_users_courses if you want to use the list wihtout any cap checks )
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
function prodinfo_get_users_courses($userid, $onlyactive = false, $fields = NULL, $sort = 'visible DESC,sortorder ASC') {
    global $DB;

    $courses = prodinfo_get_all_users_courses($userid, $onlyactive, $fields, $sort);

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
 * Can user access at least one prodinfoled course?
 *
 * Cheat if necessary, but find out as fast as possible!
 *
 * @param int|stdClass $user null means use current user
 * @return bool
 */
function prodinfo_user_sees_own_courses($user = null) {
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
        if (!empty($USER->prodinfo['prodinfoled'])) {
            foreach ($USER->prodinfo['prodinfoled'] as $until) {
                if ($until > time()) {
                    return true;
                }
            }
        }
    }

    // Now the slow way.
    $courses = prodinfo_get_all_users_courses($userid, true);
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
 * Returns list of courses user is prodinfoled into without any capability checks
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
function prodinfo_get_all_users_courses($userid, $onlyactive = false, $fields = NULL, $sort = 'visible DESC,sortorder ASC') {
    global $DB;

    // Guest account does not have any courses
    if (isguestuser($userid) or empty($userid)) {
        return(array());
    }

    $basefields = array('id', 'category', 'sortorder',
            'shortname', 'fullname', 'idnumber',
            'startdate', 'visible',
            'groupmode', 'groupmodeforce');

    if (empty($fields)) {
        $fields = $basefields;
    } else if (is_string($fields)) {
        // turn the fields from a string to an array
        $fields = explode(',', $fields);
        $fields = array_map('trim', $fields);
        $fields = array_unique(array_merge($basefields, $fields));
    } else if (is_array($fields)) {
        $fields = array_unique(array_merge($basefields, $fields));
    } else {
        throw new coding_exception('Invalid $fileds parameter in prodinfo_get_my_courses()');
    }
    if (in_array('*', $fields)) {
        $fields = array('*');
    }

    $orderby = "";
    $sort    = trim($sort);
    if (!empty($sort)) {
        $rawsorts = explode(',', $sort);
        $sorts = array();
        foreach ($rawsorts as $rawsort) {
            $rawsort = trim($rawsort);
            if (strpos($rawsort, 'c.') === 0) {
                $rawsort = substr($rawsort, 2);
            }
            $sorts[] = trim($rawsort);
        }
        $sort = 'c.'.implode(',c.', $sorts);
        $orderby = "ORDER BY $sort";
    }

    $params = array('siteid'=>SITEID);

    if ($onlyactive) {
        $subwhere = "WHERE ue.status = :active AND e.status = :enabled AND ue.timestart < :now1 AND (ue.timeend = 0 OR ue.timeend > :now2)";
        $params['now1']    = round(time(), -2); // improves db caching
        $params['now2']    = $params['now1'];
        $params['active']  = prodinfo_USER_ACTIVE;
        $params['enabled'] = prodinfo_INSTANCE_ENABLED;
    } else {
        $subwhere = "";
    }

    $coursefields = 'c.' .join(',c.', $fields);
    $ccselect = ', ' . context_helper::get_preload_record_columns_sql('ctx');
    $ccjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)";
    $params['contextlevel'] = CONTEXT_COURSE;

    //note: we can not use DISTINCT + text fields due to Oracle and MS limitations, that is why we have the subselect there
    $sql = "SELECT $coursefields $ccselect
              FROM {course} c
              JOIN (SELECT DISTINCT e.courseid
                      FROM {prodinfo} e
                      JOIN {user_paymentgateways} ue ON (ue.prodinfoid = e.id AND ue.userid = :userid)
                 $subwhere
                   ) en ON (en.courseid = c.id)
           $ccjoin
             WHERE c.id <> :siteid
          $orderby";
    $params['userid']  = $userid;

    $courses = $DB->get_records_sql($sql, $params);

    return $courses;
}



/**
 * Called when user is about to be deleted.
 * @param object $user
 * @return void
 */
function prodinfo_user_delete($user) {
    global $DB;

    $plugins = prodinfo_get_plugins(true);
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
function prodinfo_course_delete($course) {
    global $DB;

    $instances = prodinfo_get_instances($course->id, false);
    $plugins = prodinfo_get_plugins(true);
    foreach ($instances as $instance) {
        if (isset($plugins[$instance->prodinfo])) {
            $plugins[$instance->prodinfo]->delete_instance($instance);
        }
        // low level delete in case plugin did not do it
        $DB->delete_records('user_paymentgateways', array('prodinfoid'=>$instance->id));
        $DB->delete_records('role_assignments', array('itemid'=>$instance->id, 'component'=>'prodinfo_'.$instance->prodinfo));
        $DB->delete_records('user_paymentgateways', array('prodinfoid'=>$instance->id));
        $DB->delete_records('prodinfo', array('id'=>$instance->id));
    }
}

/**
 * Try to prodinfo user via default internal auth plugin.
 *
 * For now this is always using the manual prodinfo plugin...
 *
 * @param $courseid
 * @param $userid
 * @param $roleid
 * @param $timestart
 * @param $timeend
 * @return bool success
 */
function prodinfo_try_internal_prodinfo($courseid, $userid, $roleid = null, $timestart = 0, $timeend = 0) {
    global $DB;

    //note: this is hardcoded to manual plugin for now

    if (!prodinfo_is_enabled('manual')) {
        return false;
    }

    if (!$prodinfo = prodinfo_get_plugin('manual')) {
        return false;
    }
    if (!$instances = $DB->get_records('prodinfo', array('prodinfo'=>'manual', 'courseid'=>$courseid, 'status'=>prodinfo_INSTANCE_ENABLED), 'sortorder,id ASC')) {
        return false;
    }
    $instance = reset($instances);

    $prodinfo->prodinfo_user($instance, $userid, $roleid, $timestart, $timeend);

    return true;
}

/**
 * Is there a chance users might self prodinfo
 * @param int $courseid
 * @return bool
 */
function prodinfo_selfprodinfo_available($courseid) {
    $result = false;

    $plugins = prodinfo_get_plugins(true);
    $prodinfoinstances = prodinfo_get_instances($courseid, true);
    foreach($prodinfoinstances as $instance) {
        if (!isset($plugins[$instance->prodinfo])) {
            continue;
        }
        if ($instance->prodinfo === 'guest') {
            // blacklist known temporary guest plugins
            continue;
        }
        if ($plugins[$instance->prodinfo]->show_prodinfome_link($instance)) {
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
function prodinfo_get_paymentgateway_end($courseid, $userid) {
    global $DB;

    $sql = "SELECT ue.*
              FROM {user_paymentgateways} ue
              JOIN {prodinfo} e ON (e.id = ue.prodinfoid AND e.courseid = :courseid)
              JOIN {user} u ON u.id = ue.userid
             WHERE ue.userid = :userid AND ue.status = :active AND e.status = :enabled AND u.deleted = 0";
    $params = array('enabled'=>prodinfo_INSTANCE_ENABLED, 'active'=>prodinfo_USER_ACTIVE, 'userid'=>$userid, 'courseid'=>$courseid);

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
 * This is intended for operations that are going to affect prodinfo instances.
 *
 * @param stdClass $instance prodinfo instance
 * @return bool
 */
function prodinfo_accessing_via_instance(stdClass $instance) {
    global $DB, $USER;

    if (empty($instance->id)) {
        return false;
    }

    if (is_siteadmin()) {
        // Admins may go anywhere.
        return false;
    }

    return $DB->record_exists('user_paymentgateways', array('userid'=>$USER->id, 'prodinfoid'=>$instance->id));
}


/**
 * All prodinfo plugins should be based on this class,
 * this is also the main source of documentation.
 */
abstract class prodinfo_plugin {
    protected $config = null;

    /**
     * Returns name of this prodinfo plugin
     * @return string
     */
    public function get_name() {
        // second word in class is always prodinfo name, sorry, no fancy plugin names with _
        $words = explode('_', get_class($this));
        return $words[1];
    }

    /**
     * Returns localised name of prodinfo instance
     *
     * @param object $instance (null is accepted too)
     * @return string
     */
    public function get_instance_name($instance) {
        if (empty($instance->name)) {
            $prodinfo = $this->get_name();
            return get_string('pluginname', 'prodinfo_'.$prodinfo);
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
     * @param array $instances all prodinfo instances of this type in one course
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
            $this->config = get_config("prodinfo_$name");
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
        set_config($name, $value, "prodinfo_$pluginname");
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
     * @param stdClass $instance course prodinfo instance
     * All plugins allowing this must implement 'prodinfo/xxx:prodinfo' capability
     *
     * @return bool - true means user with 'prodinfo/xxx:prodinfo' may prodinfo others freely, false means nobody may add more paymentgateways manually
     */
    public function allow_prodinfo(stdClass $instance) {
        return false;
    }

    /**
     * Does this plugin allow manual unpaymentgateway of all users?
     * All plugins allowing this must implement 'prodinfo/xxx:unprodinfo' capability
     *
     * @param stdClass $instance course prodinfo instance
     * @return bool - true means user with 'prodinfo/xxx:unprodinfo' may unprodinfo others freely, false means nobody may touch user_paymentgateways
     */
    public function allow_unprodinfo(stdClass $instance) {
        return false;
    }

    /**
     * Does this plugin allow manual unpaymentgateway of a specific user?
     * All plugins allowing this must implement 'prodinfo/xxx:unprodinfo' capability
     *
     * This is useful especially for synchronisation plugins that
     * do suspend instead of full unpaymentgateway.
     *
     * @param stdClass $instance course prodinfo instance
     * @param stdClass $ue record from user_paymentgateways table, specifies user
     *
     * @return bool - true means user with 'prodinfo/xxx:unprodinfo' may unprodinfo this user, false means nobody may touch this user paymentgateway
     */
    public function allow_unprodinfo_user(stdClass $instance, stdClass $ue) {
        return $this->allow_unprodinfo($instance);
    }

    /**
     * Does this plugin allow manual changes in user_paymentgateways table?
     *
     * All plugins allowing this must implement 'prodinfo/xxx:manage' capability
     *
     * @param stdClass $instance course prodinfo instance
     * @return bool - true means it is possible to change prodinfo period and status in user_paymentgateways table
     */
    public function allow_manage(stdClass $instance) {
        return false;
    }

    /**
     * Does this plugin support some way to user to self prodinfo?
     *
     * @param stdClass $instance course prodinfo instance
     *
     * @return bool - true means show "prodinfo me in this course" link in course UI
     */
    public function show_prodinfome_link(stdClass $instance) {
        return false;
    }

    /**
     * Attempt to automatically prodinfo current user in course without any interaction,
     * calling code has to make sure the plugin and instance are active.
     *
     * This should return either a timestamp in the future or false.
     *
     * @param stdClass $instance course prodinfo instance
     * @return bool|int false means not prodinfoled, integer means timeend
     */
    public function try_autoprodinfo(stdClass $instance) {
        global $USER;

        return false;
    }

    /**
     * Attempt to automatically gain temporary guest access to course,
     * calling code has to make sure the plugin and instance are active.
     *
     * This should return either a timestamp in the future or false.
     *
     * @param stdClass $instance course prodinfo instance
     * @return bool|int false means no guest access, integer means timeend
     */
    public function try_guestaccess(stdClass $instance) {
        global $USER;

        return false;
    }

    /**
     * prodinfo user into course via prodinfo instance.
     *
     * @param stdClass $instance
     * @param int $userid
     * @param int $roleid optional role id
     * @param int $timestart 0 means unknown
     * @param int $timeend 0 means forever
     * @param int $status default to prodinfo_USER_ACTIVE for new paymentgateways, no change by default in updates
     * @param bool $recovergrades restore grade history
     * @return void
     */
    public function prodinfo_user(stdClass $instance, $userid, $roleid = null, $timestart = 0, $timeend = 0, $status = null, $recovergrades = null) {
        global $DB, $USER, $CFG; // CFG necessary!!!

        if ($instance->courseid == SITEID) {
            throw new coding_exception('invalid attempt to prodinfo into frontpage course!');
        }

        $name = $this->get_name();
        $courseid = $instance->courseid;

        if ($instance->prodinfo !== $name) {
            throw new coding_exception('invalid prodinfo instance!');
        }
        $context = context_course::instance($instance->courseid, MUST_EXIST);
        if (!isset($recovergrades)) {
            $recovergrades = $CFG->recovergradesdefault;
        }

        $inserted = false;
        $updated  = false;
        if ($ue = $DB->get_record('user_paymentgateways', array('prodinfoid'=>$instance->id, 'userid'=>$userid))) {
            //only update if timestart or timeend or status are different.
            if ($ue->timestart != $timestart or $ue->timeend != $timeend or (!is_null($status) and $ue->status != $status)) {
                $this->update_user_prodinfo($instance, $userid, $status, $timestart, $timeend);
            }
        } else {
            $ue = new stdClass();
            $ue->prodinfoid      = $instance->id;
            $ue->status       = is_null($status) ? prodinfo_USER_ACTIVE : $status;
            $ue->userid       = $userid;
            $ue->timestart    = $timestart;
            $ue->timeend      = $timeend;
            $ue->modifierid   = $USER->id;
            $ue->timecreated  = time();
            $ue->timemodified = $ue->timecreated;
            $ue->id = $DB->insert_record('user_paymentgateways', $ue);

            $inserted = true;
        }

        if ($inserted) {
            // Trigger event.
            $event = \core\event\user_paymentgateway_created::create(
                    array(
                        'objectid' => $ue->id,
                        'courseid' => $courseid,
                        'context' => $context,
                        'relateduserid' => $ue->userid,
                        'other' => array('prodinfo' => $name)
                        )
                    );
            $event->trigger();
        }

        if ($roleid) {
            // this must be done after the paymentgateway event so that the role_assigned event is triggered afterwards
            if ($this->roles_protected()) {
                role_assign($roleid, $userid, $context->id, 'prodinfo_'.$name, $instance->id);
            } else {
                role_assign($roleid, $userid, $context->id);
            }
        }

        // Recover old grades if present.
        if ($recovergrades) {
            require_once("$CFG->libdir/gradelib.php");
            grade_recover_history_grades($userid, $courseid);
        }

        // reset current user paymentgateway caching
        if ($userid == $USER->id) {
            if (isset($USER->prodinfo['prodinfoled'][$courseid])) {
                unset($USER->prodinfo['prodinfoled'][$courseid]);
            }
            if (isset($USER->prodinfo['tempguest'][$courseid])) {
                unset($USER->prodinfo['tempguest'][$courseid]);
                remove_temp_course_roles($context);
            }
        }
    }

    /**
     * Store user_paymentgateways changes and trigger event.
     *
     * @param stdClass $instance
     * @param int $userid
     * @param int $status
     * @param int $timestart
     * @param int $timeend
     * @return void
     */
    public function update_user_prodinfo(stdClass $instance, $userid, $status = NULL, $timestart = NULL, $timeend = NULL) {
        global $DB, $USER;

        $name = $this->get_name();

        if ($instance->prodinfo !== $name) {
            throw new coding_exception('invalid prodinfo instance!');
        }

        if (!$ue = $DB->get_record('user_paymentgateways', array('prodinfoid'=>$instance->id, 'userid'=>$userid))) {
            // weird, user not prodinfoled
            return;
        }

        $modified = false;
        if (isset($status) and $ue->status != $status) {
            $ue->status = $status;
            $modified = true;
        }
        if (isset($timestart) and $ue->timestart != $timestart) {
            $ue->timestart = $timestart;
            $modified = true;
        }
        if (isset($timeend) and $ue->timeend != $timeend) {
            $ue->timeend = $timeend;
            $modified = true;
        }

        if (!$modified) {
            // no change
            return;
        }

        $ue->modifierid = $USER->id;
        $DB->update_record('user_paymentgateways', $ue);
        context_course::instance($instance->courseid)->mark_dirty(); // reset prodinfo caches

        // Invalidate core_access cache for get_suspended_userids.
        cache_helper::invalidate_by_definition('core', 'suspended_userids', array(), array($instance->courseid));

        // Trigger event.
        $event = \core\event\user_paymentgateway_updated::create(
                array(
                    'objectid' => $ue->id,
                    'courseid' => $instance->courseid,
                    'context' => context_course::instance($instance->courseid),
                    'relateduserid' => $ue->userid,
                    'other' => array('prodinfo' => $name)
                    )
                );
        $event->trigger();
    }

    /**
     * Unprodinfo user from course,
     * the last unpaymentgateway removes all remaining roles.
     *
     * @param stdClass $instance
     * @param int $userid
     * @return void
     */
    public function unprodinfo_user(stdClass $instance, $userid) {
        global $CFG, $USER, $DB;
        require_once("$CFG->dirroot/group/lib.php");

        $name = $this->get_name();
        $courseid = $instance->courseid;

        if ($instance->prodinfo !== $name) {
            throw new coding_exception('invalid prodinfo instance!');
        }
        $context = context_course::instance($instance->courseid, MUST_EXIST);

        if (!$ue = $DB->get_record('user_paymentgateways', array('prodinfoid'=>$instance->id, 'userid'=>$userid))) {
            // weird, user not prodinfoled
            return;
        }

        // Remove all users groups linked to this paymentgateway instance.
        if ($gms = $DB->get_records('groups_members', array('userid'=>$userid, 'component'=>'prodinfo_'.$name, 'itemid'=>$instance->id))) {
            foreach ($gms as $gm) {
                groups_remove_member($gm->groupid, $gm->userid);
            }
        }

        role_unassign_all(array('userid'=>$userid, 'contextid'=>$context->id, 'component'=>'prodinfo_'.$name, 'itemid'=>$instance->id));
        $DB->delete_records('user_paymentgateways', array('id'=>$ue->id));

        // add extra info and trigger event
        $ue->courseid  = $courseid;
        $ue->prodinfo     = $name;

        $sql = "SELECT 'x'
                  FROM {user_paymentgateways} ue
                  JOIN {prodinfo} e ON (e.id = ue.prodinfoid)
                 WHERE ue.userid = :userid AND e.courseid = :courseid";
        if ($DB->record_exists_sql($sql, array('userid'=>$userid, 'courseid'=>$courseid))) {
            $ue->lastprodinfo = false;

        } else {
            // the big cleanup IS necessary!
            require_once("$CFG->libdir/gradelib.php");

            // remove all remaining roles
            role_unassign_all(array('userid'=>$userid, 'contextid'=>$context->id), true, false);

            //clean up ALL invisible user data from course if this is the last paymentgateway - groups, grades, etc.
            groups_delete_group_members($courseid, $userid);

            grade_user_unprodinfo($courseid, $userid);

            $DB->delete_records('user_lastaccess', array('userid'=>$userid, 'courseid'=>$courseid));

            $ue->lastprodinfo = true; // means user not prodinfoled any more
        }
        // Trigger event.
        $event = \core\event\user_paymentgateway_deleted::create(
                array(
                    'courseid' => $courseid,
                    'context' => $context,
                    'relateduserid' => $ue->userid,
                    'objectid' => $ue->id,
                    'other' => array(
                        'userpaymentgateway' => (array)$ue,
                        'prodinfo' => $name
                        )
                    )
                );
        $event->trigger();
        // reset all prodinfo caches
        $context->mark_dirty();

        // reset current user paymentgateway caching
        if ($userid == $USER->id) {
            if (isset($USER->prodinfo['prodinfoled'][$courseid])) {
                unset($USER->prodinfo['prodinfoled'][$courseid]);
            }
            if (isset($USER->prodinfo['tempguest'][$courseid])) {
                unset($USER->prodinfo['tempguest'][$courseid]);
                remove_temp_course_roles($context);
            }
        }
    }

    /**
     * Forces synchronisation of user paymentgateways.
     *
     * This is important especially for external prodinfo plugins,
     * this function is called for all enabled prodinfo plugins
     * right after every user login.
     *
     * @param object $user user record
     * @return void
     */
    public function sync_user_paymentgateways($user) {
        // override if necessary
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
     * Is it possible to delete prodinfo instance via standard UI?
     *
     * @deprecated since Moodle 2.8 MDL-35864 - please use can_delete_instance() instead.
     * @todo MDL-46479 This will be deleted in Moodle 3.0.
     * @see class_name::can_delete_instance()
     * @param object $instance
     * @return bool
     */
    public function instance_deleteable($instance) {
        debugging('Function prodinfo_plugin::instance_deleteable() is deprecated', DEBUG_DEVELOPER);
        return $this->can_delete_instance($instance);
    }

    /**
     * Is it possible to delete prodinfo instance via standard UI?
     *
     * @param stdClass  $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        return false;
    }

    /**
     * Is it possible to hide/show prodinfo instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        debugging("The paymentgateway plugin '".$this->get_name()."' should override the function can_hide_show_instance().", DEBUG_DEVELOPER);
        return true;
    }

    /**
     * Returns link to manual prodinfo UI if exists.
     * Does the access control tests automatically.
     *
     * @param object $instance
     * @return moodle_url
     */
    public function get_manual_prodinfo_link($instance) {
        return NULL;
    }

    /**
     * Returns list of unprodinfo links for all prodinfo instances in course.
     *
     * @param int $instance
     * @return moodle_url or NULL if self unpaymentgateway not supported
     */
    public function get_unprodinfoself_link($instance) {
        global $USER, $CFG, $DB;

        $name = $this->get_name();
        if ($instance->prodinfo !== $name) {
            throw new coding_exception('invalid prodinfo instance!');
        }

        if ($instance->courseid == SITEID) {
            return NULL;
        }

        if (!prodinfo_is_enabled($name)) {
            return NULL;
        }

        if ($instance->status != prodinfo_INSTANCE_ENABLED) {
            return NULL;
        }

        if (!file_exists("$CFG->dirroot/prodinfo/$name/unprodinfoself.php")) {
            return NULL;
        }

        $context = context_course::instance($instance->courseid, MUST_EXIST);

        if (!has_capability("prodinfo/$name:unprodinfoself", $context)) {
            return NULL;
        }

        if (!$DB->record_exists('user_paymentgateways', array('prodinfoid'=>$instance->id, 'userid'=>$USER->id, 'status'=>prodinfo_USER_ACTIVE))) {
            return NULL;
        }

        return new moodle_url("/prodinfo/$name/unprodinfoself.php", array('prodinfoid'=>$instance->id));
    }

    /**
     * Adds prodinfo instance UI to course edit form
     *
     * @param object $instance prodinfo instance or null if does not exist yet
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
     * @param object $instance prodinfo instance or null if does not exist yet
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
            if ($this->get_config('defaultprodinfo')) {
                $this->add_default_instance($course);
            }
        }
    }

    /**
     * Add new instance of prodinfo plugin.
     * @param object $course
     * @param array instance fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, array $fields = NULL) {
        global $DB;

        if ($course->id == SITEID) {
            throw new coding_exception('Invalid request to add prodinfo instance to frontpage.');
        }

        $instance = new stdClass();
        $instance->prodinfo          = $this->get_name();
        $instance->status         = prodinfo_INSTANCE_ENABLED;
        $instance->courseid       = $course->id;
        $instance->prodinfostartdate = 0;
        $instance->prodinfoenddate   = 0;
        $instance->timemodified   = time();
        $instance->timecreated    = $instance->timemodified;
        $instance->sortorder      = $DB->get_field('prodinfo', 'COALESCE(MAX(sortorder), -1) + 1', array('courseid'=>$course->id));

        $fields = (array)$fields;
        unset($fields['prodinfo']);
        unset($fields['courseid']);
        unset($fields['sortorder']);
        foreach($fields as $field=>$value) {
            $instance->$field = $value;
        }

        return $DB->insert_record('prodinfo', $instance);
    }

    /**
     * Add new instance of prodinfo plugin with default settings,
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
     * @param int $newstatus prodinfo_INSTANCE_ENABLED, prodinfo_INSTANCE_DISABLED
     * @return void
     */
    public function update_status($instance, $newstatus) {
        global $DB;

        $instance->status = $newstatus;
        $DB->update_record('prodinfo', $instance);

        // invalidate all prodinfo caches
        $context = context_course::instance($instance->courseid);
        $context->mark_dirty();
    }

    /**
     * Delete course prodinfo plugin instance, unprodinfo all users.
     * @param object $instance
     * @return void
     */
    public function delete_instance($instance) {
        global $DB;

        $name = $this->get_name();
        if ($instance->prodinfo !== $name) {
            throw new coding_exception('invalid prodinfo instance!');
        }

        //first unprodinfo all users
        $participants = $DB->get_recordset('user_paymentgateways', array('prodinfoid'=>$instance->id));
        foreach ($participants as $participant) {
            $this->unprodinfo_user($instance, $participant->userid);
        }
        $participants->close();

        // now clean up all remainders that were not removed correctly
        $DB->delete_records('groups_members', array('itemid'=>$instance->id, 'component'=>'prodinfo_'.$name));
        $DB->delete_records('role_assignments', array('itemid'=>$instance->id, 'component'=>'prodinfo_'.$name));
        $DB->delete_records('user_paymentgateways', array('prodinfoid'=>$instance->id));

        // finally drop the prodinfo row
        $DB->delete_records('prodinfo', array('id'=>$instance->id));

        // invalidate all prodinfo caches
        $context = context_course::instance($instance->courseid);
        $context->mark_dirty();
    }

    /**
     * Creates course prodinfo form, checks if form submitted
     * and prodinfos user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    public function prodinfo_page_hook(stdClass $instance) {
        return null;
    }

    /**
     * Checks if user can self prodinfo.
     *
     * @param stdClass $instance paymentgateway instance
     * @param bool $checkuserpaymentgateway if true will check if user paymentgateway is inactive.
     *             used by navigation to improve performance.
     * @return bool|string true if successful, else error message or false
     */
    public function can_self_prodinfo(stdClass $instance, $checkuserpaymentgateway = true) {
        return false;
    }

    /**
     * Return information for paymentgateway instance containing list of parameters required
     * for paymentgateway, name of paymentgateway plugin etc.
     *
     * @param stdClass $instance paymentgateway instance
     * @return array instance info.
     */
    public function get_prodinfo_info(stdClass $instance) {
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
        $versionfile = "$CFG->dirroot/prodinfo/$name/version.php";
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
     * Called for all enabled prodinfo plugins that returned true from is_cron_required().
     * @return void
     */
    public function cron() {
    }

    /**
     * Called when user is about to be deleted
     * @param object $user
     * @return void
     */
    public function user_delete($user) {
        global $DB;

        $sql = "SELECT e.*
                  FROM {prodinfo} e
                  JOIN {user_paymentgateways} ue ON (ue.prodinfoid = e.id)
                 WHERE e.prodinfo = :name AND ue.userid = :userid";
        $params = array('name'=>$this->get_name(), 'userid'=>$user->id);

        $rs = $DB->get_recordset_sql($sql, $params);
        foreach($rs as $instance) {
            $this->unprodinfo_user($instance, $user->id);
        }
        $rs->close();
    }

    /**
     * Returns an prodinfo_user_button that takes the user to a page where they are able to
     * prodinfo users into the managers course through this plugin.
     *
     * Optional: If the plugin supports manual paymentgateways it can choose to override this
     * otherwise it shouldn't
     *
     * @param course_paymentgateway_manager $manager
     * @return prodinfo_user_button|false
     */
    public function get_manual_prodinfo_button(course_paymentgateway_manager $manager) {
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
     * Return an array of prodinfo_bulk_paymentgateway_operation objects that define
     * the bulk actions that can be performed on user paymentgateways by the plugin.
     *
     * @param course_paymentgateway_manager $manager
     * @return array
     */
    public function get_bulk_operations(course_paymentgateway_manager $manager) {
        return array();
    }

    /**
     * Do any paymentgateways need expiration processing.
     *
     * Plugins that want to call this functionality must implement 'expiredaction' config setting.
     *
     * @param progress_trace $trace
     * @param int $courseid one course, empty mean all
     * @return bool true if any data processed, false if not
     */
    public function process_expirations(progress_trace $trace, $courseid = null) {
        global $DB;

        $name = $this->get_name();
        if (!prodinfo_is_enabled($name)) {
            $trace->finished();
            return false;
        }

        $processed = false;
        $params = array();
        $coursesql = "";
        if ($courseid) {
            $coursesql = "AND e.courseid = :courseid";
        }

        // Deal with expired accounts.
        $action = $this->get_config('expiredaction', prodinfo_EXT_REMOVED_KEEP);

        if ($action == prodinfo_EXT_REMOVED_UNprodinfo) {
            $instances = array();
            $sql = "SELECT ue.*, e.courseid, c.id AS contextid
                      FROM {user_paymentgateways} ue
                      JOIN {prodinfo} e ON (e.id = ue.prodinfoid AND e.prodinfo = :prodinfo)
                      JOIN {context} c ON (c.instanceid = e.courseid AND c.contextlevel = :courselevel)
                     WHERE ue.timeend > 0 AND ue.timeend < :now $coursesql";
            $params = array('now'=>time(), 'courselevel'=>CONTEXT_COURSE, 'prodinfo'=>$name, 'courseid'=>$courseid);

            $rs = $DB->get_recordset_sql($sql, $params);
            foreach ($rs as $ue) {
                if (!$processed) {
                    $trace->output("Starting processing of prodinfo_$name expirations...");
                    $processed = true;
                }
                if (empty($instances[$ue->prodinfoid])) {
                    $instances[$ue->prodinfoid] = $DB->get_record('prodinfo', array('id'=>$ue->prodinfoid));
                }
                $instance = $instances[$ue->prodinfoid];
                if (!$this->roles_protected()) {
                    // Let's just guess what extra roles are supposed to be removed.
                    if ($instance->roleid) {
                        role_unassign($instance->roleid, $ue->userid, $ue->contextid);
                    }
                }
                // The unprodinfo cleans up all subcontexts if this is the only course paymentgateway for this user.
                $this->unprodinfo_user($instance, $ue->userid);
                $trace->output("Unprodinfoling expired user $ue->userid from course $instance->courseid", 1);
            }
            $rs->close();
            unset($instances);

        } else if ($action == prodinfo_EXT_REMOVED_SUSPENDNOROLES or $action == prodinfo_EXT_REMOVED_SUSPEND) {
            $instances = array();
            $sql = "SELECT ue.*, e.courseid, c.id AS contextid
                      FROM {user_paymentgateways} ue
                      JOIN {prodinfo} e ON (e.id = ue.prodinfoid AND e.prodinfo = :prodinfo)
                      JOIN {context} c ON (c.instanceid = e.courseid AND c.contextlevel = :courselevel)
                     WHERE ue.timeend > 0 AND ue.timeend < :now
                           AND ue.status = :useractive $coursesql";
            $params = array('now'=>time(), 'courselevel'=>CONTEXT_COURSE, 'useractive'=>prodinfo_USER_ACTIVE, 'prodinfo'=>$name, 'courseid'=>$courseid);
            $rs = $DB->get_recordset_sql($sql, $params);
            foreach ($rs as $ue) {
                if (!$processed) {
                    $trace->output("Starting processing of prodinfo_$name expirations...");
                    $processed = true;
                }
                if (empty($instances[$ue->prodinfoid])) {
                    $instances[$ue->prodinfoid] = $DB->get_record('prodinfo', array('id'=>$ue->prodinfoid));
                }
                $instance = $instances[$ue->prodinfoid];

                if ($action == prodinfo_EXT_REMOVED_SUSPENDNOROLES) {
                    if (!$this->roles_protected()) {
                        // Let's just guess what roles should be removed.
                        $count = $DB->count_records('role_assignments', array('userid'=>$ue->userid, 'contextid'=>$ue->contextid));
                        if ($count == 1) {
                            role_unassign_all(array('userid'=>$ue->userid, 'contextid'=>$ue->contextid, 'component'=>'', 'itemid'=>0));

                        } else if ($count > 1 and $instance->roleid) {
                            role_unassign($instance->roleid, $ue->userid, $ue->contextid, '', 0);
                        }
                    }
                    // In any case remove all roles that belong to this instance and user.
                    role_unassign_all(array('userid'=>$ue->userid, 'contextid'=>$ue->contextid, 'component'=>'prodinfo_'.$name, 'itemid'=>$instance->id), true);
                    // Final cleanup of subcontexts if there are no more course roles.
                    if (0 == $DB->count_records('role_assignments', array('userid'=>$ue->userid, 'contextid'=>$ue->contextid))) {
                        role_unassign_all(array('userid'=>$ue->userid, 'contextid'=>$ue->contextid, 'component'=>'', 'itemid'=>0), true);
                    }
                }

                $this->update_user_prodinfo($instance, $ue->userid, prodinfo_USER_SUSPENDED);
                $trace->output("Suspending expired user $ue->userid in course $instance->courseid", 1);
            }
            $rs->close();
            unset($instances);

        } else {
            // prodinfo_EXT_REMOVED_KEEP means no changes.
        }

        if ($processed) {
            $trace->output("...finished processing of prodinfo_$name expirations");
        } else {
            $trace->output("No expired prodinfo_$name paymentgateways detected");
        }
        $trace->finished();

        return $processed;
    }

    /**
     * Send expiry notifications.
     *
     * Plugin that wants to have expiry notification MUST implement following:
     * - expirynotifyhour plugin setting,
     * - configuration options in instance edit form (expirynotify, notifyall and expirythreshold),
     * - notification strings (expirymessageprodinfolersubject, expirymessageprodinfolerbody,
     *   expirymessageprodinfoledsubject and expirymessageprodinfoledbody),
     * - expiry_notification provider in db/messages.php,
     * - upgrade code that sets default thresholds for existing courses (should be 1 day),
     * - something that calls this method, such as cron.
     *
     * @param progress_trace $trace (accepts bool for backwards compatibility only)
     */
    public function send_expiry_notifications($trace) {
        global $DB, $CFG;

        $name = $this->get_name();
        if (!prodinfo_is_enabled($name)) {
            $trace->finished();
            return;
        }

        // Unfortunately this may take a long time, it should not be interrupted,
        // otherwise users get duplicate notification.

        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_HUGE);


        $expirynotifylast = $this->get_config('expirynotifylast', 0);
        $expirynotifyhour = $this->get_config('expirynotifyhour');
        if (is_null($expirynotifyhour)) {
            debugging("send_expiry_notifications() in $name paymentgateway plugin needs expirynotifyhour setting");
            $trace->finished();
            return;
        }

        if (!($trace instanceof progress_trace)) {
            $trace = $trace ? new text_progress_trace() : new null_progress_trace();
            debugging('prodinfo_plugin::send_expiry_notifications() now expects progress_trace instance as parameter!', DEBUG_DEVELOPER);
        }

        $timenow = time();
        $notifytime = usergetmidnight($timenow, $CFG->timezone) + ($expirynotifyhour * 3600);

        if ($expirynotifylast > $notifytime) {
            $trace->output($name.' paymentgateway expiry notifications were already sent today at '.userdate($expirynotifylast, '', $CFG->timezone).'.');
            $trace->finished();
            return;

        } else if ($timenow < $notifytime) {
            $trace->output($name.' paymentgateway expiry notifications will be sent at '.userdate($notifytime, '', $CFG->timezone).'.');
            $trace->finished();
            return;
        }

        $trace->output('Processing '.$name.' paymentgateway expiration notifications...');

        // Notify users responsible for paymentgateway once every day.
        $sql = "SELECT ue.*, e.expirynotify, e.notifyall, e.expirythreshold, e.courseid, c.fullname
                  FROM {user_paymentgateways} ue
                  JOIN {prodinfo} e ON (e.id = ue.prodinfoid AND e.prodinfo = :name AND e.expirynotify > 0 AND e.status = :enabled)
                  JOIN {course} c ON (c.id = e.courseid)
                  JOIN {user} u ON (u.id = ue.userid AND u.deleted = 0 AND u.suspended = 0)
                 WHERE ue.status = :active AND ue.timeend > 0 AND ue.timeend > :now1 AND ue.timeend < (e.expirythreshold + :now2)
              ORDER BY ue.prodinfoid ASC, u.lastname ASC, u.firstname ASC, u.id ASC";
        $params = array('enabled'=>prodinfo_INSTANCE_ENABLED, 'active'=>prodinfo_USER_ACTIVE, 'now1'=>$timenow, 'now2'=>$timenow, 'name'=>$name);

        $rs = $DB->get_recordset_sql($sql, $params);

        $lastprodinfolid = 0;
        $users = array();

        foreach($rs as $ue) {
            if ($lastprodinfolid and $lastprodinfolid != $ue->prodinfoid) {
                $this->notify_expiry_prodinfoler($lastprodinfolid, $users, $trace);
                $users = array();
            }
            $lastprodinfolid = $ue->prodinfoid;

            $prodinfoler = $this->get_prodinfoler($ue->prodinfoid);
            $context = context_course::instance($ue->courseid);

            $user = $DB->get_record('user', array('id'=>$ue->userid));

            $users[] = array('fullname'=>fullname($user, has_capability('moodle/site:viewfullnames', $context, $prodinfoler)), 'timeend'=>$ue->timeend);

            if (!$ue->notifyall) {
                continue;
            }

            if ($ue->timeend - $ue->expirythreshold + 86400 < $timenow) {
                // Notify prodinfoled users only once at the start of the threshold.
                $trace->output("user $ue->userid was already notified that paymentgateway in course $ue->courseid expires on ".userdate($ue->timeend, '', $CFG->timezone), 1);
                continue;
            }

            $this->notify_expiry_prodinfoled($user, $ue, $trace);
        }
        $rs->close();

        if ($lastprodinfolid and $users) {
            $this->notify_expiry_prodinfoler($lastprodinfolid, $users, $trace);
        }

        $trace->output('...notification processing finished.');
        $trace->finished();

        $this->set_config('expirynotifylast', $timenow);
    }

    /**
     * Returns the user who is responsible for paymentgateways for given instance.
     *
     * Override if plugin knows anybody better than admin.
     *
     * @param int $instanceid paymentgateway instance id
     * @return stdClass user record
     */
    protected function get_prodinfoler($instanceid) {
        return get_admin();
    }

    /**
     * Notify user about incoming expiration of their paymentgateway,
     * it is called only if notification of prodinfoled users (aka students) is enabled in course.
     *
     * This is executed only once for each expiring paymentgateway right
     * at the start of the expiration threshold.
     *
     * @param stdClass $user
     * @param stdClass $ue
     * @param progress_trace $trace
     */
    protected function notify_expiry_prodinfoled($user, $ue, progress_trace $trace) {
        global $CFG;

        $name = $this->get_name();

        $oldforcelang = force_current_language($user->lang);

        $prodinfoler = $this->get_prodinfoler($ue->prodinfoid);
        $context = context_course::instance($ue->courseid);

        $a = new stdClass();
        $a->course   = format_string($ue->fullname, true, array('context'=>$context));
        $a->user     = fullname($user, true);
        $a->timeend  = userdate($ue->timeend, '', $user->timezone);
        $a->prodinfoler = fullname($prodinfoler, has_capability('moodle/site:viewfullnames', $context, $user));

        $subject = get_string('expirymessageprodinfoledsubject', 'prodinfo_'.$name, $a);
        $body = get_string('expirymessageprodinfoledbody', 'prodinfo_'.$name, $a);

        $message = new stdClass();
        $message->notification      = 1;
        $message->component         = 'prodinfo_'.$name;
        $message->name              = 'expiry_notification';
        $message->userfrom          = $prodinfoler;
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
     * it is called only if notification of prodinfolers (aka teachers) is enabled in course.
     *
     * This is called repeatedly every day for each course if there are any pending expiration
     * in the expiration threshold.
     *
     * @param int $eid
     * @param array $users
     * @param progress_trace $trace
     */
    protected function notify_expiry_prodinfoler($eid, $users, progress_trace $trace) {
        global $DB;

        $name = $this->get_name();

        $instance = $DB->get_record('prodinfo', array('id'=>$eid, 'prodinfo'=>$name));
        $context = context_course::instance($instance->courseid);
        $course = $DB->get_record('course', array('id'=>$instance->courseid));

        $prodinfoler = $this->get_prodinfoler($instance->id);
        $admin = get_admin();

        $oldforcelang = force_current_language($prodinfoler->lang);

        foreach($users as $key=>$info) {
            $users[$key] = '* '.$info['fullname'].' - '.userdate($info['timeend'], '', $prodinfoler->timezone);
        }

        $a = new stdClass();
        $a->course    = format_string($course->fullname, true, array('context'=>$context));
        $a->threshold = get_string('numdays', '', $instance->expirythreshold / (60*60*24));
        $a->users     = implode("\n", $users);
        $a->extendurl = (string)new moodle_url('/prodinfo/users.php', array('id'=>$instance->courseid));

        $subject = get_string('expirymessageprodinfolersubject', 'prodinfo_'.$name, $a);
        $body = get_string('expirymessageprodinfolerbody', 'prodinfo_'.$name, $a);

        $message = new stdClass();
        $message->notification      = 1;
        $message->component         = 'prodinfo_'.$name;
        $message->name              = 'expiry_notification';
        $message->userfrom          = $admin;
        $message->userto            = $prodinfoler;
        $message->subject           = $subject;
        $message->fullmessage       = $body;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml   = markdown_to_html($body);
        $message->smallmessage      = $subject;
        $message->contexturlname    = $a->course;
        $message->contexturl        = $a->extendurl;

        if (message_send($message)) {
            $trace->output("notifying user $prodinfoler->id about all expiring $name paymentgateways in course $instance->courseid", 1);
        } else {
            $trace->output("error notifying user $prodinfoler->id about all expiring $name paymentgateways in course $instance->courseid", 1);
        }

        force_current_language($oldforcelang);
    }

    /**
     * Backup execution step hook to annotate custom fields.
     *
     * @param backup_paymentgateways_execution_step $step
     * @param stdClass $prodinfo
     */
    public function backup_annotate_custom_fields(backup_paymentgateways_execution_step $step, stdClass $prodinfo) {
        // Override as necessary to annotate custom fields in the prodinfo table.
    }

    /**
     * Automatic prodinfo sync executed during restore.
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
    public function restore_instance(restore_paymentgateways_structure_step $step, stdClass $data, $course, $oldid) {
        // Do not call this from overridden methods, restore and set new id there.
        $step->set_mapping('prodinfo', $oldid, 0);
    }

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
