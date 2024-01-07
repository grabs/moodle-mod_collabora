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
 * Global settings
 *
 * @package   mod_collabora
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_collabora\util;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings->add(
        new admin_setting_heading(
            'mod_collabora_general_hdr',
            get_string('general'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'mod_collabora/url',
            new lang_string('collaboraurl', 'mod_collabora'),
            '',
            '',
            PARAM_URL
        )
    );

    $settings->add(
        new admin_setting_configselect(
            'mod_collabora/defaultformat',
            new lang_string('defaultformat', 'mod_collabora'),
            '',
            util::FORMAT_WORDPROCESSOR,
            util::format_menu()
        )
    );

    $settings->add(
        new admin_setting_configselect(
            'mod_collabora/defaultdisplay',
            new lang_string('defaultdisplay', 'mod_collabora'),
            '',
            util::DISPLAY_CURRENT,
            util::display_menu()
        )
    );

    $yesno = [
        1 => new lang_string('yes'),
        0 => new lang_string('no')
    ];
    $settings->add(
        new admin_setting_configselect(
            'mod_collabora/defaultdisplayname',
            new lang_string('defaultdisplayname', 'mod_collabora'),
            '',
            1,
            $yesno
        )
    );

    $settings->add(
        new admin_setting_configselect(
            'mod_collabora/defaultdisplaydescription',
            new lang_string('defaultdisplaydescription', 'mod_collabora'),
            '',
            1,
            $yesno
        )
    );

    $modlist = [
        util::UI_SERVER => get_string('uiserver', 'mod_collabora'),
        util::UI_COMPACT => get_string('uicompact', 'mod_collabora'),
        util::UI_TABBED => get_string('uitabbed', 'mod_collabora'),
    ];
    $settings->add(
        new admin_setting_configselect(
            'mod_collabora/uimode',
            new lang_string('uimode', 'mod_collabora'),
            '',
            util::UI_SERVER,
            $modlist
        )
    );

    $settings->add(
        new admin_setting_configcheckbox(
            'mod_collabora/enableversions',
            new lang_string('enableversions', 'mod_collabora'),
            new lang_string('enableversions_help', 'mod_collabora'),
            true
        )
    );

    $settings->add(
        new admin_setting_heading(
            'mod_collabora_security_hdr',
            get_string('setting_header_security', 'mod_collabora'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configselect(
            'mod_collabora/allowcollaboraserverexplicit',
            new lang_string('setting_allowcollaboraserverexplicit', 'mod_collabora'),
            new lang_string('setting_allowcollaboraserverexplicit_help', 'mod_collabora'),
            0,
            $yesno
        )
    );
}
