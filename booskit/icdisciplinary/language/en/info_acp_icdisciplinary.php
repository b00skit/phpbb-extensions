<?php
/**
 *
 * @package booskit/icdisciplinary
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
	'ACP_BOOSKIT_ICDISCIPLINARY_TITLE'		=> 'IC Disciplinary Records',
	'ACP_ICDISCIPLINARY_SETTINGS'			=> 'Settings',
	'ACP_ICDISCIPLINARY_SETTINGS_EXPLAIN'	=> 'Configure the IC Disciplinary Records extension.',

	'BOOSKIT_ICDISCIPLINARY_SOURCE'			=> 'Definitions Source',
	'BOOSKIT_ICDISCIPLINARY_SOURCE_EXPLAIN'	=> 'Select whether to use a local database table or an external JSON file for disciplinary definitions.',
	'BOOSKIT_ICDISCIPLINARY_SOURCE_DB'		=> 'Local Database',
	'BOOSKIT_ICDISCIPLINARY_SOURCE_JSON'	=> 'External JSON',
	'BOOSKIT_ICDISCIPLINARY_JSON_URL'		=> 'JSON URL',
	'BOOSKIT_ICDISCIPLINARY_JSON_URL_EXPLAIN'=> 'URL to the JSON file containing disciplinary definitions.',

	'BOOSKIT_ICDISCIPLINARY_ACCESS_L1'		=> 'Level 1 Access Groups',
	'BOOSKIT_ICDISCIPLINARY_ACCESS_L2'		=> 'Level 2 Access Groups',
	'BOOSKIT_ICDISCIPLINARY_ACCESS_FULL'	=> 'Full Access Groups',
	'BOOSKIT_ICDISCIPLINARY_ACCESS_EXPLAIN'	=> 'Comma-separated list of Group IDs.',

    'RULES'             => 'Ruleset',
    'RULES_EXPLAIN'     => 'The ruleset text displayed on the add/edit form.',

	'BOOSKIT_ICDISCIPLINARY_SOURCE_URL'		=> 'URL',
	'BOOSKIT_ICDISCIPLINARY_SOURCE_LOCAL'		=> 'Local',
	'BOOSKIT_ICDISCIPLINARY_LOCAL_DEFINITIONS'	=> 'Local Definitions',
	'BOOSKIT_ICDISCIPLINARY_LOCAL_DEFINITIONS_EXPLAIN' => 'Manage disciplinary types locally.',
));
