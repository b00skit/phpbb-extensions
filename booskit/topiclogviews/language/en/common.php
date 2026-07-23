<?php
/**
 *
 * Topic Log Views extension for phpBB.
 *
 * @copyright (c) 2026 Booskit
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
	'LOG_TOPIC_VIEWED' => '<strong>Viewed topic</strong><br />» %s',
));
