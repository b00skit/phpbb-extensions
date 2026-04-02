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
	'ACP_SENDAS_TITLE'				=> 'Send As',
	'ACP_SENDAS_EXPLAIN'			=> 'Manage Send As extension, which allows users to send private messages as alternative characters with custom names, colors, and ranks.',
	'ACP_SENDAS_SETTINGS'			=> 'Send As Settings',
	'ACP_SENDAS_SHOW_ORIGINAL'		=> 'Show original sender in brackets',
	'ACP_SENDAS_SHOW_ORIGINAL_EXPLAIN'	=> 'Display the real username in brackets after the character name (e.g., "John (Jim)").',
	'ACP_SENDAS_CONFIG_SAVED'		=> 'Send As settings have been saved.',
]);
