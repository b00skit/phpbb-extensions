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
	'ACP_PRIVATEMESSAGEAS_TITLE'				=> 'Private Message As',
	'ACP_PRIVATEMESSAGEAS_EXPLAIN'			=> 'Manage Private Message As extension, which allows users to send private messages as alternative characters with custom names, colors, and ranks.',
	'ACP_PRIVATEMESSAGEAS_SETTINGS'			=> 'Private Message As Settings',
	'ACP_PRIVATEMESSAGEAS_SHOW_ORIGINAL'		=> 'Show original sender in brackets',
	'ACP_PRIVATEMESSAGEAS_SHOW_ORIGINAL_EXPLAIN'	=> 'Display the real username in brackets after the character name (e.g., "John (Jim)").',
	'ACP_PRIVATEMESSAGEAS_CONFIG_SAVED'		=> 'Private Message As settings have been saved.',
]);
