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

	'BOOSKIT_AWARDS_SOURCE' => 'Definitions Source',
	'BOOSKIT_AWARDS_SOURCE_EXPLAIN' => 'Choose whether to load award definitions from an external JSON URL or manage them locally.',
	'BOOSKIT_AWARDS_SOURCE_URL' => 'External JSON URL',
	'BOOSKIT_AWARDS_SOURCE_LOCAL' => 'Local Settings (Database)',

	'BOOSKIT_AWARDS_JSON_URL' => 'JSON URL',
	'BOOSKIT_AWARDS_JSON_URL_EXPLAIN' => 'The URL to fetch the award definitions JSON from.',

	'BOOSKIT_AWARDS_ACCESS_L1' => 'Level 1 Access Groups',
	'BOOSKIT_AWARDS_ACCESS_L1_EXPLAIN' => 'Comma separated list of group IDs.',
	'BOOSKIT_AWARDS_ACCESS_L2' => 'Level 2 Access Groups',
	'BOOSKIT_AWARDS_ACCESS_L2_EXPLAIN' => 'Comma separated list of group IDs.',
	'BOOSKIT_AWARDS_ACCESS_FULL' => 'Full Access Groups',
	'BOOSKIT_AWARDS_ACCESS_FULL_EXPLAIN' => 'Comma separated list of group IDs.',

	'RULES' => 'Ruleset',
	'RULES_EXPLAIN' => 'The message displayed at the top of the management form. BBCode is supported.',

	'BOOSKIT_AWARDS_LOCAL_DEFINITIONS' => 'Local Definitions',
	'BOOSKIT_AWARDS_LOCAL_DEFINITIONS_EXPLAIN' => 'Manage the award definitions here when "Local Settings" is selected.',

	'ID' => 'ID',
	'NAME' => 'Name',
	'DESCRIPTION' => 'Description',
	'IMAGE' => 'Image URL',
	'DIMENSIONS' => 'Dimensions (WxH)',
	'ACTION' => 'Action',
	'ADD' => 'Add',
	'UPDATE' => 'Update',
	'DELETE' => 'Delete',
));
