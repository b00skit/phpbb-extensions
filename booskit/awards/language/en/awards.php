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
	'BOOSKIT_AWARDS_JSON_URL' => 'External JSON URL',
	'BOOSKIT_AWARDS_JSON_URL_EXPLAIN' => 'URL to the JSON file containing award definitions. If left empty, internal defaults will be used.',
	'LOG_BOOSKIT_AWARDS_SETTINGS_UPDATED' => '<strong>User Awards settings updated</strong>',

	'ADD_AWARD' => 'Add Award',
	'AWARD_NAME' => 'Award Name',
	'AWARD_DATE' => 'Date of Issue',
	'AWARD_COMMENT' => 'Comment',
	'AWARD_ADDED' => 'Award successfully added.',
	'USER_AWARDS' => 'User Awards',
    'AWARD_SELECT' => 'Select Award',
    'NO_AWARD_SELECTED' => 'Please select an award.',
));
