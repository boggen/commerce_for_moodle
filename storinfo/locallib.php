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
 * This file contains the course_storemultiinfo_manager class which is used to interface
 * with the functions that exist in storinfolib.php in relation to a single course.
 *
 * @package    core_storinfo
 * @copyright  2010 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This class provides a targeted tied together means of interfacing the storemultiinfo
 * tasks together with a course.
 *
 * It is provided as a convenience more than anything else.
 *
 * @copyright 2010 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_storemultiinfo_manager {

    /**
     * The course context
     * @var stdClass
     */
    protected $context;
    /**
     * The course we are managing storemultiinfos for
     * @var stdClass
     */
    protected $course = null;
    /**
     * Limits the focus of the manager to one storemultiinfo plugin instance
     * @var string
     */
    protected $instancefilter = null;
    /**
     * Limits the focus of the manager to users with specified role
     * @var int
     */
    protected $rolefilter = 0;
    /**
     * Limits the focus of the manager to users who match search string
     * @var string
     */
    protected $searchfilter = '';
    /**
     * Limits the focus of the manager to users in specified group
     * @var int
     */
    protected $groupfilter = 0;
    /**
     * Limits the focus of the manager to users who match status active/inactive
     * @var int
     */
    protected $statusfilter = -1;

    /**
     * The total number of users storinfoled in the course
     * Populated by course_storemultiinfo_manager::get_total_users
     * @var int
     */
    protected $totalusers = null;
    /**
     * An array of users currently storinfoled in the course
     * Populated by course_storemultiinfo_manager::get_users
     * @var array
     */
    protected $users = array();

    /**
     * An array of users who have roles within this course but who have not
     * been storinfoled in the course
     * @var array
     */
    protected $otherusers = array();

    /**
     * The total number of users who hold a role within the course but who
     * arn't storinfoled.
     * @var int
     */
    protected $totalotherusers = null;

    /**
     * The current moodle_page object
     * @var moodle_page
     */
    protected $moodlepage = null;

    /**#@+
     * These variables are used to cache the information this class uses
     * please never use these directly instead use their get_ counterparts.
     * @access private
     * @var array
     */
    private $_instancessql = null;
    private $_instances = null;
    private $_inames = null;
    private $_plugins = null;
    private $_allplugins = null;
    private $_roles = null;
    private $_assignableroles = null;
    private $_assignablerolesothers = null;
    private $_groups = null;
    /**#@-*/

    /**
     * Constructs the course storemultiinfo manager
     *
     * @param moodle_page $moodlepage
     * @param stdClass $course
     * @param string $instancefilter
     * @param int $rolefilter If non-zero, filters to users with specified role
     * @param string $searchfilter If non-blank, filters to users with search text
     * @param int $groupfilter if non-zero, filter users with specified group
     * @param int $statusfilter if not -1, filter users with active/inactive storemultiinfo.
     */
    public function __construct(moodle_page $moodlepage, $course, $instancefilter = null,
            $rolefilter = 0, $searchfilter = '', $groupfilter = 0, $statusfilter = -1) {
        $this->moodlepage = $moodlepage;
        $this->context = context_course::instance($course->id);
        $this->course = $course;
        $this->instancefilter = $instancefilter;
        $this->rolefilter = $rolefilter;
        $this->searchfilter = $searchfilter;
        $this->groupfilter = $groupfilter;
        $this->statusfilter = $statusfilter;
    }

    /**
     * Returns the current moodle page
     * @return moodle_page
     */
    public function get_moodlepage() {
        return $this->moodlepage;
    }

    /**
     * Returns the total number of storinfoled users in the course.
     *
     * If a filter was specificed this will be the total number of users storinfoled
     * in this course by means of that instance.
     *
     * @global moodle_database $DB
     * @return int
     */
	
    public function get_total_users() {
        global $DB;
        if ($this->totalusers === null) {
            list($instancessql, $params, $filter) = $this->get_instance_sql();
            list($filtersql, $moreparams) = $this->get_filter_sql();
            $params += $moreparams;
            $sqltotal = "SELECT COUNT(DISTINCT u.id)
                           FROM {user} u
                           JOIN {user_storemultiinfos} ue ON (ue.userid = u.id  AND ue.storinfoid $instancessql)
                           JOIN {storinfo} e ON (e.id = ue.storinfoid)
                      LEFT JOIN {groups_members} gm ON u.id = gm.userid
                          WHERE $filtersql";
            $this->totalusers = (int)$DB->count_records_sql($sqltotal, $params);
        }
        return $this->totalusers;
    }

    /**
     * Returns the total number of storinfoled users in the course.
     *
     * If a filter was specificed this will be the total number of users storinfoled
     * in this course by means of that instance.
     *
     * @global moodle_database $DB
     * @return int
     */

    public function get_total_other_users() {
        global $DB;
        if ($this->totalotherusers === null) {
            list($ctxcondition, $params) = $DB->get_in_or_equal($this->context->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'ctx');
            $params['courseid'] = $this->course->id;
            $sql = "SELECT COUNT(DISTINCT u.id)
                      FROM {role_assignments} ra
                      JOIN {user} u ON u.id = ra.userid
                      JOIN {context} ctx ON ra.contextid = ctx.id
                 LEFT JOIN (
                           SELECT ue.id, ue.userid
                             FROM {user_storemultiinfos} ue
                        LEFT JOIN {storinfo} e ON e.id=ue.storinfoid
                            WHERE e.courseid = :courseid
                         ) ue ON ue.userid=u.id
                     WHERE ctx.id $ctxcondition AND
                           ue.id IS NULL";
            $this->totalotherusers = (int)$DB->count_records_sql($sql, $params);
        }
        return $this->totalotherusers;
    }

    /**
     * Gets all of the users storinfoled in this course.
     *
     * If a filter was specified this will be the users who were storinfoled
     * in this course by means of that instance. If role or search filters were
     * specified then these will also be applied.
     *
     * @global moodle_database $DB
     * @param string $sort
     * @param string $direction ASC or DESC
     * @param int $page First page should be 0
     * @param int $perpage Defaults to 25
     * @return array
     */

    public function get_users($sort, $direction='ASC', $page=0, $perpage=25) {
        global $DB;
        if ($direction !== 'ASC') {
            $direction = 'DESC';
        }
        $key = md5("$sort-$direction-$page-$perpage");
        if (!array_key_exists($key, $this->users)) {
            list($instancessql, $params, $filter) = $this->get_instance_sql();
            list($filtersql, $moreparams) = $this->get_filter_sql();
            $params += $moreparams;
            $extrafields = get_extra_user_fields($this->get_context());
            $extrafields[] = 'lastaccess';
            $ufields = user_picture::fields('u', $extrafields);
            $sql = "SELECT DISTINCT $ufields, ul.timeaccess AS lastseen
                      FROM {user} u
                      JOIN {user_storemultiinfos} ue ON (ue.userid = u.id  AND ue.storinfoid $instancessql)
                      JOIN {storinfo} e ON (e.id = ue.storinfoid)
                 LEFT JOIN {user_lastaccess} ul ON (ul.courseid = e.courseid AND ul.userid = u.id)
                 LEFT JOIN {groups_members} gm ON u.id = gm.userid
                     WHERE $filtersql
                  ORDER BY u.$sort $direction";
            $this->users[$key] = $DB->get_records_sql($sql, $params, $page*$perpage, $perpage);
        }
        return $this->users[$key];
    }

    /**
     * Obtains WHERE clause to filter results by defined search and role filter
     * (instance filter is handled separately in JOIN clause, see
     * get_instance_sql).
     *
     * @return array Two-element array with SQL and params for WHERE clause
     */
	
    protected function get_filter_sql() {
        global $DB;

        // Search condition.
        $extrafields = get_extra_user_fields($this->get_context());
        list($sql, $params) = users_search_sql($this->searchfilter, 'u', true, $extrafields);

        // Role condition.
        if ($this->rolefilter) {
            // Get context SQL.
            $contextids = $this->context->get_parent_context_ids();
            $contextids[] = $this->context->id;
            list($contextsql, $contextparams) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);
            $params += $contextparams;

            // Role check condition.
            $sql .= " AND (SELECT COUNT(1) FROM {role_assignments} ra WHERE ra.userid = u.id " .
                    "AND ra.roleid = :roleid AND ra.contextid $contextsql) > 0";
            $params['roleid'] = $this->rolefilter;
        }

        // Group condition.
        if ($this->groupfilter) {
            $sql .= " AND gm.groupid = :groupid";
            $params['groupid'] = $this->groupfilter;
        }

        // Status condition.
        if ($this->statusfilter === storinfo_USER_ACTIVE) {
            $sql .= " AND ue.status = :active AND e.status = :enabled AND ue.timestart < :now1
                    AND (ue.timeend = 0 OR ue.timeend > :now2)";
            $now = round(time(), -2); // rounding helps caching in DB
            $params += array('enabled' => storinfo_INSTANCE_ENABLED,
                             'active' => storinfo_USER_ACTIVE,
                             'now1' => $now,
                             'now2' => $now);
        } else if ($this->statusfilter === storinfo_USER_SUSPENDED) {
            $sql .= " AND (ue.status = :inactive OR e.status = :disabled OR ue.timestart > :now1
                    OR (ue.timeend <> 0 AND ue.timeend < :now2))";
            $now = round(time(), -2); // rounding helps caching in DB
            $params += array('disabled' => storinfo_INSTANCE_DISABLED,
                             'inactive' => storinfo_USER_SUSPENDED,
                             'now1' => $now,
                             'now2' => $now);
        }

        return array($sql, $params);
    }

    /**
     * Gets and array of other users.
     *
     * Other users are users who have been assigned roles or inherited roles
     * within this course but who have not been storinfoled in the course
     *
     * @global moodle_database $DB
     * @param string $sort
     * @param string $direction
     * @param int $page
     * @param int $perpage
     * @return array
     */

    public function get_other_users($sort, $direction='ASC', $page=0, $perpage=25) {
        global $DB;
        if ($direction !== 'ASC') {
            $direction = 'DESC';
        }
        $key = md5("$sort-$direction-$page-$perpage");
        if (!array_key_exists($key, $this->otherusers)) {
            list($ctxcondition, $params) = $DB->get_in_or_equal($this->context->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'ctx');
            $params['courseid'] = $this->course->id;
            $params['cid'] = $this->course->id;
            $sql = "SELECT ra.id as raid, ra.contextid, ra.component, ctx.contextlevel, ra.roleid, u.*, ue.lastseen
                    FROM {role_assignments} ra
                    JOIN {user} u ON u.id = ra.userid
                    JOIN {context} ctx ON ra.contextid = ctx.id
               LEFT JOIN (
                       SELECT ue.id, ue.userid, ul.timeaccess AS lastseen
                         FROM {user_storemultiinfos} ue
                    LEFT JOIN {storinfo} e ON e.id=ue.storinfoid
                    LEFT JOIN {user_lastaccess} ul ON (ul.courseid = e.courseid AND ul.userid = ue.userid)
                        WHERE e.courseid = :courseid
                       ) ue ON ue.userid=u.id
                   WHERE ctx.id $ctxcondition AND
                         ue.id IS NULL
                ORDER BY u.$sort $direction, ctx.depth DESC";
            $this->otherusers[$key] = $DB->get_records_sql($sql, $params, $page*$perpage, $perpage);
        }
        return $this->otherusers[$key];
    }

    /**
     * Helper method used by {@link get_potential_users()} and {@link search_other_users()}.
     *
     * @param string $search the search term, if any.
     * @param bool $searchanywhere Can the search term be anywhere, or must it be at the start.
     * @return array with three elements:
     *     string list of fields to SELECT,
     *     string contents of SQL WHERE clause,
     *     array query params. Note that the SQL snippets use named parameters.
     */

    protected function get_basic_search_conditions($search, $searchanywhere) {
        global $DB, $CFG;

        // Add some additional sensible conditions
        $tests = array("u.id <> :guestid", 'u.deleted = 0', 'u.confirmed = 1');
        $params = array('guestid' => $CFG->siteguest);
        if (!empty($search)) {
            $conditions = get_extra_user_fields($this->get_context());
            $conditions[] = 'u.firstname';
            $conditions[] = 'u.lastname';
            $conditions[] = $DB->sql_fullname('u.firstname', 'u.lastname');
            if ($searchanywhere) {
                $searchparam = '%' . $search . '%';
            } else {
                $searchparam = $search . '%';
            }
            $i = 0;
            foreach ($conditions as $key => $condition) {
                $conditions[$key] = $DB->sql_like($condition, ":con{$i}00", false);
                $params["con{$i}00"] = $searchparam;
                $i++;
            }
            $tests[] = '(' . implode(' OR ', $conditions) . ')';
        }
        $wherecondition = implode(' AND ', $tests);

        $extrafields = get_extra_user_fields($this->get_context(), array('username', 'lastaccess'));
        $extrafields[] = 'username';
        $extrafields[] = 'lastaccess';
        $ufields = user_picture::fields('u', $extrafields);

        return array($ufields, $params, $wherecondition);
    }

    /**
     * Helper method used by {@link get_potential_users()} and {@link search_other_users()}.
     *
     * @param string $search the search string, if any.
     * @param string $fields the first bit of the SQL when returning some users.
     * @param string $countfields fhe first bit of the SQL when counting the users.
     * @param string $sql the bulk of the SQL statement.
     * @param array $params query parameters.
     * @param int $page which page number of the results to show.
     * @param int $perpage number of users per page.
     * @param int $addedstoremultiinfo number of users added to storemultiinfo.
     * @return array with two elememts:
     *      int total number of users matching the search.
     *      array of user objects returned by the query.
     */

    protected function execute_search_queries($search, $fields, $countfields, $sql, array $params, $page, $perpage, $addedstoremultiinfo=0) {
        global $DB, $CFG;

        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->get_context());
        $order = ' ORDER BY ' . $sort;

        $totalusers = $DB->count_records_sql($countfields . $sql, $params);
        $availableusers = $DB->get_records_sql($fields . $sql . $order,
                array_merge($params, $sortparams), ($page*$perpage) - $addedstoremultiinfo, $perpage);

        return array('totalusers' => $totalusers, 'users' => $availableusers);
    }

    /**
     * Gets an array of the users that can be storinfoled in this course.
     *
     * @global moodle_database $DB
     * @param int $storinfoid
     * @param string $search
     * @param bool $searchanywhere
     * @param int $page Defaults to 0
     * @param int $perpage Defaults to 25
     * @param int $addedstoremultiinfo Defaults to 0
     * @return array Array(totalusers => int, users => array)
     */

    public function get_potential_users($storinfoid, $search='', $searchanywhere=false, $page=0, $perpage=25, $addedstoremultiinfo=0) {
        global $DB;

        list($ufields, $params, $wherecondition) = $this->get_basic_search_conditions($search, $searchanywhere);

        $fields      = 'SELECT '.$ufields;
        $countfields = 'SELECT COUNT(1)';
        $sql = " FROM {user} u
            LEFT JOIN {user_storemultiinfos} ue ON (ue.userid = u.id AND ue.storinfoid = :storinfoid)
                WHERE $wherecondition
                      AND ue.id IS NULL";
        $params['storinfoid'] = $storinfoid;

        return $this->execute_search_queries($search, $fields, $countfields, $sql, $params, $page, $perpage, $addedstoremultiinfo);
    }

    /**
     * Searches other users and returns paginated results
     *
     * @global moodle_database $DB
     * @param string $search
     * @param bool $searchanywhere
     * @param int $page Starting at 0
     * @param int $perpage
     * @return array
     */

    public function search_other_users($search='', $searchanywhere=false, $page=0, $perpage=25) {
        global $DB, $CFG;

        list($ufields, $params, $wherecondition) = $this->get_basic_search_conditions($search, $searchanywhere);

        $fields      = 'SELECT ' . $ufields;
        $countfields = 'SELECT COUNT(u.id)';
        $sql   = " FROM {user} u
              LEFT JOIN {role_assignments} ra ON (ra.userid = u.id AND ra.contextid = :contextid)
                  WHERE $wherecondition
                    AND ra.id IS NULL";
        $params['contextid'] = $this->context->id;

        return $this->execute_search_queries($search, $fields, $countfields, $sql, $params, $page, $perpage);
    }

    /**
     * Gets an array containing some SQL to user for when selecting, params for
     * that SQL, and the filter that was used in constructing the sql.
     *
     * @global moodle_database $DB
     * @return string
     */
