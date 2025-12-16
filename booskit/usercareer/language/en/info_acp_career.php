<?php
/**
 *
 * @package booskit/usercareer
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
	'ACP_BOOSKIT_CAREER_TITLE' => 'User Career',
	'ACP_BOOSKIT_CAREER_SETTINGS' => 'Career Settings',
	'CAREER_JSON_URL' => 'Definitions JSON URL',
	'CAREER_JSON_URL_EXPLAIN' => 'URL to the JSON file containing career type definitions.',
	'CAREER_ACCESS_VIEW' => 'Local View Access Group IDs',
	'CAREER_ACCESS_VIEW_GLOBAL' => 'Global View Access Group IDs',
	'CAREER_ACCESS_L1' => 'Level 1 Access Group IDs',
	'CAREER_ACCESS_L2' => 'Level 2 Access Group IDs',
	'CAREER_ACCESS_L3' => 'Level 3 Access Group IDs',
	'CAREER_ACCESS_FULL' => 'Full Access Group IDs',
	'CAREER_ACCESS_LEVEL_EXPLAIN' => 'Comma separated list of Group IDs.',
	'LOG_CAREER_ADDED' => '<strong>Added career note to user</strong><br />» %s',
	'LOG_CAREER_EDITED' => '<strong>Edited career note for user</strong><br />» %s',
	'LOG_CAREER_DELETED' => '<strong>Deleted career note from user</strong><br />» %s',
));
