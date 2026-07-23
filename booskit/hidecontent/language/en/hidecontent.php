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
	'HIDE_POST'             => 'Hide Post',
	'UNHIDE_POST'           => 'Unhide Post',
	'HIDE_TOPIC'            => 'Hide Topic',
	'UNHIDE_TOPIC'          => 'Unhide Topic',
	'POST_HIDDEN_SUCCESS'   => 'The post has been successfully hidden.',
	'POST_UNHIDDEN_SUCCESS' => 'The post has been successfully unhidden.',
	'TOPIC_HIDDEN_SUCCESS'  => 'The topic has been successfully hidden.',
	'TOPIC_UNHIDDEN_SUCCESS' => 'The topic has been successfully unhidden.',
	'POST_IS_HIDDEN_BADGE'  => 'HIDDEN POST',
	'TOPIC_IS_HIDDEN_BADGE' => 'HIDDEN TOPIC',
	'HIDE_CONTENT_OPTION'   => 'Hide content',
));
