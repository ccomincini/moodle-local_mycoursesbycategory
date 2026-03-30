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
 * Admin settings for the plugin.
 *
 * @package    local_mycoursesbycategory
 * @copyright  2026 Invisiblefarm
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_mycoursesbycategory',
        get_string('pluginname', 'local_mycoursesbycategory')
    );

    // Enable redirect.
    $settings->add(new admin_setting_configcheckbox(
        'local_mycoursesbycategory/enableredirect',
        get_string('redirectenabled', 'local_mycoursesbycategory'),
        get_string('redirectdescription', 'local_mycoursesbycategory'),
        0
    ));

    // Layout.
    $settings->add(new admin_setting_configselect(
        'local_mycoursesbycategory/layout',
        get_string('layout', 'local_mycoursesbycategory'),
        get_string('layoutdescription', 'local_mycoursesbycategory'),
        'card',
        [
            'card' => get_string('layoutcard', 'local_mycoursesbycategory'),
            'list' => get_string('layoutlist', 'local_mycoursesbycategory'),
        ]
    ));

    $ADMIN->add('localplugins', $settings);
}
