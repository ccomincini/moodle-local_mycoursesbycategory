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
 * Hook callbacks for local_mycoursesbycategory.
 *
 * @package    local_mycoursesbycategory
 * @copyright  2026 Invisiblefarm
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mycoursesbycategory;

use core\hook\output\before_http_headers;

/**
 * Hook callbacks for the my courses by category plugin.
 */
class hook_callbacks {
    /**
     * Redirect /my/courses.php to the plugin page when enabled.
     *
     * @param before_http_headers $hook The hook instance (unused, required by hook API).
     */
    public static function before_http_headers(before_http_headers $hook): void { // phpcs:ignore
        if (!get_config('local_mycoursesbycategory', 'enableredirect')) {
            return;
        }

        $requesturi = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($requesturi, '/my/courses.php') !== false) {
            redirect(new \moodle_url('/local/mycoursesbycategory/index.php'));
        }
    }
}
