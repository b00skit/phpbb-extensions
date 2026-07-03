<?php
/**
 *
 * Extended Permissions. An extension for the phpBB Forum Software package.
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
	$lang = [];
}

$lang = array_merge($lang, [
	'ACP_EXTENDEDPERMISSIONS_TITLE'			=> 'Extended Permissions',
	'ACP_EXTENDEDPERMISSIONS_EXPLAIN'		=> 'Allows configuring group-based access control for moderator logs and the last 5 actions list in the MCP, and controls administrative permissions.',
	'ACP_EXTENDEDPERMISSIONS_SETTINGS'		=> 'Extended Permissions Settings',
	'ACP_EXTENDEDPERMISSIONS_MOD_LOGS'		=> 'Groups with access to Moderator Logs',
	'ACP_EXTENDEDPERMISSIONS_MOD_LOGS_EXPLAIN'	=> 'Select which user groups are allowed to access and view Moderator Logs in the MCP. If no groups are selected, all moderators will have access as default.',
	'ACP_EXTENDEDPERMISSIONS_LAST_ACTIONS'	=> 'Groups with access to Last 5 Actions',
	'ACP_EXTENDEDPERMISSIONS_LAST_ACTIONS_EXPLAIN'	=> 'Select which user groups are allowed to view the &ldquo;Latest 5 logged actions&rdquo; list on the MCP home page. If no groups are selected, all moderators will have access as default.',
	'ACP_EXTENDEDPERMISSIONS_SAVED'			=> 'Extended Permissions settings have been saved.',

	'LOG_EXTENDEDPERMISSIONS_CONFIG'		=> '<strong>Extended Permissions settings updated</strong>',
]);
