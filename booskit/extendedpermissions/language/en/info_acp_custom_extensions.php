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
	'ACP_EXTENSIONS_MANAGER'			=> 'Extensions Manager',
	'ACP_CUSTOM_EXTENSIONS'				=> 'Custom Extensions',
	'ACP_CUSTOM_EXTENSIONS_TITLE'		=> 'Custom Extensions',
	'ACP_CUSTOM_EXTENSIONS_SETTINGS'	=> 'Custom Extensions Settings',
	'ACP_CUSTOM_EXTENSIONS_EXPLAIN'		=> 'Configure custom extension permissions to define which user groups have access to this module and what extensions they can access in the ACP.',

	'EXTPERM_SYSTEM'					=> 'Custom Permissions System',
	'EXTPERM_SYSTEM_EXPLAIN'			=> 'When enabled, users without full administrator privileges are restricted according to their assigned permission groups.',
	'EXTPERM_SYSTEM_ENABLED'			=> 'Enabled (Group-based Permissions)',
	'EXTPERM_SYSTEM_DISABLED'			=> 'Disabled (All extensions accessible to extension managers)',

	'MODULE_ACCESS_GROUPS'				=> 'Module Access Groups',
	'MODULE_ACCESS_GROUPS_EXPLAIN'		=> 'Select which user groups have direct access to configure this Custom Extensions module.',

	'PERMISSION_GROUPS'					=> 'Permission Groups',
	'PERMISSION_GROUPS_EXPLAIN'			=> 'Configure custom permission groups to control access by user group, module management access, and per-extension access.',
	'ADD_PERMISSION_GROUP'				=> 'Add Permission Group',
	'PERMISSION_GROUP_NAME'				=> 'Permission Group Name',
	'APPLIES_TO_GROUPS'					=> 'Applies To Groups',
	'APPLIES_TO_GROUPS_EXPLAIN'			=> 'Select which phpBB user groups receive these permissions.',
	'CAN_MANAGE_MODULE'					=> 'Can Access Custom Extensions Module',
	'CAN_MANAGE_MODULE_EXPLAIN'			=> 'Allow members of these groups to access and configure this Custom Extensions module in the ACP.',
	'ALLOWED_EXTENSIONS'				=> 'Allowed Extensions',
	'ALLOWED_EXTENSIONS_EXPLAIN'		=> 'Select which extensions members of these groups are permitted to access.',
	'EXTENSION_NAME'					=> 'Extension',
	'PERM_ACCESS'						=> 'Allow Access',
	'NO_EXTENSIONS_FOUND'				=> 'No installed extensions found.',
	'UPDATE'							=> 'Update',
	'DELETE'							=> 'Delete',
	'CONFIG_UPDATED'					=> 'Configuration updated successfully.',
]);
