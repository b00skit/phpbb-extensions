<?php
/**
 *
 * @package booskit/twofactor
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
    'UCP_BOOSKIT_TWOFACTOR_TITLE'   => 'Two-Factor Authentication',
    'UCP_BOOSKIT_TWOFACTOR_MANAGE'  => 'Two-Factor Authentication',
]);