// ====================================================================================================	
// ====================================================================================================
// ====================================================================================================
// ====================================================================================================
// ====================================================================================================
    protected function get_instance_sql() {
        global $DB;
        if ($this->_instancessql === null) {
            $instances = $this->get_storemultiinfo_instances();
            $filter = $this->get_storemultiinfo_filter();
            if ($filter && array_key_exists($filter, $instances)) {
                $sql = " = :ifilter";
                $params = array('ifilter'=>$filter);
            } else {
                $filter = 0;
                if ($instances) {
                    list($sql, $params) = $DB->get_in_or_equal(array_keys($this->get_storemultiinfo_instances()), SQL_PARAMS_NAMED);
                } else {
                    // no enabled instances, oops, we should probably say something
                    $sql = "= :never";
                    $params = array('never'=>-1);
                }
            }
            $this->instancefilter = $filter;
            $this->_instancessql = array($sql, $params, $filter);
        }
        return $this->_instancessql;
    }

    /**
     * Returns all of the storemultiinfo instances for this course.
     *
     * NOTE: since 2.4 it includes instances of disabled plugins too.
     *
     * @return array
     */

    public function get_storemultiinfo_instances() {
        if ($this->_instances === null) {
            $this->_instances = storinfo_get_instances($this->course->id, false);
        }
        return $this->_instances;
    }

    /**
     * Returns the names for all of the storemultiinfo instances for this course.
     *
     * NOTE: since 2.4 it includes instances of disabled plugins too.
     *
     * @return array
     */

    public function get_storemultiinfo_instance_names() {
        if ($this->_inames === null) {
            $instances = $this->get_storemultiinfo_instances();
            $plugins = $this->get_storemultiinfo_plugins(false);
            foreach ($instances as $key=>$instance) {
                if (!isset($plugins[$instance->storinfo])) {
                    // weird, some broken stuff in plugin
                    unset($instances[$key]);
                    continue;
                }
                $this->_inames[$key] = $plugins[$instance->storinfo]->get_instance_name($instance);
            }
        }
        return $this->_inames;
    }

    /**
     * Gets all of the storemultiinfo plugins that are active for this course.
     *
     * @param bool $onlyenabled return only enabled storinfo plugins
     * @return array
     */

    public function get_storemultiinfo_plugins($onlyenabled = true) {
        if ($this->_plugins === null) {
            $this->_plugins = storinfo_get_plugins(true);
        }

        if ($onlyenabled) {
            return $this->_plugins;
        }

        if ($this->_allplugins === null) {
            // Make sure we have the same objects in _allplugins and _plugins.
            $this->_allplugins = $this->_plugins;
            foreach (storinfo_get_plugins(false) as $name=>$plugin) {
                if (!isset($this->_allplugins[$name])) {
                    $this->_allplugins[$name] = $plugin;
                }
            }
        }

        return $this->_allplugins;
    }

    /**
     * Gets all of the roles this course can contain.
     *
     * @return array
     */

    public function get_all_roles() {
        if ($this->_roles === null) {
            $this->_roles = role_fix_names(get_all_roles($this->context), $this->context);
        }
        return $this->_roles;
    }

    /**
     * Gets all of the assignable roles for this course.
     *
     * @return array
     */

    public function get_assignable_roles($otherusers = false) {
        if ($this->_assignableroles === null) {
            $this->_assignableroles = get_assignable_roles($this->context, ROLENAME_ALIAS, false); // verifies unassign access control too
        }

        if ($otherusers) {
            if (!is_array($this->_assignablerolesothers)) {
                $this->_assignablerolesothers = array();
                list($courseviewroles, $ignored) = get_roles_with_cap_in_context($this->context, 'moodle/course:view');
                foreach ($this->_assignableroles as $roleid=>$role) {
                    if (isset($courseviewroles[$roleid])) {
                        $this->_assignablerolesothers[$roleid] = $role;
                    }
                }
            }
            return $this->_assignablerolesothers;
        } else {
            return $this->_assignableroles;
        }
    }

    /**
     * Gets all of the groups for this course.
     *
     * @return array
     */

    public function get_all_groups() {
        if ($this->_groups === null) {
            $this->_groups = groups_get_all_groups($this->course->id);
            foreach ($this->_groups as $gid=>$group) {
                $this->_groups[$gid]->name = format_string($group->name);
            }
        }
        return $this->_groups;
    }

    /**
     * Unstorinfos a user from the course given the users ue entry
     *
     * @global moodle_database $DB
     * @param stdClass $ue
     * @return bool
     */

    public function unstorinfo_user($ue) {
        global $DB;
        list ($instance, $plugin) = $this->get_user_storemultiinfo_components($ue);
        if ($instance && $plugin && $plugin->allow_unstorinfo_user($instance, $ue) && has_capability("storinfo/$instance->storinfo:unstorinfo", $this->context)) {
            $plugin->unstorinfo_user($instance, $ue->userid);
            return true;
        }
        return false;
    }

    /**
     * Given a user storemultiinfo record this method returns the plugin and storemultiinfo
     * instance that relate to it.
     *
     * @param stdClass|int $userstoremultiinfo
     * @return array array($instance, $plugin)
     */

    public function get_user_storemultiinfo_components($userstoremultiinfo) {
        global $DB;
        if (is_numeric($userstoremultiinfo)) {
            $userstoremultiinfo = $DB->get_record('user_storemultiinfos', array('id'=>(int)$userstoremultiinfo));
        }
        $instances = $this->get_storemultiinfo_instances();
        $plugins = $this->get_storemultiinfo_plugins(false);
        if (!$userstoremultiinfo || !isset($instances[$userstoremultiinfo->storinfoid])) {
            return array(false, false);
        }
        $instance = $instances[$userstoremultiinfo->storinfoid];
        $plugin = $plugins[$instance->storinfo];
        return array($instance, $plugin);
    }

    /**
     * Removes an assigned role from a user.
     *
     * @global moodle_database $DB
     * @param int $userid
     * @param int $roleid
     * @return bool
     */

    public function unassign_role_from_user($userid, $roleid) {
        global $DB;
        // Admins may unassign any role, others only those they could assign.
        if (!is_siteadmin() and !array_key_exists($roleid, $this->get_assignable_roles())) {
            if (defined('AJAX_SCRIPT')) {
                throw new moodle_exception('invalidrole');
            }
            return false;
        }
        $user = $DB->get_record('user', array('id'=>$userid), '*', MUST_EXIST);
        $ras = $DB->get_records('role_assignments', array('contextid'=>$this->context->id, 'userid'=>$user->id, 'roleid'=>$roleid));
        foreach ($ras as $ra) {
            if ($ra->component) {
                if (strpos($ra->component, 'storinfo_') !== 0) {
                    continue;
                }
                if (!$plugin = storinfo_get_plugin(substr($ra->component, 6))) {
                    continue;
                }
                if ($plugin->roles_protected()) {
                    continue;
                }
            }
            role_unassign($ra->roleid, $ra->userid, $ra->contextid, $ra->component, $ra->itemid);
        }
        return true;
    }

    /**
     * Assigns a role to a user.
     *
     * @param int $roleid
     * @param int $userid
     * @return int|false
     */

    public function assign_role_to_user($roleid, $userid) {
        require_capability('moodle/role:assign', $this->context);
        if (!array_key_exists($roleid, $this->get_assignable_roles())) {
            if (defined('AJAX_SCRIPT')) {
                throw new moodle_exception('invalidrole');
            }
            return false;
        }
        return role_assign($roleid, $userid, $this->context->id, '', NULL);
    }

    /**
     * Adds a user to a group
     *
     * @param stdClass $user
     * @param int $groupid
     * @return bool
     */

    public function add_user_to_group($user, $groupid) {
        require_capability('moodle/course:managegroups', $this->context);
        $group = $this->get_group($groupid);
        if (!$group) {
            return false;
        }
        return groups_add_member($group->id, $user->id);
    }

    /**
     * Removes a user from a group
     *
     * @global moodle_database $DB
     * @param StdClass $user
     * @param int $groupid
     * @return bool
     */

    public function remove_user_from_group($user, $groupid) {
        global $DB;
        require_capability('moodle/course:managegroups', $this->context);
        $group = $this->get_group($groupid);
        if (!groups_remove_member_allowed($group, $user)) {
            return false;
        }
        if (!$group) {
            return false;
        }
        return groups_remove_member($group, $user);
    }

    /**
     * Gets the requested group
     *
     * @param int $groupid
     * @return stdClass|int
     */

    public function get_group($groupid) {
        $groups = $this->get_all_groups();
        if (!array_key_exists($groupid, $groups)) {
            return false;
        }
        return $groups[$groupid];
    }

    /**
     * Edits an storemultiinfo
     *
     * @param stdClass $userstoremultiinfo
     * @param stdClass $data
     * @return bool
     */
