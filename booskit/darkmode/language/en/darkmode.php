<?php
/**
 *
 * @package booskit/darkmode
 * @license MIT
 *
 */

if (!defined('IN_PHPBB')) {
    exit;
}

if (empty($lang) || !is_array($lang)) {
    $lang = [];
}

$lang = array_merge($lang, [
    'DARK_MODE'          => 'Dark Mode',
    'DARK_MODE_ENABLE'   => 'Enable dark mode',
    'DARK_MODE_DISABLE'  => 'Disable dark mode',
    'DARK_MODE_TOGGLE'   => 'Toggle dark mode',
]);
