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
 * needs major re work of all code (copied from moodle/lib/storinfolib.php)
 */ 
/**
 * This library includes the basic parts of storinfo api.
 * It is available on each page.
 *
 * @package    core
 * @subpackage storinfo
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** Course storinfo instance enabled. (used in storinfo->status) */
define('storinfo_INSTANCE_ENABLED', 0);

/** Course storinfo instance disabled, user may enter course if other storinfo instance enabled. (used in storinfo->status)*/
define('storinfo_INSTANCE_DISABLED', 1);

/** User is active participant (used in user_storemultiinfos->status)*/
define('storinfo_USER_ACTIVE', 0);

/** User participation in course is suspended (used in user_storemultiinfos->status) */
define('storinfo_USER_SUSPENDED', 1);

/** @deprecated - storinfo caching was reworked, use storinfo_MAX_TIMESTAMP instead */
define('storinfo_REQUIRE_LOGIN_CACHE_PERIOD', 1800);

/** The timestamp indicating forever */
define('storinfo_MAX_TIMESTAMP', 2147483647);

/** When user disappears from external source, the storemultiinfo is completely removed */
define('storinfo_EXT_REMOVED_UNstorinfo', 0);

/** When user disappears from external source, the storemultiinfo is kept as is - one way sync */
define('storinfo_EXT_REMOVED_KEEP', 1);

/** @deprecated since 2.4 not used any more, migrate plugin to new restore methods */
define('storinfo_RESTORE_TYPE', 'storinforestore');

/**
 * When user disappears from external source, user storemultiinfo is suspended, roles are kept as is.
 * In some cases user needs a role with some capability to be visible in UI - suc has in gradebook,
 * assignments, etc.
 */
define('storinfo_EXT_REMOVED_SUSPEND', 2);

/**
 * When user disappears from external source, the storemultiinfo is suspended and roles assigned
 * by storinfo instance are removed. Please note that user may "disappear" from gradebook and other areas.
 * */
define('storinfo_EXT_REMOVED_SUSPENDNOROLES', 3);

/**
 * Returns instances of storinfo plugins
 * @param bool $enabled return enabled only
 * @return array of storinfo plugins name=>instance
 */
