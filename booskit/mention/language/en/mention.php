<?php
/**
 *
 * @package booskit/mention
 * @license MIT
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
	'NOTIFICATION_MENTION'            => [
		1 => '<strong>Mentioned</strong> by %1$s in:',
	],
	'NOTIFICATION_TYPE_MENTION'       => 'Someone mentions you in a post',

	'NOTIFICATION_GROUP_MENTION'      => [
		1 => '<strong>Group mentioned</strong> by %1$s in:',
	],
	'NOTIFICATION_TYPE_GROUP_MENTION' => 'Someone mentions a group you belong to in a post',
]);
