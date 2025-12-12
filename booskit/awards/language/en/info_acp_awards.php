<?php
/**
 *
 * @package booskit/awards
 * @license MIT
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
	'ACP_BOOSKIT_AWARDS_TITLE' => 'User Awards',
	'ACP_BOOSKIT_AWARDS_SETTINGS' => 'Settings',
));
