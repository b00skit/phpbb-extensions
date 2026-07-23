<?php
/**
 *
 * Hide Content Extension for phpBB.
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
	$lang = array();
}

$lang = array_merge($lang, array(
	'ACL_M_HIDE'        => 'Can hide content',
	'ACL_M_VIEW_HIDDEN' => 'Can view hidden content',
));