//============================================================================
//============================================================================
//============================================================================
//============================================================================
    public function edit_storemultiinfo($userstoremultiinfo, $data) {
        //Only allow editing if the user has the appropriate capability
        //Already checked in /storinfo/users.php but checking again in case this function is called from elsewhere
        list($instance, $plugin) = $this->get_user_storemultiinfo_components($userstoremultiinfo);
        if ($instance && $plugin && $plugin->allow_manage($instance) && has_capability("storinfo/$instance->storinfo:manage", $this->context)) {
            if (!isset($data->status)) {
                $data->status = $userstoremultiinfo->status;
            }
            $plugin->update_user_storinfo($instance, $userstoremultiinfo->userid, $data->status, $data->timestart, $data->timeend);
            return true;
        }
        return false;
    }

    /**
     * Returns the current storemultiinfo filter that is being applied by this class
     * @return string
     */
    public function get_storemultiinfo_filter() {
        return $this->instancefilter;
    }

    /**
     * Gets the roles assigned to this user that are applicable for this course.
     *
     * @param int $userid
     * @return array
     */

    public function get_user_roles($userid) {
        $roles = array();
        $ras = get_user_roles($this->context, $userid, true, 'c.contextlevel DESC, r.sortorder ASC');
        $plugins = $this->get_storemultiinfo_plugins(false);
        foreach ($ras as $ra) {
            if ($ra->contextid != $this->context->id) {
                if (!array_key_exists($ra->roleid, $roles)) {
                    $roles[$ra->roleid] = null;
                }
                // higher ras, course always takes precedence
                continue;
            }
            if (array_key_exists($ra->roleid, $roles) && $roles[$ra->roleid] === false) {
                continue;
            }
            $changeable = true;
            if ($ra->component) {
                $changeable = false;
                if (strpos($ra->component, 'storinfo_') === 0) {
                    $plugin = substr($ra->component, 6);
                    if (isset($plugins[$plugin])) {
                        $changeable = !$plugins[$plugin]->roles_protected();
                    }
                }
            }

            $roles[$ra->roleid] = $changeable;
        }
        return $roles;
    }

    /**
     * Gets the storemultiinfos this user has in the course - including all suspended plugins and instances.
     *
     * @global moodle_database $DB
     * @param int $userid
     * @return array
     */

    public function get_user_storemultiinfos($userid) {
        global $DB;
        list($instancessql, $params, $filter) = $this->get_instance_sql();
        $params['userid'] = $userid;
        $userstoremultiinfos = $DB->get_records_select('user_storemultiinfos', "storinfoid $instancessql AND userid = :userid", $params);
        $instances = $this->get_storemultiinfo_instances();
        $plugins = $this->get_storemultiinfo_plugins(false);
        $inames = $this->get_storemultiinfo_instance_names();
        foreach ($userstoremultiinfos as &$ue) {
            $ue->storemultiinfoinstance     = $instances[$ue->storinfoid];
            $ue->storemultiinfoplugin       = $plugins[$ue->storemultiinfoinstance->storinfo];
            $ue->storemultiinfoinstancename = $inames[$ue->storemultiinfoinstance->id];
        }
        return $userstoremultiinfos;
    }

    /**
     * Gets the groups this user belongs to
     *
     * @param int $userid
     * @return array
     */

    public function get_user_groups($userid) {
        return groups_get_all_groups($this->course->id, $userid, 0, 'g.id');
    }

    /**
     * Retursn an array of params that would go into the URL to return to this
     * exact page.
     *
     * @return array
     */
