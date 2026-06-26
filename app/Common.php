<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('render_pagination')) {
    /**
     * Renders a Bootstrap 5 pagination with ellipses (...) for many pages.
     */
    function render_pagination(int $currentPage, int $totalPages, callable $urlGenerator): string
    {
        if ($totalPages <= 1) return '';

        $sidePages = 2; // Number of pages to show around current page
        $html = '<ul class="pagination mb-0 flex-wrap justify-content-center">';

        // Previous Button
        $prevClass = ($currentPage <= 1) ? 'disabled' : '';
        $prevUrl = $urlGenerator(max(1, $currentPage - 1));
        $html .= "<li class='page-item {$prevClass}'><a class='page-link js-page' href='{$prevUrl}'>&laquo;</a></li>";

        // Logic for page numbers
        for ($i = 1; $i <= $totalPages; $i++) {
            // Always show first and last pages
            // Show current page and $sidePages around it
            if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $sidePages && $i <= $currentPage + $sidePages)) {
                $activeClass = ($i == $currentPage) ? 'active' : '';
                $url = $urlGenerator($i);
                $html .= "<li class='page-item {$activeClass}'><a class='page-link js-page' href='{$url}'>{$i}</a></li>";
            } 
            // Show ellipses
            elseif ($i == $currentPage - $sidePages - 1 || $i == $currentPage + $sidePages + 1) {
                $html .= "<li class='page-item disabled'><span class='page-link'>&hellip;</span></li>";
            }
        }

        // Next Button
        $nextClass = ($currentPage >= $totalPages) ? 'disabled' : '';
        $nextUrl = $urlGenerator(min($totalPages, $currentPage + 1));
        $html .= "<li class='page-item {$nextClass}'><a class='page-link js-page' href='{$nextUrl}'>&raquo;</a></li>";

        $html .= '</ul>';
        return $html;
    }
}
