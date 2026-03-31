<?php
/**
 *
 * Post As. An extension for the phpBB Forum Software package.
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
	'ACP_POSTAS_TITLE'				=> 'Post As',
	'ACP_POSTAS_EXPLAIN'			=> 'Manage Post As extension, which allows users to post as alternative characters with custom names, colors, and ranks.',
	'ACP_POSTAS_SETTINGS'			=> 'Post As Settings',
	'ACP_POSTAS_SHOW_ORIGINAL'		=> 'Show original poster in brackets',
	'ACP_POSTAS_SHOW_ORIGINAL_EXPLAIN'	=> 'Display the real username in brackets after the character name (e.g., "John (Jim)").',
	'ACP_POSTAS_CONFIG_SAVED'		=> 'Post As settings have been saved.',
]);
