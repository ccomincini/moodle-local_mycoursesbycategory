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
 * Library callbacks for local_mycoursesbycategory.
 *
 * @package    local_mycoursesbycategory
 * @copyright  2026 Invisiblefarm
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add a navigation node for this plugin.
 *
 * @param global_navigation $navigation The global navigation object.
 */
function local_mycoursesbycategory_extend_navigation(global_navigation $navigation) {
    $mycourses = $navigation->find('mycourses', navigation_node::TYPE_ROOTNODE);
    if ($mycourses) {
        $mycourses->add(
            get_string('mycoursesbycategory', 'local_mycoursesbycategory'),
            new moodle_url('/local/mycoursesbycategory/index.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'mycoursesbycategory',
            new pix_icon('i/course', '')
        );
    }
}
