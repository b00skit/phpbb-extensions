<?php
/**
 *
 * @package booskit/threadprefixes
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
	'ACL_F_APPLY_PREFIX'	=> 'Can apply thread prefixes to topics',
));
