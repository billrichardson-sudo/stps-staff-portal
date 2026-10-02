<?php

// Collapses "a/b/../c" into "a/c" so no literal ".." ever reaches
// file_exists()/require(). Some hosts block any path containing a literal
// ".." segment even when it resolves to a legitimate in-account file.
if (! function_exists('stpsNormalizePath')) {
    function stpsNormalizePath($path)
    {
        $isAbsolute = isset($path[0]) && ($path[0] === '/' || $path[0] === '\\');
        $parts = array();
        foreach (preg_split('#[/\\\\]+#', $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }

        return ($isAbsolute ? '/' : '').implode('/', $parts);
    }
}
