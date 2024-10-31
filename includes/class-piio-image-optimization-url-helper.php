<?php

/**
* Piio URL Helper
*
* @link       https://piio.co
* @since      0.9.0
*
* @package    Piio_Image_Optimization
* @subpackage Piio_Image_Optimization/includes
*/

/**
* Fired during plugin activation.
*
* This class defines helpers to work with url
*
* @since      0.9.16
* @package    Piio_Image_Optimization
* @subpackage Piio_Image_Optimization/includes
* @author     Piio, Inc. <support@piio.co>
*/
if (!class_exists('Piio_Image_Optimization_FILE_HELPER')) {
    class Piio_Image_Optimization_File_Helper
    {
        public static function url_to_absolute($relativeUrl)
        {
            $baseUrl = get_site_url();
            // If relative URL has a scheme, clean path and return.
            $r = parse_url($relativeUrl);
            if ($r === false) {
                return false;
            }
            if (!empty($r['scheme'])) {
                if (!empty($r['path']) && $r['path'][0] == '/') {
                    $r['path'] = self::url_remove_dot_segments($r['path']);
                }
                return self::join_url($r);
            }

            // Make sure the base URL is absolute.
            $b = parse_url($baseUrl);
            if ($b === false || empty($b['scheme']) || empty($b['host'])) {
                return false;
            }
            $r['scheme'] = $b['scheme'];

            // If relative URL has an authority, clean path and return.
            if (isset($r['host'])) {
                if (!empty($r['path'])) {
                    $r['path'] = self::url_remove_dot_segments($r['path']);
                }
                return self::join_url($r);
            }
            unset($r['port']);
            unset($r['user']);
            unset($r['pass']);

            // Copy base authority.
            $r['host'] = $b['host'];
            if (isset($b['port'])) {
                $r['port'] = $b['port'];
            }
            if (isset($b['user'])) {
                $r['user'] = $b['user'];
            }
            if (isset($b['pass'])) {
                $r['pass'] = $b['pass'];
            }

            // If relative URL has no path, use base path
            if (empty($r['path'])) {
                if (!empty($b['path'])) {
                    $r['path'] = $b['path'];
                }
                if (!isset($r['query']) && isset($b['query'])) {
                    $r['query'] = $b['query'];
                }
                return self::join_url($r);
            }

            // If relative URL path doesn't start with /, merge with base path
            if ($r['path'][0] != '/') {
                $base = mb_strrchr($b['path'], '/', true, 'UTF-8');
                if ($base === false) {
                    $base = '';
                }
                $r['path'] = $base . '/' . $r['path'];
            }
            $r['path'] = self::url_remove_dot_segments($r['path']);
            return self::join_url($r);
        }

        private static function join_url($parts, $encode = true)
        {
            if ($encode) {
                if (isset($parts['user'])) {
                    $parts['user'] = rawurlencode($parts['user']);
                }
                if (isset($parts['pass'])) {
                    $parts['pass'] = rawurlencode($parts['pass']);
                }
                if (isset($parts['host']) && !preg_match('!^(\[[\da-f.:]+\]])|([\da-f.:]+)$!ui', $parts['host'])) {
                    $parts['host'] = rawurlencode($parts['host']);
                }
                if (!empty($parts['path'])) {
                    $parts['path'] = preg_replace('!%2F!ui', '/', rawurlencode($parts['path']));
                }
                if (isset($parts['query'])) {
                    $parts['query'] = rawurlencode($parts['query']);
                }
                if (isset($parts['fragment'])) {
                    $parts['fragment'] = rawurlencode($parts['fragment']);
                }
            }

            $url = '';
            if (!empty($parts['scheme'])) {
                $url .= $parts['scheme'] . ':';
            }
            if (isset($parts['host'])) {
                $url .= '//';
                if (isset($parts['user'])) {
                    $url .= $parts['user'];
                    if (isset($parts['pass'])) {
                        $url .= ':' . $parts['pass'];
                    }
                    $url .= '@';
                }
                if (preg_match('!^[\da-f]*:[\da-f.:]+$!ui', $parts['host'])) {
                    $url .= '[' . $parts['host'] . ']'; // IPv6
                } else {
                    $url .= $parts['host'];             // IPv4 or name
                }
                if (isset($parts['port'])) {
                    $url .= ':' . $parts['port'];
                }
                if (!empty($parts['path']) && $parts['path'][0] != '/') {
                    $url .= '/';
                }
            }
            if (!empty($parts['path'])) {
                $url .= $parts['path'];
            }
            if (isset($parts['query'])) {
                $url .= '?' . $parts['query'];
            }
            if (isset($parts['fragment'])) {
                $url .= '#' . $parts['fragment'];
            }
            return $url;
        }

        private static function url_remove_dot_segments($path)
        {
            // multi-byte character explode
            $inSegs = preg_split('!/!u', $path);
            $outSegs = array( );
            foreach ($inSegs as $seg) {
                if ($seg == '' || $seg == '.') {
                    continue;
                }
                if ($seg == '..') {
                    array_pop($outSegs);
                } else {
                    array_push($outSegs, $seg);
                }
            }
            $outPath = implode('/', $outSegs);
            if ($path[0] == '/') {
                $outPath = '/' . $outPath;
            }
            // compare last multi-byte character against '/'
            if ($outPath != '/' && (mb_strlen($path)-1) == mb_strrpos($path, '/', 'UTF-8')) {
                $outPath .= '/';
            }
            return $outPath;
        }
    }
}
