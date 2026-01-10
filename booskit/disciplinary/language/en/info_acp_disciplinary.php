<?php
/**
 *
 * @package booskit/disciplinary
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
	'ACP_BOOSKIT_DISCIPLINARY_TITLE'		=> 'User Disciplinary Actions',
	'ACP_BOOSKIT_DISCIPLINARY_SETTINGS'		=> 'Disciplinary Settings',

	'BOOSKIT_DISCIPLINARY_SOURCE' => 'Definitions Source',
	'BOOSKIT_DISCIPLINARY_SOURCE_EXPLAIN' => 'Choose whether to load disciplinary definitions from an external JSON URL or manage them locally.',
	'BOOSKIT_DISCIPLINARY_SOURCE_URL' => 'External JSON URL',
	'BOOSKIT_DISCIPLINARY_SOURCE_LOCAL' => 'Local Settings (Database)',

	'BOOSKIT_DISCIPLINARY_JSON_URL'			=> 'External JSON Definition URL',
	'BOOSKIT_DISCIPLINARY_JSON_URL_EXPLAIN'	=> 'URL to a JSON file containing disciplinary action definitions.',

	'BOOSKIT_DISCIPLINARY_ACCESS_L1'			=> 'Level 1 Access Groups',
	'BOOSKIT_DISCIPLINARY_ACCESS_L1_EXPLAIN'	=> 'Comma separated list of group IDs for Level 1 Access.',
	'BOOSKIT_DISCIPLINARY_ACCESS_L2'			=> 'Level 2 Access Groups',
	'BOOSKIT_DISCIPLINARY_ACCESS_L2_EXPLAIN'	=> 'Comma separated list of group IDs for Level 2 Access.',
	'BOOSKIT_DISCIPLINARY_ACCESS_L3'			=> 'Level 3 Access Groups',
	'BOOSKIT_DISCIPLINARY_ACCESS_L3_EXPLAIN'	=> 'Comma separated list of group IDs for Level 3 Access.',
	'BOOSKIT_DISCIPLINARY_ACCESS_FULL'			=> 'Full Access Groups',
	'BOOSKIT_DISCIPLINARY_ACCESS_FULL_EXPLAIN'	=> 'Comma separated list of group IDs for Full Access.',

	'RULES' => 'Ruleset',
	'RULES_EXPLAIN' => 'The message displayed at the top of the management form. BBCode is supported.',

	'BOOSKIT_DISCIPLINARY_LOCAL_DEFINITIONS' => 'Local Definitions',
	'BOOSKIT_DISCIPLINARY_LOCAL_DEFINITIONS_EXPLAIN' => 'Manage the disciplinary definitions here when "Local Settings" is selected.',

	'ID' => 'ID',
	'NAME' => 'Name',
	'DESCRIPTION' => 'Description',
	'COLOR' => 'Color',
	'ACCESS_LEVEL' => 'Access Level',
	'ACTION' => 'Action',
	'ADD' => 'Add',
	'UPDATE' => 'Update',
	'DELETE' => 'Delete',
));
