<?php
/**
 *
 * Extended Permissions. An extension for the phpBB Forum Software package.
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
	'ACL_A_EXTENSIONS_MANAGE' => 'Can manage extensions',
	'ACL_M_MOD_LOGS'          => 'Can view moderator logs',
	'ACL_M_LAST_ACTIONS'      => 'Can view last 5 actions',
]);