//============================================================================
//============================================================================
//============================================================================
//============================================================================
    public function get_url_params() {
        $args = array(
            'id' => $this->course->id
        );
        if (!empty($this->instancefilter)) {
            $args['ifilter'] = $this->instancefilter;
        }
        if (!empty($this->rolefilter)) {
            $args['role'] = $this->rolefilter;
        }
        if ($this->searchfilter !== '') {
            $args['search'] = $this->searchfilter;
        }
        if (!empty($this->groupfilter)) {
            $args['group'] = $this->groupfilter;
        }
        if ($this->statusfilter !== -1) {
            $args['status'] = $this->statusfilter;
        }
        return $args;
    }

    /**
     * Returns the course this object is managing storemultiinfos for
     *
     * @return stdClass
     */

    public function get_course() {
        return $this->course;
    }

    /**
     * Returns the course context
     *
     * @return stdClass
     */
//============================================================================
//============================================================================
//============================================================================
//============================================================================
    public function get_context() {
        return $this->context;
    }

    /**
     * Gets an array of other users in this course ready for display.
     *
     * Other users are users who have been assigned or inherited roles within this
     * course but have not been storinfoled.
     *
     * @param core_storinfo_renderer $renderer
     * @param moodle_url $pageurl
     * @param string $sort
     * @param string $direction ASC | DESC
     * @param int $page Starting from 0
     * @param int $perpage
     * @return array
     */

    public function get_other_users_for_display(core_storinfo_renderer $renderer, moodle_url $pageurl, $sort, $direction, $page, $perpage) {

        $userroles = $this->get_other_users($sort, $direction, $page, $perpage);
        $roles = $this->get_all_roles();
        $plugins = $this->get_storemultiinfo_plugins(false);

        $context    = $this->get_context();
        $now = time();
        $extrafields = get_extra_user_fields($context);

        $users = array();
        foreach ($userroles as $userrole) {
            $contextid = $userrole->contextid;
            unset($userrole->contextid); // This would collide with user avatar.
            if (!array_key_exists($userrole->id, $users)) {
                $users[$userrole->id] = $this->prepare_user_for_display($userrole, $extrafields, $now);
            }
            $a = new stdClass;
            $a->role = $roles[$userrole->roleid]->localname;
            if ($contextid == $this->context->id) {
                $changeable = true;
                if ($userrole->component) {
                    $changeable = false;
                    if (strpos($userrole->component, 'storinfo_') === 0) {
                        $plugin = substr($userrole->component, 6);
                        if (isset($plugins[$plugin])) {
                            $changeable = !$plugins[$plugin]->roles_protected();
                        }
                    }
                }
                $roletext = get_string('rolefromthiscourse', 'storinfo', $a);
            } else {
                $changeable = false;
                switch ($userrole->contextlevel) {
                    case CONTEXT_COURSE :
                        // Meta course
                        $roletext = get_string('rolefrommetacourse', 'storinfo', $a);
                        break;
                    case CONTEXT_COURSECAT :
                        $roletext = get_string('rolefromcategory', 'storinfo', $a);
                        break;
                    case CONTEXT_SYSTEM:
                    default:
                        $roletext = get_string('rolefromsystem', 'storinfo', $a);
                        break;
                }
            }
            if (!isset($users[$userrole->id]['roles'])) {
                $users[$userrole->id]['roles'] = array();
            }
            $users[$userrole->id]['roles'][$userrole->roleid] = array(
                'text' => $roletext,
                'unchangeable' => !$changeable
            );
        }
        return $users;
    }

    /**
     * Gets an array of users for display, this includes minimal user information
     * as well as minimal information on the users roles, groups, and storemultiinfos.
     *
     * @param core_storinfo_renderer $renderer
     * @param moodle_url $pageurl
     * @param int $sort
     * @param string $direction ASC or DESC
     * @param int $page
     * @param int $perpage
     * @return array
     */

    public function get_users_for_display(course_storemultiinfo_manager $manager, $sort, $direction, $page, $perpage) {
        $pageurl = $manager->get_moodlepage()->url;
        $users = $this->get_users($sort, $direction, $page, $perpage);

        $now = time();
        $straddgroup = get_string('addgroup', 'group');
        $strunstorinfo = get_string('unstorinfo', 'storinfo');
        $stredit = get_string('edit');

        $allroles   = $this->get_all_roles();
        $assignable = $this->get_assignable_roles();
        $allgroups  = $this->get_all_groups();
        $context    = $this->get_context();
        $canmanagegroups = has_capability('moodle/course:managegroups', $context);

        $url = new moodle_url($pageurl, $this->get_url_params());
        $extrafields = get_extra_user_fields($context);

        $enabledplugins = $this->get_storemultiinfo_plugins(true);

        $userdetails = array();
        foreach ($users as $user) {
            $details = $this->prepare_user_for_display($user, $extrafields, $now);

            // Roles
            $details['roles'] = array();
            foreach ($this->get_user_roles($user->id) as $rid=>$rassignable) {
                $unchangeable = !$rassignable;
                if (!is_siteadmin() and !isset($assignable[$rid])) {
                    $unchangeable = true;
                }
                $details['roles'][$rid] = array('text'=>$allroles[$rid]->localname, 'unchangeable'=>$unchangeable);
            }

            // Users
            $usergroups = $this->get_user_groups($user->id);
            $details['groups'] = array();
            foreach($usergroups as $gid=>$unused) {
                $details['groups'][$gid] = $allgroups[$gid]->name;
            }

            // storemultiinfos
            $details['storemultiinfos'] = array();
            foreach ($this->get_user_storemultiinfos($user->id) as $ue) {
                if (!isset($enabledplugins[$ue->storemultiinfoinstance->storinfo])) {
                    $details['storemultiinfos'][$ue->id] = array(
                        'text' => $ue->storemultiinfoinstancename,
                        'period' => null,
                        'dimmed' =>  true,
                        'actions' => array()
                    );
                    continue;
                } else if ($ue->timestart and $ue->timeend) {
                    $period = get_string('periodstartend', 'storinfo', array('start'=>userdate($ue->timestart), 'end'=>userdate($ue->timeend)));
                    $periodoutside = ($ue->timestart && $ue->timeend && ($now < $ue->timestart || $now > $ue->timeend));
                } else if ($ue->timestart) {
                    $period = get_string('periodstart', 'storinfo', userdate($ue->timestart));
                    $periodoutside = ($ue->timestart && $now < $ue->timestart);
                } else if ($ue->timeend) {
                    $period = get_string('periodend', 'storinfo', userdate($ue->timeend));
                    $periodoutside = ($ue->timeend && $now > $ue->timeend);
                } else {
                    // If there is no start or end show when user was storinfoled.
                    $period = get_string('periodnone', 'storinfo', userdate($ue->timecreated));
                    $periodoutside = false;
                }
                $details['storemultiinfos'][$ue->id] = array(
                    'text' => $ue->storemultiinfoinstancename,
                    'period' => $period,
                    'dimmed' =>  ($periodoutside or $ue->status != storinfo_USER_ACTIVE or $ue->storemultiinfoinstance->status != storinfo_INSTANCE_ENABLED),
                    'actions' => $ue->storemultiinfoplugin->get_user_storemultiinfo_actions($manager, $ue)
                );
            }
            $userdetails[$user->id] = $details;
        }
        return $userdetails;
    }

    /**
     * Prepare a user record for display
     *
     * This function is called by both {@link get_users_for_display} and {@link get_other_users_for_display} to correctly
     * prepare user fields for display
     *
     * Please note that this function does not check capability for moodle/coures:viewhiddenuserfields
     *
     * @param object $user The user record
     * @param array $extrafields The list of fields as returned from get_extra_user_fields used to determine which
     * additional fields may be displayed
     * @param int $now The time used for lastaccess calculation
     * @return array The fields to be displayed including userid, courseid, picture, firstname, lastseen and any
     * additional fields from $extrafields
     */

    private function prepare_user_for_display($user, $extrafields, $now) {
        $details = array(
            'userid'           => $user->id,
            'courseid'         => $this->get_course()->id,
            'picture'          => new user_picture($user),
            'firstname'        => fullname($user, has_capability('moodle/site:viewfullnames', $this->get_context())),
            'lastseen'         => get_string('never'),
            'lastcourseaccess' => get_string('never'),
        );
        foreach ($extrafields as $field) {
            $details[$field] = $user->{$field};
        }

        // Last time user has accessed the site.
        if ($user->lastaccess) {
            $details['lastseen'] = format_time($now - $user->lastaccess);
        }

        // Last time user has accessed the course.
        if ($user->lastseen) {
            $details['lastcourseaccess'] = format_time($now - $user->lastseen);
        }
        return $details;
    }

    public function get_manual_storinfo_buttons() {
        $plugins = $this->get_storemultiinfo_plugins(true); // Skip disabled plugins.
        $buttons = array();
        foreach ($plugins as $plugin) {
            $newbutton = $plugin->get_manual_storinfo_button($this);
            if (is_array($newbutton)) {
                $buttons += $newbutton;
            } else if ($newbutton instanceof storinfo_user_button) {
                $buttons[] = $newbutton;
            }
        }
        return $buttons;
    }

    public function has_instance($storinfopluginname) {
        // Make sure manual storemultiinfos instance exists
        foreach ($this->get_storemultiinfo_instances() as $instance) {
            if ($instance->storinfo == $storinfopluginname) {
                return true;
            }
        }
        return false;
    }

