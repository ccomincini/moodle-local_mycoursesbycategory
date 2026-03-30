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

use advanced_testcase;

/**
 * Tests for the helper class.
 *
 * @package    local_mycoursesbycategory
 * @copyright  2026 Invisiblefarm
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_mycoursesbycategory\helper
 */
class helper_test extends advanced_testcase {

    /**
     * Test grouping courses by category with courses in different categories.
     */
    public function test_get_courses_grouped_by_category(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();

        // Create two categories.
        $cat1 = $generator->create_category(['name' => 'Alpha Category']);
        $cat2 = $generator->create_category(['name' => 'Beta Category']);

        // Create courses in each category.
        $course1 = $generator->create_course(['category' => $cat1->id, 'fullname' => 'Course A']);
        $course2 = $generator->create_course(['category' => $cat1->id, 'fullname' => 'Course B']);
        $course3 = $generator->create_course(['category' => $cat2->id, 'fullname' => 'Course C']);

        // Create a user and enrol them in all courses.
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course1->id);
        $generator->enrol_user($user->id, $course2->id);
        $generator->enrol_user($user->id, $course3->id);

        $this->setUser($user);

        $categories = helper::get_courses_grouped_by_category($user->id);

        // Should have 2 categories, sorted alphabetically.
        $this->assertCount(2, $categories);
        $this->assertEquals('Alpha Category', $categories[0]['name']);
        $this->assertEquals('Beta Category', $categories[1]['name']);

        // Alpha should have 2 courses, Beta should have 1.
        $this->assertCount(2, $categories[0]['courses']);
        $this->assertEquals(2, $categories[0]['coursecount']);
        $this->assertCount(1, $categories[1]['courses']);
        $this->assertEquals(1, $categories[1]['coursecount']);
    }

    /**
     * Test that a user with no courses gets an empty array.
     */
    public function test_get_courses_no_enrolments(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);

        $categories = helper::get_courses_grouped_by_category($user->id);

        $this->assertIsArray($categories);
        $this->assertEmpty($categories);
    }

    /**
     * Test that course data contains expected fields.
     */
    public function test_course_data_fields(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();

        $cat = $generator->create_category(['name' => 'Test Category']);
        $course = $generator->create_course([
            'category' => $cat->id,
            'fullname' => 'Test Course',
            'shortname' => 'TC1',
        ]);

        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);

        $this->setUser($user);

        $categories = helper::get_courses_grouped_by_category($user->id);

        $this->assertCount(1, $categories);

        $coursedata = $categories[0]['courses'][0];
        $this->assertEquals($course->id, $coursedata['id']);
        $this->assertEquals('Test Course', $coursedata['fullname']);
        $this->assertEquals('TC1', $coursedata['shortname']);
        $this->assertArrayHasKey('courseurl', $coursedata);
        $this->assertArrayHasKey('courseimage', $coursedata);
        $this->assertArrayHasKey('iscomplete', $coursedata);
    }
}
