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
 * User storemultiinfo created event.
 *
 * @package    core
 * @copyright  2013 Rajesh Taneja <rajesh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event class for when user is storinfoled in a course.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - string storinfo: name of storemultiinfo instance.
 * }
 *
 * @package    core
 * @since      Moodle 2.6
 * @copyright  2013 Rajesh Taneja <rajesh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_storemultiinfo_created extends base {

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        //$this->data['objecttable'] = 'user_storemultiinfos';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventuserstoremultiinfocreated', 'core_storinfo');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' storinfoled the user with id '$this->relateduserid' using the storemultiinfo method " .
            "'{$this->other['storinfo']}' in the course with id '$this->courseid'.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/storinfo/users.php', array('id' => $this->courseid));
    }

    /**
     * Return name of the legacy event, which is replaced by this event.
     *
     * @return string legacy event name
     */
    public static function get_legacy_eventname() {
        return 'user_storinfoled';
    }

    /**
     * Return user_storinfoled legacy event data.
     *
     * @return \stdClass
     */
    protected function get_legacy_eventdata() {
        //$legacyeventdata = $this->get_record_snapshot('user_storemultiinfos', $this->objectid);
        $legacyeventdata->storinfo = $this->other['storinfo'];
        $legacyeventdata->courseid = $this->courseid;
        return $legacyeventdata;
    }

    /**
     * Return legacy data for add_to_log().
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        return array($this->courseid, 'course', 'storinfo', '../storinfo/users.php?id=' . $this->courseid, $this->courseid);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }

        if (!isset($this->other['storinfo'])) {
            throw new \coding_exception('The \'storinfo\' value must be set in other.');
        }
    }
}