//============================================================================
//============================================================================
//============================================================================
//============================================================================
    /**
     * Returns the storemultiinfo plugin that the course manager was being filtered to.
     *
     * If no filter was being applied then this function returns false.
     *
     * @return storinfo_plugin
     */
    public function get_filtered_storemultiinfo_plugin() {
        $instances = $this->get_storemultiinfo_instances();
        $plugins = $this->get_storemultiinfo_plugins(false);

        if (empty($this->instancefilter) || !array_key_exists($this->instancefilter, $instances)) {
            return false;
        }

        $instance = $instances[$this->instancefilter];
        return $plugins[$instance->storinfo];
    }

    /**
     * Returns and array of users + storemultiinfo details.
     *
     * Given an array of user id's this function returns and array of user storemultiinfos for those users
     * as well as enough user information to display the users name and picture for each storemultiinfo.
     *
     * @global moodle_database $DB
     * @param array $userids
     * @return array
     */

    public function get_users_storemultiinfos(array $userids) {
        global $DB;

        $instances = $this->get_storemultiinfo_instances();
        $plugins = $this->get_storemultiinfo_plugins(false);

        if  (!empty($this->instancefilter)) {
            $instancesql = ' = :instanceid';
            $instanceparams = array('instanceid' => $this->instancefilter);
        } else {
            list($instancesql, $instanceparams) = $DB->get_in_or_equal(array_keys($instances), SQL_PARAMS_NAMED, 'instanceid0000');
        }

        $userfields = user_picture::fields('u');
        list($idsql, $idparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'userid0000');

        list($sort, $sortparams) = users_order_by_sql('u');

        $sql = "SELECT ue.id AS ueid, ue.status, ue.storinfoid, ue.userid, ue.timestart, ue.timeend, ue.modifierid, ue.timecreated, ue.timemodified, $userfields
                  FROM {user_storemultiinfos} ue
             LEFT JOIN {user} u ON u.id = ue.userid
                 WHERE ue.storinfoid $instancesql AND
                       u.id $idsql
              ORDER BY $sort";

        $rs = $DB->get_recordset_sql($sql, $idparams + $instanceparams + $sortparams);
        $users = array();
        foreach ($rs as $ue) {
            $user = user_picture::unalias($ue);
            $ue->id = $ue->ueid;
            unset($ue->ueid);
            if (!array_key_exists($user->id, $users)) {
                $user->storemultiinfos = array();
                $users[$user->id] = $user;
            }
            $ue->storemultiinfoinstance = $instances[$ue->storinfoid];
            $ue->storemultiinfoplugin = $plugins[$ue->storemultiinfoinstance->storinfo];
            $users[$user->id]->storemultiinfos[$ue->id] = $ue;
        }
        $rs->close();
        return $users;
    }
}

