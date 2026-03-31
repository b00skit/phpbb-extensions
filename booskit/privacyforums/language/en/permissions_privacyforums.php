<?php
/**
 *
 * @package booskit/privacyforums
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
	'ACL_F_VIEW_OTHERS' => 'Can view other\'s forum content',
	'ACL_F_POST_OTHERS' => 'Can post on other\'s forum content',
	'NOT_YOUR_TOPIC_REPLY' => 'You are not allowed to reply to other\'s topics in this forum.',
));
