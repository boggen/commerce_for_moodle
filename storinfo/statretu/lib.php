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
 * statretu storemultiinfo plugin.
 *
 * This plugin lets the user specify a "statretu" (CSV) containing storemultiinfo information.
 * On a regular cron cycle, the specified file is parsed and then deleted.
 *
 * @package    storinfo_statretu
 * @copyright  2010 Eugene Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * statretu storemultiinfo plugin implementation.
 *
 * Comma separated file assumed to have four or six fields per line:
 *   operation, role, idnumber(user), idnumber(course) [, starttime [, endtime]]
 * where:
 *   operation        = add | del
 *   role             = student | teacher | teacheredit
 *   idnumber(user)   = idnumber in the user table NB not id
 *   idnumber(course) = idnumber in the course table NB not id
 *   starttime        = start time (in seconds since epoch) - optional
 *   endtime          = end time (in seconds since epoch) - optional
 *
 * @author  Eugene Venter - based on code by Petr Skoda, Martin Dougiamas, Martin Langhoff and others
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class storinfo_statretu_plugin extends storinfo_plugin {
    protected $lasternoller = null;
    protected $lasternollercourseid = 0;

    /**
     * Does this plugin assign protected roles are can they be manually removed?
     * @return bool - false means anybody may tweak roles, it does not use itemid and component when assigning roles
     */
    public function roles_protected() {
        return false;
    }






    /**
     * Is it possible to delete storinfo instance via standard UI?
     *
     * @param object $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('storinfo/statretu:manage', $context);
    }

    /**
     * Is it possible to hide/show storinfo instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('storinfo/statretu:manage', $context);
    }

 



    /**
     * Returns the user who is responsible for statretu storemultiinfos in given curse.
     *
     * Usually it is the first editing teacher - the person with "highest authority"
     * as defined by sort_by_roleassignment_authority() having 'storinfo/statretu:manage'
     * or 'moodle/role:assign' capability.
     *
     * @param int $courseid storemultiinfo instance id
     * @return stdClass user record
     */
    protected function get_storinfoler($courseid) {
        if ($this->lasternollercourseid == $courseid and $this->lasternoller) {
            return $this->lasternoller;
        }

        $context = context_course::instance($courseid);

        $users = get_storinfoled_users($context, 'storinfo/statretu:manage');
        if (!$users) {
            $users = get_storinfoled_users($context, 'moodle/role:assign');
        }

        if ($users) {
            $users = sort_by_roleassignment_authority($users, $context);
            $this->lasternoller = reset($users);
            unset($users);
        } else {
            $this->lasternoller = get_admin();
        }

        $this->lasternollercourseid == $courseid;

        return $this->lasternoller;
    }

    /**
     * Returns a mapping of ims roles to role ids.
     *
     * @param progress_trace $trace
     * @return array imsrolename=>roleid
     */
    protected function get_role_map(progress_trace $trace) {
        global $DB;

        // Get all roles.
        $rolemap = array();
        $roles = $DB->get_records('role', null, '', 'id, name, shortname');
        foreach ($roles as $id=>$role) {
            $alias = $this->get_config('map_'.$id, $role->shortname, '');
            $alias = trim(core_text::strtolower($alias));
            if ($alias === '') {
                // Either not configured yet or somebody wants to skip these intentionally.
                continue;
            }
            if (isset($rolemap[$alias])) {
                $trace->output("Duplicate role alias $alias detected!");
            } else {
                $rolemap[$alias] = $id;
            }
        }

        return $rolemap;
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
        global $DB;

        if ($instance = $DB->get_record('storinfo', array('courseid'=>$course->id, 'storinfo'=>$this->get_name()))) {
            $instanceid = $instance->id;
        } else {
            $instanceid = $this->add_instance($course);
        }
        $step->set_mapping('storinfo', $oldid, $instanceid);
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
        $this->storinfo_user($instance, $userid, null, $data->timestart, $data->timeend, $data->status);
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
        role_assign($roleid, $userid, $contextid, 'storinfo_'.$instance->storinfo, $instance->id);
    }
}