/**
 * A button that is used to storinfo users in a course
 *
 * @copyright 2010 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//============================================================================
//============================================================================
//============================================================================
//============================================================================
class storinfo_user_button extends single_button {

    /**
     * An array containing JS YUI modules required by this button
     * @var array
     */
    protected $jsyuimodules = array();

    /**
     * An array containing JS initialisation calls required by this button
     * @var array
     */
    protected $jsinitcalls = array();

    /**
     * An array strings required by JS for this button
     * @var array
     */
    protected $jsstrings = array();

    /**
     * Initialises the new storinfo_user_button
     *
     * @staticvar int $count The number of storinfo user buttons already created
     * @param moodle_url $url
     * @param string $label The text to display in the button
     * @param string $method Either post or get
     */
    public function __construct(moodle_url $url, $label, $method = 'post') {
        static $count = 0;
        $count ++;
        parent::__construct($url, $label, $method);
        $this->class = 'singlebutton storinfousersbutton';
        $this->formid = 'storinfousersbutton-'.$count;
    }

    /**
     * Adds a YUI module call that will be added to the page when the button is used.
     *
     * @param string|array $modules One or more modules to require
     * @param string $function The JS function to call
     * @param array $arguments An array of arguments to pass to the function
     * @param string $galleryversion Deprecated: The gallery version to use
     * @param bool $ondomready If true the call is postponed until the DOM is finished loading
     */
