<?php
// app/Helpers/menu_helper.php

if (!function_exists('isActive')) {
    /**
     * Kembalikan class "active" jika path URL saat ini mengandung salah satu key.
     * @param string|array $keys  ex: 'pengguna-dosen' atau ['pengguna-dosen','pengguna-reviewer']
     * @param string       $class class yang dikembalikan (default: 'active')
     */
    function isActive($keys, string $class = 'active'): string
    {
        $path = trim(service('request')->uri->getPath(), '/'); // ex: admin/master/pengguna-dosen
        foreach ((array)$keys as $k) {
            $k = trim((string)$k, '/');
            if ($k !== '' && stripos($path, $k) !== false) {
                return $class;
            }
        }
        return '';
    }
}

if (!function_exists('openShow')) {
    /**
     * Untuk class collapse: return 'show' jika salah satu key match.
     */
    function openShow($keys): string
    {
        return isActive($keys, 'show');
    }
}

if (!function_exists('openExpanded')) {
    /**
     * Untuk atribut aria-expanded: return 'true' jika match, 'false' jika tidak.
     */
    function openExpanded($keys): string
    {
        return isActive($keys) ? 'true' : 'false';
    }
}
