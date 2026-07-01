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
	'TOPIC_PREFIX'	=> 'Topic Prefix',
	'NO_PREFIX'		=> '-- No Prefix --',
));
