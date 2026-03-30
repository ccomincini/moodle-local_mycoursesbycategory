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

namespace local_mycoursesbycategory;

use core_course_category;
use core_course_list_element;
use moodle_url;

/**
 * Helper class for grouping courses by category.
 *
 * @package    local_mycoursesbycategory
 * @copyright  2026 Invisiblefarm
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /**
     * Get enrolled courses grouped by category.
     *
     * @param int $userid The user ID. Defaults to current user.
     * @return array Array of categories, each containing an array of courses.
     */
    public static function get_courses_grouped_by_category(int $userid = 0): array {
        global $USER, $DB;

        if (!$userid) {
            $userid = $USER->id;
        }

        // Use default Moodle sort order (visible DESC, sortorder ASC) to respect admin ordering.
        $courses = enrol_get_my_courses('*');
        $categories = [];

        foreach ($courses as $course) {
            if ($course->id == SITEID) {
                continue;
            }

            $cat = core_course_category::get($course->category, IGNORE_MISSING, true);
            $catname = $cat ? $cat->get_formatted_name() : get_string('miscellaneous');
            $catsortorder = $cat ? $cat->sortorder : 0;
            $catid = $course->category;

            if (!isset($categories[$catid])) {
                $parentpath = $cat ? self::get_category_parent_path($cat) : '';
                $categories[$catid] = [
                    'id' => $catid,
                    'name' => $catname,
                    'parentpath' => $parentpath,
                    'hasparentpath' => $parentpath !== '',
                    'depth' => $cat ? (int) $cat->depth : 1,
                    'sortorder' => $catsortorder,
                    'courses' => [],
                    'coursecount' => 0,
                ];
            }

            // Course image.
            $courseobj = new core_course_list_element($course);
            $courseimage = self::get_course_image($courseobj);

            // Course completion: check directly in course_completions table.
            $timecompleted = $DB->get_field(
                'course_completions',
                'timecompleted',
                ['userid' => $userid, 'course' => $course->id]
            );
            $iscomplete = !empty($timecompleted);

            $categories[$catid]['courses'][] = [
                'id' => $course->id,
                'fullname' => format_string($course->fullname, true),
                'shortname' => format_string($course->shortname, true),
                'courseurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
                'courseimage' => $courseimage,
                'iscomplete' => $iscomplete,
                'completebadgeurl' => $iscomplete
                    ? (new moodle_url('/local/mycoursesbycategory/pix/completato.png'))->out(false)
                    : null,
            ];
            $categories[$catid]['coursecount']++;
        }

        // Sort categories by admin-defined sort order.
        usort($categories, fn($a, $b) => $a['sortorder'] <=> $b['sortorder']);

        return $categories;
    }

    /**
     * Build the parent category path as a breadcrumb string.
     *
     * For a category "2025" nested under "Protezione Civile" under "Formazione",
     * this returns "Formazione / Protezione Civile".
     *
     * @param core_course_category $cat The category.
     * @return string The parent path, empty for top-level categories.
     */
    private static function get_category_parent_path(core_course_category $cat): string {
        if ($cat->depth <= 1) {
            return '';
        }

        $ids = explode('/', trim($cat->path, '/'));
        // Remove the last element (current category).
        array_pop($ids);

        $parts = [];
        foreach ($ids as $id) {
            $ancestor = core_course_category::get((int) $id, IGNORE_MISSING, true);
            if ($ancestor) {
                $parts[] = $ancestor->get_formatted_name();
            }
        }

        return implode(' / ', $parts);
    }

    /**
     * Get the course image URL, with fallback to Moodle's generated pattern.
     *
     * @param core_course_list_element $course The course object.
     * @return string The image URL.
     */
    public static function get_course_image(core_course_list_element $course): string {
        global $OUTPUT;

        foreach ($course->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                return moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    null,
                    $file->get_filepath(),
                    $file->get_filename()
                )->out(false);
            }
        }

        // Fallback: Moodle's generated course image pattern.
        $context = \context_course::instance($course->id);
        return $OUTPUT->get_generated_url_for_course($context);
    }
}
