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
 * Main page: displays enrolled courses grouped by category.
 *
 * @package    local_mycoursesbycategory
 * @copyright  2026 Invisiblefarm
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/mycoursesbycategory:view', $context);

$title = get_string('mycoursesbycategory', 'local_mycoursesbycategory');

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/mycoursesbycategory/index.php'));
$PAGE->set_pagelayout('mycourses');
$PAGE->set_title($title);
$PAGE->set_heading('');

$categories = \local_mycoursesbycategory\helper::get_courses_grouped_by_category();

$templatedata = [
    'title' => $title,
    'hascourses' => !empty($categories),
    'categories' => $categories,
];

$PAGE->requires->js_call_amd('local_mycoursesbycategory/collapse', 'init', [$USER->id]);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_mycoursesbycategory/main', $templatedata);
echo $OUTPUT->footer();
