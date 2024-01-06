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

namespace mod_collabora\external;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_collabora\util;

/**
 * Main support functions
 *
 * @package   mod_collabora
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class services extends external_api {
    /**
     * @return external_function_parameters
     */
    public static function restore_version_parameters() {
        return new external_function_parameters(
            [
                'id'      => new external_value(PARAM_INT, 'The collabora id'),
                'version' => new external_value(PARAM_INT, 'The version to be restored'),
                'userid' => new external_value(PARAM_INT, 'The userid', VALUE_DEFAULT, 0),
            ]
        );
    }

    /**
     * Process version restore.
     *
     * @param  int $id
     * @param  int $version
     * @param  int $userid
     * @return array
     */
    public static function restore_version($id, $version, $userid) {
        global $DB, $USER;

        // We always must pass webservice params through validate_parameters.
        [
            'id' => $id,
            'version' => $version,
            'userid' => $userid,
        ] = self::validate_parameters(self::restore_version_parameters(), [
            'id' => $id,
            'version' => $version,
            'userid' => $userid,
        ]);

        if (!empty($userid)) {
            $user = $DB->get_record('user', ['id' => $userid]);
        } else {
            $user = $USER;
        }

        [$course, $cm] = get_course_and_cm_from_instance($id, 'collabora');

        // We always must call validate_context in a webservice.
        self::validate_context($cm->context);
        require_capability('mod/collabora:manageversions', $cm->context, $user->id);

        $collabora = $DB->get_record('collabora', ['id' => $id]);
        $groupid = util::get_current_groupid_from_cm($cm, $user);
        $collaborafs = new \mod_collabora\api\collabora_fs($collabora, $cm->context, $groupid, $user->id);
        if ($collaborafs->restore_version($version)) {
            $return = [
                'success' => 1,
                'failure' => 0,
                'failuremsg' => '',
            ];
        } else {
            $return = [
                'success' => 0,
                'failure' => 1,
                'failuremsg' => get_string('couldnotrestoreversion', 'mod_collabora'),
            ];
        }


        return $return;
    }

    /**
     * @return external_description
     */
    public static function restore_version_returns() {
        return new external_single_structure(
            [
                'success'    => new external_value(PARAM_INT, '1 on success'),
                'failure'    => new external_value(PARAM_INT, '1 on failure'),
                'failuremsg' => new external_value(PARAM_TEXT, 'Message on failure'),
            ]
        );
    }

}
