<?php
/**
 *
 * @package booskit/usercommandcenter
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
	'ACP_BOOSKIT_UCC_TITLE' => 'User Command Center',
	'UCC_DASHBOARD' => 'User Command Center Dashboard',
	'UCC_GENERAL_SETTINGS' => 'General Settings',
	'UCC_MODULE_SETTINGS' => 'Module Inclusion Settings',
	'UCC_ENABLED' => 'Enable Command Center',
	'UCC_ALLOWED_GROUPS' => 'Allowed Group IDs',
	'UCC_ALLOWED_GROUPS_EXPLAIN' => 'Comma separated list of Group IDs allowed to access the Command Center dashboard.',
	'UCC_INCLUDE_AWARDS' => 'Include Latest Awards',
	'UCC_INCLUDE_CAREER' => 'Include Latest Career Notes',
	'UCC_INCLUDE_COMMENDATIONS' => 'Include Latest Commendations',
	'UCC_INCLUDE_DISCIPLINARY' => 'Include Latest Disciplinary Actions',
	'UCC_INCLUDE_IC_DISCIPLINARY' => 'Include Latest IC Disciplinary Actions',
	'UCC_SETTINGS_SAVED' => 'User Command Center settings saved successfully.',
	'UCC_NO_DATA' => 'No recent items found in this category.',
	'UCC_AWARDS_TITLE' => 'Latest Awards',
	'UCC_CAREER_TITLE' => 'Latest Career Notes',
	'UCC_COMMENDATIONS_TITLE' => 'Latest Commendations',
	'UCC_DISCIPLINARY_TITLE' => 'Latest Disciplinary Actions',
	'UCC_IC_DISCIPLINARY_TITLE' => 'Latest IC Disciplinary Actions',
	'UCC_VIEW_PROFILE' => 'View Profile',
	'UCC_VIEW_ALL' => 'View All',
	'UCC_BACK_TO_DASHBOARD' => 'Back to Dashboard',
	'UCC_ISSUED_BY' => 'Issued by',
	'UCC_USER' => 'User',
	'UCC_DATE' => 'Date',
	'UCC_TYPE' => 'Type',
	'UCC_CHARACTER' => 'Character',
));
