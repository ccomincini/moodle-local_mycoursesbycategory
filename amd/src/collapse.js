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
 * Collapse/expand functionality for category sections.
 *
 * Persists collapse state per category in localStorage so that the
 * user sees the same layout across page reloads and sessions.
 *
 * Compatible with Bootstrap 4 (Moodle 4.3) and Bootstrap 5 (Moodle 4.4+).
 *
 * @module     local_mycoursesbycategory/collapse
 * @copyright  2026 Invisiblefarm
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* global bootstrap */
define(['jquery'], function($) {

    var storageKey = '';

    /**
     * Get the set of collapsed category IDs from localStorage.
     *
     * @return {Object} Map of category ID → true for collapsed categories.
     */
    function getCollapsedState() {
        try {
            return JSON.parse(localStorage.getItem(storageKey)) || {};
        } catch (e) {
            return {};
        }
    }

    /**
     * Save the set of collapsed category IDs to localStorage.
     *
     * @param {Object} state Map of category ID → true.
     */
    function saveCollapsedState(state) {
        try {
            localStorage.setItem(storageKey, JSON.stringify(state));
        } catch (e) {
            // Storage full or unavailable — silently ignore.
        }
    }

    /**
     * Toggle a collapsible element using the available Bootstrap API.
     *
     * @param {HTMLElement} element The collapsible element.
     * @param {string} action Either 'show' or 'hide'.
     */
    function toggleCollapse(element, action) {
        // Bootstrap 5 native API (Moodle 4.4+).
        if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
            var instance = bootstrap.Collapse.getOrCreateInstance(element, {toggle: false});
            instance[action]();
        } else {
            // Bootstrap 4 jQuery API (Moodle 4.3).
            $(element).collapse(action);
        }
    }

    return {
        /**
         * Initialise the collapse module.
         *
         * @param {number} userid The current user ID.
         */
        init: function(userid) {
            storageKey = 'local_mycoursesbycategory_collapsed_' + userid;
            var $container = $('#local-mycoursesbycategory');
            if (!$container.length) {
                return;
            }

            // Restore saved state: collapse sections that were previously collapsed.
            var collapsed = getCollapsedState();
            $container.find('.category-section .collapse').each(function() {
                // E.g. "category-5".
                var id = this.id;
                if (collapsed[id]) {
                    // Remove 'show' class immediately (no animation on page load).
                    $(this).removeClass('show');
                    $(this).prev('.card-header').attr('aria-expanded', 'false');
                }
            });

            // Expand all.
            $container.on('click', '[data-action="expand-all"]', function() {
                var state = {};
                // Clear all collapsed state.
                saveCollapsedState(state);
                $container.find('.category-section .collapse').each(function() {
                    toggleCollapse(this, 'show');
                });
            });

            // Collapse all.
            $container.on('click', '[data-action="collapse-all"]', function() {
                var state = {};
                $container.find('.category-section .collapse').each(function() {
                    state[this.id] = true;
                    toggleCollapse(this, 'hide');
                });
                saveCollapsedState(state);
            });

            // Update aria-expanded and persist state when sections are toggled.
            $container.on('shown.bs.collapse hidden.bs.collapse', '.collapse', function(e) {
                var $header = $(this).prev('.card-header');
                var isShown = e.type === 'shown';
                $header.attr('aria-expanded', isShown ? 'true' : 'false');

                // Persist the change.
                var state = getCollapsedState();
                if (isShown) {
                    delete state[this.id];
                } else {
                    state[this.id] = true;
                }
                saveCollapsedState(state);
            });
        }
    };
});