//============================================================================
//============================================================================
//============================================================================
//============================================================================
    public function require_yui_module($modules, $function, array $arguments = null, $galleryversion = null, $ondomready = false) {
        if ($galleryversion != null) {
            debugging('The galleryversion parameter to yui_module has been deprecated since Moodle 2.3.', DEBUG_DEVELOPER);
        }

        $js = new stdClass;
        $js->modules = (array)$modules;
        $js->function = $function;
        $js->arguments = $arguments;
        $js->ondomready = $ondomready;
        $this->jsyuimodules[] = $js;
    }

    /**
     * Adds a JS initialisation call to the page when the button is used.
     *
     * @param string $function The function to call
     * @param array $extraarguments An array of arguments to pass to the function
     * @param bool $ondomready If true the call is postponed until the DOM is finished loading
     * @param array $module A module definition
     */
    public function require_js_init_call($function, array $extraarguments = null, $ondomready = false, array $module = null) {
        $js = new stdClass;
        $js->function = $function;
        $js->extraarguments = $extraarguments;
        $js->ondomready = $ondomready;
        $js->module = $module;
        $this->jsinitcalls[] = $js;
    }

    /**
     * Requires strings for JS that will be loaded when the button is used.
     *
     * @param type $identifiers
     * @param string $component
     * @param mixed $a
     */
    public function strings_for_js($identifiers, $component = 'moodle', $a = null) {
        $string = new stdClass;
        $string->identifiers = (array)$identifiers;
        $string->component = $component;
        $string->a = $a;
        $this->jsstrings[] = $string;
    }

    /**
     * Initialises the JS that is required by this button
     *
     * @param moodle_page $page
     */
    public function initialise_js(moodle_page $page) {
        foreach ($this->jsyuimodules as $js) {
            $page->requires->yui_module($js->modules, $js->function, $js->arguments, null, $js->ondomready);
        }
        foreach ($this->jsinitcalls as $js) {
            $page->requires->js_init_call($js->function, $js->extraarguments, $js->ondomready, $js->module);
        }
        foreach ($this->jsstrings as $string) {
            $page->requires->strings_for_js($string->identifiers, $string->component, $string->a);
        }
    }
}

