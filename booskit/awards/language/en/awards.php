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
    'SELECT_OPTION' => 'Select option',
	'BOOSKIT_AWARDS_ACCESS_L1' => 'Level 1 Access Groups',
	'BOOSKIT_AWARDS_ACCESS_L1_EXPLAIN' => 'Comma separated list of group IDs for Level 1 access.',
	'BOOSKIT_AWARDS_ACCESS_L2' => 'Level 2 Access Groups',
	'BOOSKIT_AWARDS_ACCESS_L2_EXPLAIN' => 'Comma separated list of group IDs for Level 2 access.',
	'BOOSKIT_AWARDS_ACCESS_FULL' => 'Full Access Groups',
	'BOOSKIT_AWARDS_ACCESS_FULL_EXPLAIN' => 'Comma separated list of group IDs for Full Access.',
	'REMOVE' => 'Remove',
	'AWARD_REMOVED' => 'Award successfully removed.',
	'CONFIRM_OPERATION' => 'Are you sure you want to carry out this operation?',

	'LOG_AWARD_ADDED' => '<strong>Added award to user</strong><br>» %s',
	'LOG_AWARD_REMOVED' => '<strong>Removed award from user</strong><br>» %s',
));