function storinfo_get_plugins($enabled) {
    global $CFG;

    $result = array();

    if ($enabled) {
        // sorted by enabled plugin order
        $enabled = explode(',', $CFG->storinfo_plugins_enabled);
        $plugins = array();
        foreach ($enabled as $plugin) {
            $plugins[$plugin] = "$CFG->dirroot/storinfo/$plugin";
        }
    } else {
        // sorted alphabetically
        $plugins = core_component::get_plugin_list('storinfo');
        ksort($plugins);
    }

    foreach ($plugins as $plugin=>$location) {
        $class = "storinfo_{$plugin}_plugin";
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
 * Returns instance of storinfo plugin
 * @param  string $name name of storinfo plugin ('manual', 'guest', ...)
 * @return storinfo_plugin
 */
function storinfo_get_plugin($name) {
    global $CFG;

    $name = clean_param($name, PARAM_PLUGIN);

    if (empty($name)) {
        // ignore malformed or missing plugin names completely
        return null;
    }

    $location = "$CFG->dirroot/storinfo/$name";

    if (!file_exists("$location/lib.php")) {
        return null;
    }
    include_once("$location/lib.php");
    $class = "storinfo_{$name}_plugin";
    if (!class_exists($class)) {
        return null;
    }

    return new $class();
}

/**
 * Returns storemultiinfo instances in given course.
 * @param int $courseid
 * @param bool $enabled
 * @return array of storinfo instances
 */

function storinfo_get_instances($courseid, $enabled) {
    global $DB, $CFG;

    if (!$enabled) {
        return $DB->get_records('storinfo', array('courseid'=>$courseid), 'sortorder,id');
    }

    $result = $DB->get_records('storinfo', array('courseid'=>$courseid, 'status'=>storinfo_INSTANCE_ENABLED), 'sortorder,id');

    $enabled = explode(',', $CFG->storinfo_plugins_enabled);
    foreach ($result as $key=>$instance) {
        if (!in_array($instance->storinfo, $enabled)) {
            unset($result[$key]);
            continue;
        }
        if (!file_exists("$CFG->dirroot/storinfo/$instance->storinfo/lib.php")) {
            // broken plugin
            unset($result[$key]);
            continue;
        }
    }

    return $result;
}


/**
 * Checks if a given plugin is in the list of enabled storemultiinfo plugins.
 *
 * @param string $storinfo storemultiinfo plugin name
 * @return boolean Whether the plugin is enabled
 */
function storinfo_is_enabled($storinfo) {
    global $CFG;

    if (empty($CFG->storinfo_plugins_enabled)) {
        return false;
    }
    return in_array($storinfo, explode(',', $CFG->storinfo_plugins_enabled));
}

/**
 * Check all the login storemultiinfo information for the given user object
 * by querying the storemultiinfo plugins
 *
 * This function may be very slow, use only once after log-in or login-as.
 *
 * @param stdClass $user
 * @return void
 */
function storinfo_check_plugins($user) {
    global $CFG;

    if (empty($user->id) or isguestuser($user)) {
        // shortcut - there is no storemultiinfo work for guests and not-logged-in users
        return;
    }

    // originally there was a broken admin test, but accidentally it was non-functional in 2.2,
    // which proved it was actually not necessary.

    static $inprogress = array();  // To prevent this function being called more than once in an invocation

    if (!empty($inprogress[$user->id])) {
        return;
    }

    $inprogress[$user->id] = true;  // Set the flag

    $enabled = storinfo_get_plugins(true);

    foreach($enabled as $storinfo) {
        $storinfo->sync_user_storemultiinfos($user);
    }

    unset($inprogress[$user->id]);  // Unset the flag
}

/**
 * Do these two students share any course?
 *
 * The courses has to be visible and storemultiinfos has to be active,
 * timestart and timeend restrictions are ignored.
 *
 * This function calls {@see storinfo_get_shared_courses()} setting checkexistsonly
 * to true.
 *
 * @param stdClass|int $user1
 * @param stdClass|int $user2
 * @return bool
 */

function storinfo_sharing_course($user1, $user2) {
    return storinfo_get_shared_courses($user1, $user2, false, true);
}

/**
 * Returns any courses shared by the two users
 *
 * The courses has to be visible and storemultiinfos has to be active,
 * timestart and timeend restrictions are ignored.
 *
 * @global moodle_database $DB
 * @param stdClass|int $user1
 * @param stdClass|int $user2
 * @param bool $preloadcontexts If set to true contexts for the returned courses
 *              will be preloaded.
 * @param bool $checkexistsonly If set to true then this function will return true
 *              if the users share any courses and false if not.
 * @return array|bool An array of courses that both users are storinfoled in OR if
 *              $checkexistsonly set returns true if the users share any courses
 *              and false if not.
 */

function storinfo_get_shared_courses($user1, $user2, $preloadcontexts = false, $checkexistsonly = false) {
    global $DB, $CFG;

    $user1 = isset($user1->id) ? $user1->id : $user1;
    $user2 = isset($user2->id) ? $user2->id : $user2;

    if (empty($user1) or empty($user2)) {
        return false;
    }

    if (!$plugins = explode(',', $CFG->storinfo_plugins_enabled)) {
        return false;
    }

    list($plugins, $params) = $DB->get_in_or_equal($plugins, SQL_PARAMS_NAMED, 'ee');
    $params['enabled'] = storinfo_INSTANCE_ENABLED;
    $params['active1'] = storinfo_USER_ACTIVE;
    $params['active2'] = storinfo_USER_ACTIVE;
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
                  FROM {storinfo} e
                  JOIN {user_storemultiinfos} ue1 ON (ue1.storinfoid = e.id AND ue1.status = :active1 AND ue1.userid = :user1)
                  JOIN {user_storemultiinfos} ue2 ON (ue2.storinfoid = e.id AND ue2.status = :active2 AND ue2.userid = :user2)
                  JOIN {course} c ON (c.id = e.courseid AND c.visible = 1)
                 WHERE e.status = :enabled AND e.storinfo $plugins
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
 * This function adds necessary storinfo plugins UI into the course edit form.
 *
 * @param MoodleQuickForm $mform
 * @param object $data course edit form data
 * @param object $context context of existing course or parent category if course does not exist
 * @return void
 */
function storinfo_course_edit_form(MoodleQuickForm $mform, $data, $context) {
    $plugins = storinfo_get_plugins(true);
    if (!empty($data->id)) {
        $instances = storinfo_get_instances($data->id, false);
        foreach ($instances as $instance) {
            if (!isset($plugins[$instance->storinfo])) {
                continue;
            }
            $plugin = $plugins[$instance->storinfo];
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
function storinfo_course_edit_validation(array $data, $context) {
    $errors = array();
    $plugins = storinfo_get_plugins(true);

    if (!empty($data['id'])) {
        $instances = storinfo_get_instances($data['id'], false);
        foreach ($instances as $instance) {
            if (!isset($plugins[$instance->storinfo])) {
                continue;
            }
            $plugin = $plugins[$instance->storinfo];
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
 * Update storinfo instances after course edit form submission
 * @param bool $inserted true means new course added, false course already existed
 * @param object $course
 * @param object $data form data
 * @return void
 */
function storinfo_course_updated($inserted, $course, $data) {
    global $DB, $CFG;

    $plugins = storinfo_get_plugins(true);

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
function storinfo_add_course_navigation(navigation_node $coursenode, $course) {
    global $CFG;

    $coursecontext = context_course::instance($course->id);

    $instances = storinfo_get_instances($course->id, true);
    $plugins   = storinfo_get_plugins(true);

    // we do not want to break all course pages if there is some borked storinfo plugin, right?
    foreach ($instances as $k=>$instance) {
        if (!isset($plugins[$instance->storinfo])) {
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
        if (has_capability('moodle/course:storinforeview', $coursecontext)) {
            $url = new moodle_url('/storinfo/users.php', array('id'=>$course->id));
            $usersnode->add(get_string('storinfoledusers', 'storinfo'), $url, navigation_node::TYPE_SETTING, null, 'review', new pix_icon('i/storinfousers', ''));
        }

        // manage storinfo plugin instances
        if (has_capability('moodle/course:storinfoconfig', $coursecontext) or has_capability('moodle/course:storinforeview', $coursecontext)) {
            $url = new moodle_url('/storinfo/instances.php', array('id'=>$course->id));
        } else {
            $url = NULL;
        }
        $instancesnode = $usersnode->add(get_string('storemultiinfoinstances', 'storinfo'), $url, navigation_node::TYPE_SETTING, null, 'manageinstances');

        // each instance decides how to configure itself or how many other nav items are exposed
        foreach ($instances as $instance) {
            if (!isset($plugins[$instance->storinfo])) {
                continue;
            }
            $plugins[$instance->storinfo]->add_course_navigation($instancesnode, $instance);
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

     // Deal somehow with users that are not storinfoled but still got a role somehow
    if ($course->id != SITEID) {
        //TODO, create some new UI for role assignments at course level
        if (has_capability('moodle/course:reviewotherusers', $coursecontext)) {
            $url = new moodle_url('/storinfo/otherusers.php', array('id'=>$course->id));
            $usersnode->add(get_string('notstorinfoledusers', 'storinfo'), $url, navigation_node::TYPE_SETTING, null, 'otherusers', new pix_icon('i/assignroles', ''));
        }
    }

    // just in case nothing was actually added
    $usersnode->trim_if_empty();

    if ($course->id != SITEID) {
        if (isguestuser() or !isloggedin()) {
            // guest account can not be storemultiinfo - no links for them
        } else if (is_storinfoled($coursecontext)) {
            // unstorinfo link if possible
            foreach ($instances as $instance) {
                if (!isset($plugins[$instance->storinfo])) {
                    continue;
                }
                $plugin = $plugins[$instance->storinfo];
                if ($unstorinfolink = $plugin->get_unstorinfoself_link($instance)) {
                    $shortname = format_string($course->shortname, true, array('context' => $coursecontext));
                    $coursenode->add(get_string('unstorinfome', 'core_storinfo', $shortname), $unstorinfolink, navigation_node::TYPE_SETTING, null, 'unstorinfoself', new pix_icon('i/user', ''));
                    break;
                    //TODO. deal with multiple unstorinfo links - not likely case, but still...
                }
            }
        } else {
            // storinfo link if possible
            if (is_viewing($coursecontext)) {
                // better not show any storinfo link, this is intended for managers and inspectors
            } else {
                foreach ($instances as $instance) {
                    if (!isset($plugins[$instance->storinfo])) {
                        continue;
                    }
                    $plugin = $plugins[$instance->storinfo];
                    if ($plugin->show_storinfome_link($instance)) {
                        $url = new moodle_url('/storinfo/index.php', array('id'=>$course->id));
                        $shortname = format_string($course->shortname, true, array('context' => $coursecontext));
                        $coursenode->add(get_string('storinfome', 'core_storinfo', $shortname), $url, navigation_node::TYPE_SETTING, null, 'storinfoself', new pix_icon('i/user', ''));
                        break;
                    }
                }
            }
        }
    }
}

/**
 * Returns list of courses current $USER is storemultiinfo in and can access
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
function storinfo_get_my_courses($fields = NULL, $sort = 'visible DESC,sortorder ASC', $limit = 0) {
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
        throw new coding_exception('Invalid $fileds parameter in storinfo_get_my_courses()');
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
                      FROM {storinfo} e
                      JOIN {user_storemultiinfos} ue ON (ue.storinfoid = e.id AND ue.userid = :userid)
                     WHERE ue.status = :active AND e.status = :enabled AND ue.timestart < :now1 AND (ue.timeend = 0 OR ue.timeend > :now2)
                   ) en ON (en.courseid = c.id)
           $ccjoin
             WHERE $wheres
          $orderby";
    $params['userid']  = $USER->id;
    $params['active']  = storinfo_USER_ACTIVE;
    $params['enabled'] = storinfo_INSTANCE_ENABLED;
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
 * Returns course storemultiinfo information icons.
 *
 * @param object $course
 * @param array $instances storinfo instances of this course, improves performance
 * @return array of pix_icon
 */
function storinfo_get_course_info_icons($course, array $instances = NULL) {
    $icons = array();
    if (is_null($instances)) {
        $instances = storinfo_get_instances($course->id, true);
    }
    $plugins = storinfo_get_plugins(true);
    foreach ($plugins as $name => $plugin) {
        $pis = array();
        foreach ($instances as $instance) {
            if ($instance->status != storinfo_INSTANCE_ENABLED or $instance->courseid != $course->id) {
                debugging('Invalid instances parameter submitted in storinfo_get_info_icons()');
                continue;
            }
            if ($instance->storinfo == $name) {
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
 * Returns course storemultiinfo detailed information.
 *
 * @param object $course
 * @return array of html fragments - can be used to construct lists
 */
function storinfo_get_course_description_texts($course) {
    $lines = array();
    $instances = storinfo_get_instances($course->id, true);
    $plugins = storinfo_get_plugins(true);
    foreach ($instances as $instance) {
        if (!isset($plugins[$instance->storinfo])) {
            //weird
            continue;
        }
        $plugin = $plugins[$instance->storinfo];
        $text = $plugin->get_description_text($instance);
        if ($text !== NULL) {
            $lines[] = $text;
        }
    }
    return $lines;
}

/**
 * Returns list of courses user is storinfoled into.
 * (Note: use storinfo_get_all_users_courses if you want to use the list wihtout any cap checks )
 *
 * - $fields is an array of fieldnames to ADD
 *   so name the fields you really need, which will
 *   be added and uniq'd
 *
 * @param int $userid
 * @param bool $onlyactive return only active storemultiinfos in courses user may see
 * @param string|array $fields
 * @param string $sort
 * @return array
 */
function storinfo_get_users_courses($userid, $onlyactive = false, $fields = NULL, $sort = 'visible DESC,sortorder ASC') {
    global $DB;

    $courses = storinfo_get_all_users_courses($userid, $onlyactive, $fields, $sort);

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
 * Can user access at least one storinfoled course?
 *
 * Cheat if necessary, but find out as fast as possible!
 *
 * @param int|stdClass $user null means use current user
 * @return bool
 */
function storinfo_user_sees_own_courses($user = null) {
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
        if (!empty($USER->storinfo['storinfoled'])) {
            foreach ($USER->storinfo['storinfoled'] as $until) {
                if ($until > time()) {
                    return true;
                }
            }
        }
    }

    // Now the slow way.
    $courses = storinfo_get_all_users_courses($userid, true);
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
 * Returns list of courses user is storinfoled into without any capability checks
 * - $fields is an array of fieldnames to ADD
 *   so name the fields you really need, which will
 *   be added and uniq'd
 *
 * @param int $userid
 * @param bool $onlyactive return only active storemultiinfos in courses user may see
 * @param string|array $fields
 * @param string $sort
 * @return array
 */
function storinfo_get_all_users_courses($userid, $onlyactive = false, $fields = NULL, $sort = 'visible DESC,sortorder ASC') {
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
        throw new coding_exception('Invalid $fileds parameter in storinfo_get_my_courses()');
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
        $params['active']  = storinfo_USER_ACTIVE;
        $params['enabled'] = storinfo_INSTANCE_ENABLED;
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
                      FROM {storinfo} e
                      JOIN {user_storemultiinfos} ue ON (ue.storinfoid = e.id AND ue.userid = :userid)
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
function storinfo_user_delete($user) {
    global $DB;

    $plugins = storinfo_get_plugins(true);
    foreach ($plugins as $plugin) {
        $plugin->user_delete($user);
    }

    // force cleanup of all broken storemultiinfos
    $DB->delete_records('user_storemultiinfos', array('userid'=>$user->id));
}

/**
 * Called when course is about to be deleted.
 * @param stdClass $course
 * @return void
 */
function storinfo_course_delete($course) {
    global $DB;

    $instances = storinfo_get_instances($course->id, false);
    $plugins = storinfo_get_plugins(true);
    foreach ($instances as $instance) {
        if (isset($plugins[$instance->storinfo])) {
            $plugins[$instance->storinfo]->delete_instance($instance);
        }
        // low level delete in case plugin did not do it
        $DB->delete_records('user_storemultiinfos', array('storinfoid'=>$instance->id));
        $DB->delete_records('role_assignments', array('itemid'=>$instance->id, 'component'=>'storinfo_'.$instance->storinfo));
        $DB->delete_records('user_storemultiinfos', array('storinfoid'=>$instance->id));
        $DB->delete_records('storinfo', array('id'=>$instance->id));
    }
}

/**
 * Try to storinfo user via default internal auth plugin.
 *
 * For now this is always using the manual storinfo plugin...
 *
 * @param $courseid
 * @param $userid
 * @param $roleid
 * @param $timestart
 * @param $timeend
 * @return bool success
 */
function storinfo_try_internal_storinfo($courseid, $userid, $roleid = null, $timestart = 0, $timeend = 0) {
    global $DB;

    //note: this is hardcoded to manual plugin for now

    if (!storinfo_is_enabled('manual')) {
        return false;
    }

    if (!$storinfo = storinfo_get_plugin('manual')) {
        return false;
    }
    if (!$instances = $DB->get_records('storinfo', array('storinfo'=>'manual', 'courseid'=>$courseid, 'status'=>storinfo_INSTANCE_ENABLED), 'sortorder,id ASC')) {
        return false;
    }
    $instance = reset($instances);

    $storinfo->storinfo_user($instance, $userid, $roleid, $timestart, $timeend);

    return true;
}

/**
 * Is there a chance users might self storinfo
 * @param int $courseid
 * @return bool
 */
function storinfo_selfstorinfo_available($courseid) {
    $result = false;

    $plugins = storinfo_get_plugins(true);
    $storinfoinstances = storinfo_get_instances($courseid, true);
    foreach($storinfoinstances as $instance) {
        if (!isset($plugins[$instance->storinfo])) {
            continue;
        }
        if ($instance->storinfo === 'guest') {
            // blacklist known temporary guest plugins
            continue;
        }
        if ($plugins[$instance->storinfo]->show_storinfome_link($instance)) {
            $result = true;
            break;
        }
    }

    return $result;
}

/**
 * This function returns the end of current active user storemultiinfo.
 *
 * It deals correctly with multiple overlapping user storemultiinfos.
 *
 * @param int $courseid
 * @param int $userid
 * @return int|bool timestamp when active storemultiinfo ends, false means no active storemultiinfo now, 0 means never
 */
function storinfo_get_storemultiinfo_end($courseid, $userid) {
    global $DB;

    $sql = "SELECT ue.*
              FROM {user_storemultiinfos} ue
              JOIN {storinfo} e ON (e.id = ue.storinfoid AND e.courseid = :courseid)
              JOIN {user} u ON u.id = ue.userid
             WHERE ue.userid = :userid AND ue.status = :active AND e.status = :enabled AND u.deleted = 0";
    $params = array('enabled'=>storinfo_INSTANCE_ENABLED, 'active'=>storinfo_USER_ACTIVE, 'userid'=>$userid, 'courseid'=>$courseid);

    if (!$storemultiinfos = $DB->get_records_sql($sql, $params)) {
        return false;
    }

    $changes = array();

    foreach ($storemultiinfos as $ue) {
        $start = (int)$ue->timestart;
        $end = (int)$ue->timeend;
        if ($end != 0 and $end < $start) {
            debugging('Invalid storemultiinfo start or end in user_storemultiinfo id:'.$ue->id);
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

    // let's sort then storemultiinfo starts&ends and go through them chronologically,
    // looking for current status and the next future end of storemultiinfo
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
                    // no storemultiinfo active
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
 * Is current user accessing course via this storemultiinfo method?
 *
 * This is intended for operations that are going to affect storinfo instances.
 *
 * @param stdClass $instance storinfo instance
 * @return bool
 */
function storinfo_accessing_via_instance(stdClass $instance) {
    global $DB, $USER;

    if (empty($instance->id)) {
        return false;
    }

    if (is_siteadmin()) {
        // Admins may go anywhere.
        return false;
    }

    return $DB->record_exists('user_storemultiinfos', array('userid'=>$USER->id, 'storinfoid'=>$instance->id));
}


/**
 * All storinfo plugins should be based on this class,
 * this is also the main source of documentation.
 */
abstract class storinfo_plugin {
    protected $config = null;

    /**
     * Returns name of this storinfo plugin
     * @return string
     */
    public function get_name() {
        // second word in class is always storinfo name, sorry, no fancy plugin names with _
        $words = explode('_', get_class($this));
        return $words[1];
    }

    /**
     * Returns localised name of storinfo instance
     *
     * @param object $instance (null is accepted too)
     * @return string
     */
    public function get_instance_name($instance) {
        if (empty($instance->name)) {
            $storinfo = $this->get_name();
            return get_string('pluginname', 'storinfo_'.$storinfo);
        } else {
            $context = context_course::instance($instance->courseid);
            return format_string($instance->name, true, array('context'=>$context));
        }
    }

    /**
     * Returns optional storemultiinfo information icons.
     *
     * This is used in course list for quick overview of storemultiinfo options.
     *
     * We are not using single instance parameter because sometimes
     * we might want to prevent icon repetition when multiple instances
     * of one type exist. One instance may also produce several icons.
     *
     * @param array $instances all storinfo instances of this type in one course
     * @return array of pix_icon
     */
    public function get_info_icons(array $instances) {
        return array();
    }

    /**
     * Returns optional storemultiinfo instance description text.
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
            $this->config = get_config("storinfo_$name");
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
        set_config($name, $value, "storinfo_$pluginname");
    }

    /**
     * Does this plugin assign protected roles are can they be manually removed?
     * @return bool - false means anybody may tweak roles, it does not use itemid and component when assigning roles
     */
    public function roles_protected() {
        return true;
    }

    /**
     * Does this plugin allow manual storemultiinfos?
     *
     * @param stdClass $instance course storinfo instance
     * All plugins allowing this must implement 'storinfo/xxx:storinfo' capability
     *
     * @return bool - true means user with 'storinfo/xxx:storinfo' may storinfo others freely, false means nobody may add more storemultiinfos manually
     */
    public function allow_storinfo(stdClass $instance) {
        return false;
    }

    /**
     * Does this plugin allow manual unstoremultiinfo of all users?
     * All plugins allowing this must implement 'storinfo/xxx:unstorinfo' capability
     *
     * @param stdClass $instance course storinfo instance
     * @return bool - true means user with 'storinfo/xxx:unstorinfo' may unstorinfo others freely, false means nobody may touch user_storemultiinfos
     */
    public function allow_unstorinfo(stdClass $instance) {
        return false;
    }

    /**
     * Does this plugin allow manual unstoremultiinfo of a specific user?
     * All plugins allowing this must implement 'storinfo/xxx:unstorinfo' capability
     *
     * This is useful especially for synchronisation plugins that
     * do suspend instead of full unstoremultiinfo.
     *
     * @param stdClass $instance course storinfo instance
     * @param stdClass $ue record from user_storemultiinfos table, specifies user
     *
     * @return bool - true means user with 'storinfo/xxx:unstorinfo' may unstorinfo this user, false means nobody may touch this user storemultiinfo
     */
    public function allow_unstorinfo_user(stdClass $instance, stdClass $ue) {
        return $this->allow_unstorinfo($instance);
    }

    /**
     * Does this plugin allow manual changes in user_storemultiinfos table?
     *
     * All plugins allowing this must implement 'storinfo/xxx:manage' capability
     *
     * @param stdClass $instance course storinfo instance
     * @return bool - true means it is possible to change storinfo period and status in user_storemultiinfos table
     */
    public function allow_manage(stdClass $instance) {
        return false;
    }

    /**
     * Does this plugin support some way to user to self storinfo?
     *
     * @param stdClass $instance course storinfo instance
     *
     * @return bool - true means show "storinfo me in this course" link in course UI
     */
    public function show_storinfome_link(stdClass $instance) {
        return false;
    }

    /**
     * Attempt to automatically storinfo current user in course without any interaction,
     * calling code has to make sure the plugin and instance are active.
     *
     * This should return either a timestamp in the future or false.
     *
     * @param stdClass $instance course storinfo instance
     * @return bool|int false means not storinfoled, integer means timeend
     */
    public function try_autostorinfo(stdClass $instance) {
        global $USER;

        return false;
    }

    /**
     * Attempt to automatically gain temporary guest access to course,
     * calling code has to make sure the plugin and instance are active.
     *
     * This should return either a timestamp in the future or false.
     *
     * @param stdClass $instance course storinfo instance
     * @return bool|int false means no guest access, integer means timeend
     */
    public function try_guestaccess(stdClass $instance) {
        global $USER;

        return false;
    }

    /**
     * storinfo user into course via storinfo instance.
     *
     * @param stdClass $instance
     * @param int $userid
     * @param int $roleid optional role id
     * @param int $timestart 0 means unknown
     * @param int $timeend 0 means forever
     * @param int $status default to storinfo_USER_ACTIVE for new storemultiinfos, no change by default in updates
     * @param bool $recovergrades restore grade history
     * @return void
     */
    public function storinfo_user(stdClass $instance, $userid, $roleid = null, $timestart = 0, $timeend = 0, $status = null, $recovergrades = null) {
        global $DB, $USER, $CFG; // CFG necessary!!!

        if ($instance->courseid == SITEID) {
            throw new coding_exception('invalid attempt to storinfo into frontpage course!');
        }

        $name = $this->get_name();
        $courseid = $instance->courseid;

        if ($instance->storinfo !== $name) {
            throw new coding_exception('invalid storinfo instance!');
        }
        $context = context_course::instance($instance->courseid, MUST_EXIST);
        if (!isset($recovergrades)) {
            $recovergrades = $CFG->recovergradesdefault;
        }

        $inserted = false;
        $updated  = false;
        if ($ue = $DB->get_record('user_storemultiinfos', array('storinfoid'=>$instance->id, 'userid'=>$userid))) {
            //only update if timestart or timeend or status are different.
            if ($ue->timestart != $timestart or $ue->timeend != $timeend or (!is_null($status) and $ue->status != $status)) {
                $this->update_user_storinfo($instance, $userid, $status, $timestart, $timeend);
            }
        } else {
            $ue = new stdClass();
            $ue->storinfoid      = $instance->id;
            $ue->status       = is_null($status) ? storinfo_USER_ACTIVE : $status;
            $ue->userid       = $userid;
            $ue->timestart    = $timestart;
            $ue->timeend      = $timeend;
            $ue->modifierid   = $USER->id;
            $ue->timecreated  = time();
            $ue->timemodified = $ue->timecreated;
            $ue->id = $DB->insert_record('user_storemultiinfos', $ue);

            $inserted = true;
        }

        if ($inserted) {
            // Trigger event.
            $event = \core\event\user_storemultiinfo_created::create(
                    array(
                        'objectid' => $ue->id,
                        'courseid' => $courseid,
                        'context' => $context,
                        'relateduserid' => $ue->userid,
                        'other' => array('storinfo' => $name)
                        )
                    );
            $event->trigger();
        }

        if ($roleid) {
            // this must be done after the storemultiinfo event so that the role_assigned event is triggered afterwards
            if ($this->roles_protected()) {
                role_assign($roleid, $userid, $context->id, 'storinfo_'.$name, $instance->id);
            } else {
                role_assign($roleid, $userid, $context->id);
            }
        }

        // Recover old grades if present.
        if ($recovergrades) {
            require_once("$CFG->libdir/gradelib.php");
            grade_recover_history_grades($userid, $courseid);
        }

        // reset current user storemultiinfo caching
        if ($userid == $USER->id) {
            if (isset($USER->storinfo['storinfoled'][$courseid])) {
                unset($USER->storinfo['storinfoled'][$courseid]);
            }
            if (isset($USER->storinfo['tempguest'][$courseid])) {
                unset($USER->storinfo['tempguest'][$courseid]);
                remove_temp_course_roles($context);
            }
        }
    }

    /**
     * Store user_storemultiinfos changes and trigger event.
     *
     * @param stdClass $instance
     * @param int $userid
     * @param int $status
     * @param int $timestart
     * @param int $timeend
     * @return void
     */
    public function update_user_storinfo(stdClass $instance, $userid, $status = NULL, $timestart = NULL, $timeend = NULL) {
        global $DB, $USER;

        $name = $this->get_name();

        if ($instance->storinfo !== $name) {
            throw new coding_exception('invalid storinfo instance!');
        }

        if (!$ue = $DB->get_record('user_storemultiinfos', array('storinfoid'=>$instance->id, 'userid'=>$userid))) {
            // weird, user not storinfoled
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
        $DB->update_record('user_storemultiinfos', $ue);
        context_course::instance($instance->courseid)->mark_dirty(); // reset storinfo caches

        // Invalidate core_access cache for get_suspended_userids.
        cache_helper::invalidate_by_definition('core', 'suspended_userids', array(), array($instance->courseid));

        // Trigger event.
        $event = \core\event\user_storemultiinfo_updated::create(
                array(
                    'objectid' => $ue->id,
                    'courseid' => $instance->courseid,
                    'context' => context_course::instance($instance->courseid),
                    'relateduserid' => $ue->userid,
                    'other' => array('storinfo' => $name)
                    )
                );
        $event->trigger();
    }

    /**
     * Unstorinfo user from course,
     * the last unstoremultiinfo removes all remaining roles.
     *
     * @param stdClass $instance
     * @param int $userid
     * @return void
     */
    public function unstorinfo_user(stdClass $instance, $userid) {
        global $CFG, $USER, $DB;
        require_once("$CFG->dirroot/group/lib.php");

        $name = $this->get_name();
        $courseid = $instance->courseid;

        if ($instance->storinfo !== $name) {
            throw new coding_exception('invalid storinfo instance!');
        }
        $context = context_course::instance($instance->courseid, MUST_EXIST);

        if (!$ue = $DB->get_record('user_storemultiinfos', array('storinfoid'=>$instance->id, 'userid'=>$userid))) {
            // weird, user not storinfoled
            return;
        }

        // Remove all users groups linked to this storemultiinfo instance.
        if ($gms = $DB->get_records('groups_members', array('userid'=>$userid, 'component'=>'storinfo_'.$name, 'itemid'=>$instance->id))) {
            foreach ($gms as $gm) {
                groups_remove_member($gm->groupid, $gm->userid);
            }
        }

        role_unassign_all(array('userid'=>$userid, 'contextid'=>$context->id, 'component'=>'storinfo_'.$name, 'itemid'=>$instance->id));
        $DB->delete_records('user_storemultiinfos', array('id'=>$ue->id));

        // add extra info and trigger event
        $ue->courseid  = $courseid;
        $ue->storinfo     = $name;

        $sql = "SELECT 'x'
                  FROM {user_storemultiinfos} ue
                  JOIN {storinfo} e ON (e.id = ue.storinfoid)
                 WHERE ue.userid = :userid AND e.courseid = :courseid";
        if ($DB->record_exists_sql($sql, array('userid'=>$userid, 'courseid'=>$courseid))) {
            $ue->laststorinfo = false;

        } else {
            // the big cleanup IS necessary!
            require_once("$CFG->libdir/gradelib.php");

            // remove all remaining roles
            role_unassign_all(array('userid'=>$userid, 'contextid'=>$context->id), true, false);

            //clean up ALL invisible user data from course if this is the last storemultiinfo - groups, grades, etc.
            groups_delete_group_members($courseid, $userid);

            grade_user_unstorinfo($courseid, $userid);

            $DB->delete_records('user_lastaccess', array('userid'=>$userid, 'courseid'=>$courseid));

            $ue->laststorinfo = true; // means user not storinfoled any more
        }
        // Trigger event.
        $event = \core\event\user_storemultiinfo_deleted::create(
                array(
                    'courseid' => $courseid,
                    'context' => $context,
                    'relateduserid' => $ue->userid,
                    'objectid' => $ue->id,
                    'other' => array(
                        'userstoremultiinfo' => (array)$ue,
                        'storinfo' => $name
                        )
                    )
                );
        $event->trigger();
        // reset all storinfo caches
        $context->mark_dirty();

        // reset current user storemultiinfo caching
        if ($userid == $USER->id) {
            if (isset($USER->storinfo['storinfoled'][$courseid])) {
                unset($USER->storinfo['storinfoled'][$courseid]);
            }
            if (isset($USER->storinfo['tempguest'][$courseid])) {
                unset($USER->storinfo['tempguest'][$courseid]);
                remove_temp_course_roles($context);
            }
        }
    }

    /**
     * Forces synchronisation of user storemultiinfos.
     *
     * This is important especially for external storinfo plugins,
     * this function is called for all enabled storinfo plugins
     * right after every user login.
     *
     * @param object $user user record
     * @return void
     */
    public function sync_user_storemultiinfos($user) {
        // override if necessary
    }

    /**
     * Returns link to page which may be used to add new instance of storemultiinfo plugin in course.
     * @param int $courseid
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
        // override for most plugins, check if instance already exists in cases only one instance is supported
        return NULL;
    }

    /**
     * Is it possible to delete storinfo instance via standard UI?
     *
     * @deprecated since Moodle 2.8 MDL-35864 - please use can_delete_instance() instead.
     * @todo MDL-46479 This will be deleted in Moodle 3.0.
     * @see class_name::can_delete_instance()
     * @param object $instance
     * @return bool
     */
    public function instance_deleteable($instance) {
        debugging('Function storinfo_plugin::instance_deleteable() is deprecated', DEBUG_DEVELOPER);
        return $this->can_delete_instance($instance);
    }

    /**
     * Is it possible to delete storinfo instance via standard UI?
     *
     * @param stdClass  $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        return false;
    }

    /**
     * Is it possible to hide/show storinfo instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        debugging("The storemultiinfo plugin '".$this->get_name()."' should override the function can_hide_show_instance().", DEBUG_DEVELOPER);
        return true;
    }

    /**
     * Returns link to manual storinfo UI if exists.
     * Does the access control tests automatically.
     *
     * @param object $instance
     * @return moodle_url
     */
    public function get_manual_storinfo_link($instance) {
        return NULL;
    }

    /**
     * Returns list of unstorinfo links for all storinfo instances in course.
     *
     * @param int $instance
     * @return moodle_url or NULL if self unstoremultiinfo not supported
     */
    public function get_unstorinfoself_link($instance) {
        global $USER, $CFG, $DB;

        $name = $this->get_name();
        if ($instance->storinfo !== $name) {
            throw new coding_exception('invalid storinfo instance!');
        }

        if ($instance->courseid == SITEID) {
            return NULL;
        }

        if (!storinfo_is_enabled($name)) {
            return NULL;
        }

        if ($instance->status != storinfo_INSTANCE_ENABLED) {
            return NULL;
        }

        if (!file_exists("$CFG->dirroot/storinfo/$name/unstorinfoself.php")) {
            return NULL;
        }

        $context = context_course::instance($instance->courseid, MUST_EXIST);

        if (!has_capability("storinfo/$name:unstorinfoself", $context)) {
            return NULL;
        }

        if (!$DB->record_exists('user_storemultiinfos', array('storinfoid'=>$instance->id, 'userid'=>$USER->id, 'status'=>storinfo_USER_ACTIVE))) {
            return NULL;
        }

        return new moodle_url("/storinfo/$name/unstorinfoself.php", array('storinfoid'=>$instance->id));
    }

    /**
     * Adds storinfo instance UI to course edit form
     *
     * @param object $instance storinfo instance or null if does not exist yet
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
     * @param object $instance storinfo instance or null if does not exist yet
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
            if ($this->get_config('defaultstorinfo')) {
                $this->add_default_instance($course);
            }
        }
    }

    /**
     * Add new instance of storinfo plugin.
     * @param object $course
     * @param array instance fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, array $fields = NULL) {
        global $DB;

        if ($course->id == SITEID) {
            throw new coding_exception('Invalid request to add storinfo instance to frontpage.');
        }

        $instance = new stdClass();
        $instance->storinfo          = $this->get_name();
        $instance->status         = storinfo_INSTANCE_ENABLED;
        $instance->courseid       = $course->id;
        $instance->storinfostartdate = 0;
        $instance->storinfoenddate   = 0;
        $instance->timemodified   = time();
        $instance->timecreated    = $instance->timemodified;
        $instance->sortorder      = $DB->get_field('storinfo', 'COALESCE(MAX(sortorder), -1) + 1', array('courseid'=>$course->id));

        $fields = (array)$fields;
        unset($fields['storinfo']);
        unset($fields['courseid']);
        unset($fields['sortorder']);
        foreach($fields as $field=>$value) {
            $instance->$field = $value;
        }

        return $DB->insert_record('storinfo', $instance);
    }

    /**
     * Add new instance of storinfo plugin with default settings,
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
     * @param int $newstatus storinfo_INSTANCE_ENABLED, storinfo_INSTANCE_DISABLED
     * @return void
     */
    public function update_status($instance, $newstatus) {
        global $DB;

        $instance->status = $newstatus;
        $DB->update_record('storinfo', $instance);

        // invalidate all storinfo caches
        $context = context_course::instance($instance->courseid);
        $context->mark_dirty();
    }

    /**
     * Delete course storinfo plugin instance, unstorinfo all users.
     * @param object $instance
     * @return void
     */
    public function delete_instance($instance) {
        global $DB;

        $name = $this->get_name();
        if ($instance->storinfo !== $name) {
            throw new coding_exception('invalid storinfo instance!');
        }

        //first unstorinfo all users
        $participants = $DB->get_recordset('user_storemultiinfos', array('storinfoid'=>$instance->id));
        foreach ($participants as $participant) {
            $this->unstorinfo_user($instance, $participant->userid);
        }
        $participants->close();

        // now clean up all remainders that were not removed correctly
        $DB->delete_records('groups_members', array('itemid'=>$instance->id, 'component'=>'storinfo_'.$name));
        $DB->delete_records('role_assignments', array('itemid'=>$instance->id, 'component'=>'storinfo_'.$name));
        $DB->delete_records('user_storemultiinfos', array('storinfoid'=>$instance->id));

        // finally drop the storinfo row
        $DB->delete_records('storinfo', array('id'=>$instance->id));

        // invalidate all storinfo caches
        $context = context_course::instance($instance->courseid);
        $context->mark_dirty();
    }

    /**
     * Creates course storinfo form, checks if form submitted
     * and storinfos user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    public function storinfo_page_hook(stdClass $instance) {
        return null;
    }

    /**
     * Checks if user can self storinfo.
     *
     * @param stdClass $instance storemultiinfo instance
     * @param bool $checkuserstoremultiinfo if true will check if user storemultiinfo is inactive.
     *             used by navigation to improve performance.
     * @return bool|string true if successful, else error message or false
     */
    public function can_self_storinfo(stdClass $instance, $checkuserstoremultiinfo = true) {
        return false;
    }

    /**
     * Return information for storemultiinfo instance containing list of parameters required
     * for storemultiinfo, name of storemultiinfo plugin etc.
     *
     * @param stdClass $instance storemultiinfo instance
     * @return array instance info.
     */
    public function get_storinfo_info(stdClass $instance) {
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
        $versionfile = "$CFG->dirroot/storinfo/$name/version.php";
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
     * Called for all enabled storinfo plugins that returned true from is_cron_required().
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
                  FROM {storinfo} e
                  JOIN {user_storemultiinfos} ue ON (ue.storinfoid = e.id)
                 WHERE e.storinfo = :name AND ue.userid = :userid";
        $params = array('name'=>$this->get_name(), 'userid'=>$user->id);

        $rs = $DB->get_recordset_sql($sql, $params);
        foreach($rs as $instance) {
            $this->unstorinfo_user($instance, $user->id);
        }
        $rs->close();
    }

    /**
     * Returns an storinfo_user_button that takes the user to a page where they are able to
     * storinfo users into the managers course through this plugin.
     *
     * Optional: If the plugin supports manual storemultiinfos it can choose to override this
     * otherwise it shouldn't
     *
     * @param course_storemultiinfo_manager $manager
     * @return storinfo_user_button|false
     */
    public function get_manual_storinfo_button(course_storemultiinfo_manager $manager) {
        return false;
    }

    /**
     * Gets an array of the user storemultiinfo actions
     *
     * @param course_storemultiinfo_manager $manager
     * @param stdClass $ue
     * @return array An array of user_storemultiinfo_actions
     */
    public function get_user_storemultiinfo_actions(course_storemultiinfo_manager $manager, $ue) {
        return array();
    }

    /**
     * Returns true if the plugin has one or more bulk operations that can be performed on
     * user storemultiinfos.
     *
     * @param course_storemultiinfo_manager $manager
     * @return bool
     */
    public function has_bulk_operations(course_storemultiinfo_manager $manager) {
       return false;
    }

    /**
     * Return an array of storinfo_bulk_storemultiinfo_operation objects that define
     * the bulk actions that can be performed on user storemultiinfos by the plugin.
     *
     * @param course_storemultiinfo_manager $manager
     * @return array
     */
    public function get_bulk_operations(course_storemultiinfo_manager $manager) {
        return array();
    }

    /**
     * Do any storemultiinfos need expiration processing.
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
        if (!storinfo_is_enabled($name)) {
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
        $action = $this->get_config('expiredaction', storinfo_EXT_REMOVED_KEEP);

        if ($action == storinfo_EXT_REMOVED_UNstorinfo) {
            $instances = array();
            $sql = "SELECT ue.*, e.courseid, c.id AS contextid
                      FROM {user_storemultiinfos} ue
                      JOIN {storinfo} e ON (e.id = ue.storinfoid AND e.storinfo = :storinfo)
                      JOIN {context} c ON (c.instanceid = e.courseid AND c.contextlevel = :courselevel)
                     WHERE ue.timeend > 0 AND ue.timeend < :now $coursesql";
            $params = array('now'=>time(), 'courselevel'=>CONTEXT_COURSE, 'storinfo'=>$name, 'courseid'=>$courseid);

            $rs = $DB->get_recordset_sql($sql, $params);
            foreach ($rs as $ue) {
                if (!$processed) {
                    $trace->output("Starting processing of storinfo_$name expirations...");
                    $processed = true;
                }
                if (empty($instances[$ue->storinfoid])) {
                    $instances[$ue->storinfoid] = $DB->get_record('storinfo', array('id'=>$ue->storinfoid));
                }
                $instance = $instances[$ue->storinfoid];
                if (!$this->roles_protected()) {
                    // Let's just guess what extra roles are supposed to be removed.
                    if ($instance->roleid) {
                        role_unassign($instance->roleid, $ue->userid, $ue->contextid);
                    }
                }
                // The unstorinfo cleans up all subcontexts if this is the only course storemultiinfo for this user.
                $this->unstorinfo_user($instance, $ue->userid);
                $trace->output("Unstorinfoling expired user $ue->userid from course $instance->courseid", 1);
            }
            $rs->close();
            unset($instances);

        } else if ($action == storinfo_EXT_REMOVED_SUSPENDNOROLES or $action == storinfo_EXT_REMOVED_SUSPEND) {
            $instances = array();
            $sql = "SELECT ue.*, e.courseid, c.id AS contextid
                      FROM {user_storemultiinfos} ue
                      JOIN {storinfo} e ON (e.id = ue.storinfoid AND e.storinfo = :storinfo)
                      JOIN {context} c ON (c.instanceid = e.courseid AND c.contextlevel = :courselevel)
                     WHERE ue.timeend > 0 AND ue.timeend < :now
                           AND ue.status = :useractive $coursesql";
            $params = array('now'=>time(), 'courselevel'=>CONTEXT_COURSE, 'useractive'=>storinfo_USER_ACTIVE, 'storinfo'=>$name, 'courseid'=>$courseid);
            $rs = $DB->get_recordset_sql($sql, $params);
            foreach ($rs as $ue) {
                if (!$processed) {
                    $trace->output("Starting processing of storinfo_$name expirations...");
                    $processed = true;
                }
                if (empty($instances[$ue->storinfoid])) {
                    $instances[$ue->storinfoid] = $DB->get_record('storinfo', array('id'=>$ue->storinfoid));
                }
                $instance = $instances[$ue->storinfoid];

                if ($action == storinfo_EXT_REMOVED_SUSPENDNOROLES) {
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
                    role_unassign_all(array('userid'=>$ue->userid, 'contextid'=>$ue->contextid, 'component'=>'storinfo_'.$name, 'itemid'=>$instance->id), true);
                    // Final cleanup of subcontexts if there are no more course roles.
                    if (0 == $DB->count_records('role_assignments', array('userid'=>$ue->userid, 'contextid'=>$ue->contextid))) {
                        role_unassign_all(array('userid'=>$ue->userid, 'contextid'=>$ue->contextid, 'component'=>'', 'itemid'=>0), true);
                    }
                }

                $this->update_user_storinfo($instance, $ue->userid, storinfo_USER_SUSPENDED);
                $trace->output("Suspending expired user $ue->userid in course $instance->courseid", 1);
            }
            $rs->close();
            unset($instances);

        } else {
            // storinfo_EXT_REMOVED_KEEP means no changes.
        }

        if ($processed) {
            $trace->output("...finished processing of storinfo_$name expirations");
        } else {
            $trace->output("No expired storinfo_$name storemultiinfos detected");
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
     * - notification strings (expirymessagestorinfolersubject, expirymessagestorinfolerbody,
     *   expirymessagestorinfoledsubject and expirymessagestorinfoledbody),
     * - expiry_notification provider in db/messages.php,
     * - upgrade code that sets default thresholds for existing courses (should be 1 day),
     * - something that calls this method, such as cron.
     *
     * @param progress_trace $trace (accepts bool for backwards compatibility only)
     */
    public function send_expiry_notifications($trace) {
        global $DB, $CFG;

        $name = $this->get_name();
        if (!storinfo_is_enabled($name)) {
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
            debugging("send_expiry_notifications() in $name storemultiinfo plugin needs expirynotifyhour setting");
            $trace->finished();
            return;
        }

        if (!($trace instanceof progress_trace)) {
            $trace = $trace ? new text_progress_trace() : new null_progress_trace();
            debugging('storinfo_plugin::send_expiry_notifications() now expects progress_trace instance as parameter!', DEBUG_DEVELOPER);
        }

        $timenow = time();
        $notifytime = usergetmidnight($timenow, $CFG->timezone) + ($expirynotifyhour * 3600);

        if ($expirynotifylast > $notifytime) {
            $trace->output($name.' storemultiinfo expiry notifications were already sent today at '.userdate($expirynotifylast, '', $CFG->timezone).'.');
            $trace->finished();
            return;

        } else if ($timenow < $notifytime) {
            $trace->output($name.' storemultiinfo expiry notifications will be sent at '.userdate($notifytime, '', $CFG->timezone).'.');
            $trace->finished();
            return;
        }

        $trace->output('Processing '.$name.' storemultiinfo expiration notifications...');

        // Notify users responsible for storemultiinfo once every day.
        $sql = "SELECT ue.*, e.expirynotify, e.notifyall, e.expirythreshold, e.courseid, c.fullname
                  FROM {user_storemultiinfos} ue
                  JOIN {storinfo} e ON (e.id = ue.storinfoid AND e.storinfo = :name AND e.expirynotify > 0 AND e.status = :enabled)
                  JOIN {course} c ON (c.id = e.courseid)
                  JOIN {user} u ON (u.id = ue.userid AND u.deleted = 0 AND u.suspended = 0)
                 WHERE ue.status = :active AND ue.timeend > 0 AND ue.timeend > :now1 AND ue.timeend < (e.expirythreshold + :now2)
              ORDER BY ue.storinfoid ASC, u.lastname ASC, u.firstname ASC, u.id ASC";
        $params = array('enabled'=>storinfo_INSTANCE_ENABLED, 'active'=>storinfo_USER_ACTIVE, 'now1'=>$timenow, 'now2'=>$timenow, 'name'=>$name);

        $rs = $DB->get_recordset_sql($sql, $params);

        $laststorinfolid = 0;
        $users = array();

        foreach($rs as $ue) {
            if ($laststorinfolid and $laststorinfolid != $ue->storinfoid) {
                $this->notify_expiry_storinfoler($laststorinfolid, $users, $trace);
                $users = array();
            }
            $laststorinfolid = $ue->storinfoid;

            $storinfoler = $this->get_storinfoler($ue->storinfoid);
            $context = context_course::instance($ue->courseid);

            $user = $DB->get_record('user', array('id'=>$ue->userid));

            $users[] = array('fullname'=>fullname($user, has_capability('moodle/site:viewfullnames', $context, $storinfoler)), 'timeend'=>$ue->timeend);

            if (!$ue->notifyall) {
                continue;
            }

            if ($ue->timeend - $ue->expirythreshold + 86400 < $timenow) {
                // Notify storinfoled users only once at the start of the threshold.
                $trace->output("user $ue->userid was already notified that storemultiinfo in course $ue->courseid expires on ".userdate($ue->timeend, '', $CFG->timezone), 1);
                continue;
            }

            $this->notify_expiry_storinfoled($user, $ue, $trace);
        }
        $rs->close();

        if ($laststorinfolid and $users) {
            $this->notify_expiry_storinfoler($laststorinfolid, $users, $trace);
        }

        $trace->output('...notification processing finished.');
        $trace->finished();

        $this->set_config('expirynotifylast', $timenow);
    }

    /**
     * Returns the user who is responsible for storemultiinfos for given instance.
     *
     * Override if plugin knows anybody better than admin.
     *
     * @param int $instanceid storemultiinfo instance id
     * @return stdClass user record
     */
    protected function get_storinfoler($instanceid) {
        return get_admin();
    }

    /**
     * Notify user about incoming expiration of their storemultiinfo,
     * it is called only if notification of storinfoled users (aka students) is enabled in course.
     *
     * This is executed only once for each expiring storemultiinfo right
     * at the start of the expiration threshold.
     *
     * @param stdClass $user
     * @param stdClass $ue
     * @param progress_trace $trace
     */
    protected function notify_expiry_storinfoled($user, $ue, progress_trace $trace) {
        global $CFG;

        $name = $this->get_name();

        $oldforcelang = force_current_language($user->lang);

        $storinfoler = $this->get_storinfoler($ue->storinfoid);
        $context = context_course::instance($ue->courseid);

        $a = new stdClass();
        $a->course   = format_string($ue->fullname, true, array('context'=>$context));
        $a->user     = fullname($user, true);
        $a->timeend  = userdate($ue->timeend, '', $user->timezone);
        $a->storinfoler = fullname($storinfoler, has_capability('moodle/site:viewfullnames', $context, $user));

        $subject = get_string('expirymessagestorinfoledsubject', 'storinfo_'.$name, $a);
        $body = get_string('expirymessagestorinfoledbody', 'storinfo_'.$name, $a);

        $message = new stdClass();
        $message->notification      = 1;
        $message->component         = 'storinfo_'.$name;
        $message->name              = 'expiry_notification';
        $message->userfrom          = $storinfoler;
        $message->userto            = $user;
        $message->subject           = $subject;
        $message->fullmessage       = $body;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml   = markdown_to_html($body);
        $message->smallmessage      = $subject;
        $message->contexturlname    = $a->course;
        $message->contexturl        = (string)new moodle_url('/course/view.php', array('id'=>$ue->courseid));

        if (message_send($message)) {
            $trace->output("notifying user $ue->userid that storemultiinfo in course $ue->courseid expires on ".userdate($ue->timeend, '', $CFG->timezone), 1);
        } else {
            $trace->output("error notifying user $ue->userid that storemultiinfo in course $ue->courseid expires on ".userdate($ue->timeend, '', $CFG->timezone), 1);
        }

        force_current_language($oldforcelang);
    }

    /**
     * Notify person responsible for storemultiinfos that some user storemultiinfos will be expired soon,
     * it is called only if notification of storinfolers (aka teachers) is enabled in course.
     *
     * This is called repeatedly every day for each course if there are any pending expiration
     * in the expiration threshold.
     *
     * @param int $eid
     * @param array $users
     * @param progress_trace $trace
     */
    protected function notify_expiry_storinfoler($eid, $users, progress_trace $trace) {
        global $DB;

        $name = $this->get_name();

        $instance = $DB->get_record('storinfo', array('id'=>$eid, 'storinfo'=>$name));
        $context = context_course::instance($instance->courseid);
        $course = $DB->get_record('course', array('id'=>$instance->courseid));

        $storinfoler = $this->get_storinfoler($instance->id);
        $admin = get_admin();

        $oldforcelang = force_current_language($storinfoler->lang);

        foreach($users as $key=>$info) {
            $users[$key] = '* '.$info['fullname'].' - '.userdate($info['timeend'], '', $storinfoler->timezone);
        }

        $a = new stdClass();
        $a->course    = format_string($course->fullname, true, array('context'=>$context));
        $a->threshold = get_string('numdays', '', $instance->expirythreshold / (60*60*24));
        $a->users     = implode("\n", $users);
        $a->extendurl = (string)new moodle_url('/storinfo/users.php', array('id'=>$instance->courseid));

        $subject = get_string('expirymessagestorinfolersubject', 'storinfo_'.$name, $a);
        $body = get_string('expirymessagestorinfolerbody', 'storinfo_'.$name, $a);

        $message = new stdClass();
        $message->notification      = 1;
        $message->component         = 'storinfo_'.$name;
        $message->name              = 'expiry_notification';
        $message->userfrom          = $admin;
        $message->userto            = $storinfoler;
        $message->subject           = $subject;
        $message->fullmessage       = $body;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml   = markdown_to_html($body);
        $message->smallmessage      = $subject;
        $message->contexturlname    = $a->course;
        $message->contexturl        = $a->extendurl;

        if (message_send($message)) {
            $trace->output("notifying user $storinfoler->id about all expiring $name storemultiinfos in course $instance->courseid", 1);
        } else {
            $trace->output("error notifying user $storinfoler->id about all expiring $name storemultiinfos in course $instance->courseid", 1);
        }

        force_current_language($oldforcelang);
    }

    /**
     * Backup execution step hook to annotate custom fields.
     *
     * @param backup_storemultiinfos_execution_step $step
     * @param stdClass $storinfo
     */
    public function backup_annotate_custom_fields(backup_storemultiinfos_execution_step $step, stdClass $storinfo) {
        // Override as necessary to annotate custom fields in the storinfo table.
    }

    /**
     * Automatic storinfo sync executed during restore.
     * Useful for automatic sync by course->idnumber or course category.
     * @param stdClass $course course record
     */
    public function restore_sync_course($course) {
        // Override if necessary.
    }

    /**
     * Restore instance and map settings.
     *
     * @param restore_storemultiinfos_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_storemultiinfos_structure_step $step, stdClass $data, $course, $oldid) {
        // Do not call this from overridden methods, restore and set new id there.
        $step->set_mapping('storinfo', $oldid, 0);
    }

    /**
     * Restore user storemultiinfo.
     *
     * @param restore_storemultiinfos_structure_step $step
     * @param stdClass $data
     * @param stdClass $instance
     * @param int $oldinstancestatus
     * @param int $userid
     */
    public function restore_user_storemultiinfo(restore_storemultiinfos_structure_step $step, $data, $instance, $userid, $oldinstancestatus) {
        // Override as necessary if plugin supports restore of storemultiinfos.
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