/**
 * User storemultiinfo action
 *
 * This class is used to manage a renderable ue action such as editing an user storemultiinfo or deleting
 * a user storemultiinfo.
 *
 * @copyright  2011 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_storemultiinfo_action implements renderable {

    /**
     * The icon to display for the action
     * @var pix_icon
     */
    protected $icon;

    /**
     * The title for the action
     * @var string
     */
    protected $title;

    /**
     * The URL to the action
     * @var moodle_url
     */
    protected $url;

    /**
     * An array of HTML attributes
     * @var array
     */
    protected $attributes = array();

    /**
     * Constructor
     * @param pix_icon $icon
     * @param string $title
     * @param moodle_url $url
     * @param array $attributes
     */
    public function __construct(pix_icon $icon, $title, $url, array $attributes = null) {
        $this->icon = $icon;
        $this->title = $title;
        $this->url = new moodle_url($url);
        if (!empty($attributes)) {
            $this->attributes = $attributes;
        }
        $this->attributes['title'] = $title;
    }

    /**
     * Returns the icon for this action
     * @return pix_icon
     */
    public function get_icon() {
        return $this->icon;
    }

    /**
     * Returns the title for this action
     * @return string
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * Returns the URL for this action
     * @return moodle_url
     */
    public function get_url() {
        return $this->url;
    }

    /**
     * Returns the attributes to use for this action
     * @return array
     */
    public function get_attributes() {
        return $this->attributes;
    }
}

class storinfo_ajax_exception extends moodle_exception {
    /**
     * Constructor
     * @param string $errorcode The name of the string from error.php to print
     * @param string $module name of module
     * @param string $link The url where the user will be prompted to continue. If no url is provided the user will be directed to the site index page.
     * @param object $a Extra words and phrases that might be required in the error string
     * @param string $debuginfo optional debugging information
     */
    public function __construct($errorcode, $link = '', $a = NULL, $debuginfo = null) {
        parent::__construct($errorcode, 'storinfo', $link, $a, $debuginfo);
    }
}

/**
 * This class is used to manage a bulk operations for storemultiinfo plugins.
 *
 * @copyright 2011 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class storinfo_bulk_storemultiinfo_operation {

    /**
     * The course storemultiinfo manager
     * @var course_storemultiinfo_manager
     */
    protected $manager;

    /**
     * The storemultiinfo plugin to which this operation belongs
     * @var storinfo_plugin
     */
    protected $plugin;

    /**
     * Contructor
     * @param course_storemultiinfo_manager $manager
     * @param stdClass $plugin
     */
    public function __construct(course_storemultiinfo_manager $manager, storinfo_plugin $plugin = null) {
        $this->manager = $manager;
        $this->plugin = $plugin;
    }

    /**
     * Returns a moodleform used for this operation, or false if no form is required and the action
     * should be immediatly processed.
     *
     * @param moodle_url|string $defaultaction
     * @param mixed $defaultcustomdata
     * @return storinfo_bulk_storemultiinfo_change_form|moodleform|false
     */
    public function get_form($defaultaction = null, $defaultcustomdata = null) {
        return false;
    }

    /**
     * Returns the title to use for this bulk operation
     *
     * @return string
     */
    abstract public function get_title();

    /**
     * Returns the identifier for this bulk operation.
     * This should be the same identifier used by the plugins function when returning
     * all of its bulk operations.
     *
     * @return string
     */
    abstract public function get_identifier();

    /**
     * Processes the bulk operation on the given users
     *
     * @param course_storemultiinfo_manager $manager
     * @param array $users
     * @param stdClass $properties
     */
    abstract public function process(course_storemultiinfo_manager $manager, array $users, stdClass $properties);
}
