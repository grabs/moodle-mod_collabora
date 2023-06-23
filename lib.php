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
 * API functions
 *
 * @package   mod_collabora
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/mod_form.php');

/**
 * Checks whether or not a feature is supported
 *
 * @param string $feature
 * @return boolean
 */
function collabora_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_COLLABORATION;
        default:
            return false;
    }
}

/**
 * Create a new collabora instance.
 *
 * @param \stdClass $collabora
 * @param mod_collabora_mod_form $mform
 * @return int
 */
function collabora_add_instance($collabora, $mform) {
    global $DB;

    $collabora->timecreated = time();
    $collabora->timemodified = time();

    $collabora->id = $DB->insert_record('collabora', $collabora);

    $completiontimeexpected = !empty($collabora->completionexpected) ? $collabora->completionexpected : null;
    \core_completion\api::update_completion_date_event($collabora->coursemodule, 'collabora',
                                                       $collabora->id, $completiontimeexpected);

    // Save the 'initial file'.
    $context = context_module::instance($collabora->coursemodule);
    file_postupdate_standard_filemanager($collabora, 'initialfile', mod_collabora_mod_form::get_filemanager_opts(),
                                         $context, 'mod_collabora', \mod_collabora\api\collabora_fs::FILEAREA_INITIAL, 0);

    return $collabora->id;

}

/**
 * Update a collabora instance.
 *
 * @param \stdClass $collabora
 * @return boolean
 */
function collabora_update_instance($collabora) {
    global $DB;

    $collabora->id = $collabora->instance;
    $collabora->timemodified = time();

    $DB->update_record('collabora', $collabora);

    $completiontimeexpected = !empty($collabora->completionexpected) ? $collabora->completionexpected : null;
    \core_completion\api::update_completion_date_event($collabora->coursemodule, 'collabora',
                                                       $collabora->id, $completiontimeexpected);

    // Do not save the 'initial file' here, as you cannot change this after the activity has been created.

    return true;
}

/**
 * Delete an existing collabora instance
 *
 * @param int $id
 * @return boolean
 */
function collabora_delete_instance($id) {
    global $DB;

    if (!$collabora = $DB->get_record('collabora', ['id' => $id])) {
        return false;
    }

    $cm = get_coursemodule_from_instance('collabora', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'collabora', $id, null);

    $DB->delete_records('collabora_document', ['collaboraid' => $collabora->id]);
    $DB->delete_records('collabora', ['id' => $collabora->id]);

    return true;
}

/**
 * Register the ability to handle drag and drop file uploads
 *
 * @return array containing details of the files / types the mod can handle
 */
function collabora_dndupload_register() {
    global $DB;

    // Prevent using this hook while disabled.
    if (!$DB->get_field('modules', 'visible', array('name' => 'collabora'))) {
        return false;
    }

    $extensions = \mod_collabora\api\collabora_fs::get_accepted_types();
    $strdnd = get_string('dnduploadcollabora', 'mod_collabora');
    $files = array();
    foreach ($extensions as $extn) {
        $extn = trim($extn, '.');
        $files[] = array('extension' => $extn, 'message' => $strdnd);
    }
    return array('files' => $files);
}

/**
 * Handle a file that has been uploaded
 *
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function collabora_dndupload_handle($uploadinfo) {
    global $DB;

    // Prevent using this hook while disabled.
    if (!$DB->get_field('modules', 'visible', array('name' => 'collabora'))) {
        return false;
    }

    // Gather the required info.
    $data = new stdClass();
    $data->course = $uploadinfo->course->id;
    $data->name = $uploadinfo->displayname;
    $data->intro = '';
    $data->introformat = FORMAT_HTML;
    $data->coursemodule = $uploadinfo->coursemodule;
    $data->initialfile_filemanager = $uploadinfo->draftitemid;
    $data->format = \mod_collabora\api\collabora_fs::FORMAT_UPLOAD;

    // Set the display options to the site defaults.
    $config = get_config('mod_collabora');
    $data->display = $config->defaultdisplay;
    $data->width = 0;
    $data->height = 0;
    $data->displayname = $config->defaultdisplayname;
    $data->displaydescription = $config->defaultdisplaydescription;

    return collabora_add_instance($data, null);
}

/**
 * Get a coursemodule info object with infos about its presentation on the course page.
 *
 * @param \stdClass $coursemodule
 * @return \cached_cm_info
 */
function collabora_get_coursemodule_info($coursemodule) {
    global $DB, $USER;
    if (!$collabora = $DB->get_record('collabora', ['id' => $coursemodule->instance])) {
        return null;
    }

    $info = new cached_cm_info();
    if ($collabora->display === \mod_collabora\api\collabora_fs::DISPLAY_NEW) {
        // Use javascript to open the link in a new tab.
        $url = new moodle_url('/mod/collabora/view.php', ['id' => $coursemodule->id]);
        $info->onclick = "event.preventDefault();window.open('".$url->out(false)."', '_blank').focus();";
    }
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('collabora', $collabora, $coursemodule->id, false);
    }

    $collaborafs = new \mod_collabora\api\collabora_fs($collabora, context_module::instance($coursemodule->id), 0, $USER->id);
    if ($specificicon = $collaborafs->get_module_icon()) {
        $info->icon = 'mod/collabora/' . $specificicon;
        $info->customdata['filtericon'] = 1; // Apply the monologo filter to the icon.
    }
    return $info;
}

/**
 * Send a stored_file to the browser
 *
 * @param \stdClass|int $course
 * @param \stdClass|null $cm
 * @param \context $context
 * @param string $filearea
 * @param array $args
 * @param boolean $forcedownload
 * @param array $options
 * @return void
 */
function collabora_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        return;
    }
    require_login($course, false, $cm);
    // File link only occurs on the edit settings page, so restrict access to teachers.
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    if ($filearea !== \mod_collabora\api\collabora_fs::FILEAREA_INITIAL) {
        return;
    }

    $itemid = (int)array_shift($args);
    if ($itemid !== 0) {
        return;
    }

    $filename = array_pop($args);
    $filepath = '/'.implode('/', $args);
    if ($filepath !== '/') {
        $filepath .= '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_collabora', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return;
    }

    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settingsnav The settings navigation object
 * @param navigation_node $collaboranode The node to add module settings to
 * @return void
 */
function collabora_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $collaboranode) {
    global $USER, $PAGE, $CFG;

    if (empty($PAGE->cm->context)) {
        return;
    }

    if (has_capability('mod/collabora:repair', $PAGE->cm->context)) {
        $repairurl = new \moodle_url('/mod/collabora/repair.php', array('id' => $PAGE->cm->id));
        $repairicon = new \pix_icon('repair', get_string('repair', 'mod_collabora'), 'mod_collabora');

        $collaboranode->add(
            get_string('repair', 'mod_collabora'),
            $repairurl,
            navigation_node::TYPE_SETTING,
            null,
            null,
            $repairicon
        );
    }
}

/**
 * Get icon mapping for FontAwesome.
 */
function collabora_get_fontawesome_icon_map() {
    // We build a map of some icons we use in pix_icon objects.
    $iconmap = array(
        'mod_collabora:repair' => 'fa-medkit',
    );

    return $iconmap;
}
