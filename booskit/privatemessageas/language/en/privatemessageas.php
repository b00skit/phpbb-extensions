<?php
/**
 *
 * Send As. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'PRIVATEMESSAGEAS_YOURSELF' => 'Yourself',
	'PRIVATEMESSAGEAS_SELECT' => 'Private message as:',
	'PRIVATEMESSAGEAS_EXPLAIN' => 'Select which character to private message as. This will change the displayed name color and rank image.',
]);
