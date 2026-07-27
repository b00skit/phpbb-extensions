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

	'BOOSKIT_ICDISCIPLINARY_PERM_SYSTEM'			=> 'Permission System',
	'BOOSKIT_ICDISCIPLINARY_PERM_SYSTEM_EXPLAIN'	=> 'Choose between Legacy Access Levels (default) or New Permission Groups.',
	'BOOSKIT_ICDISCIPLINARY_PERM_SYSTEM_LEGACY'	=> 'Legacy (Access Levels)',
	'BOOSKIT_ICDISCIPLINARY_PERM_SYSTEM_GROUPS'	=> 'New Permission Groups',

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

	'PERMISSION_GROUPS'					=> 'Permission Groups',
	'PERMISSION_GROUPS_EXPLAIN'			=> 'Configure custom permission groups to control access by user group, power over target groups, character management, and per-action permissions.',
	'ADD_PERMISSION_GROUP'				=> 'Add Permission Group',
	'PERMISSION_GROUP_NAME'				=> 'Permission Group Name',
	'APPLIES_TO_GROUPS'					=> 'Applies To Groups',
	'APPLIES_TO_GROUPS_EXPLAIN'			=> 'Select which phpBB user groups receive these permissions.',
	'POWER_OVER_GROUPS'					=> 'Power Over Groups',
	'POWER_OVER_GROUPS_EXPLAIN'			=> 'Select target scopes or specific phpBB user groups this permission group has authority over.',
	'EXCLUDE_GROUPS'					=> 'Exclude Groups',
	'EXCLUDE_GROUPS_EXPLAIN'			=> 'Select target phpBB user groups that are explicitly excluded from this permission group.',
	'ALL_GROUPS'						=> 'All Groups',
	'SELF_GROUP'						=> 'Self',
	'CHARACTER_PERMISSIONS'				=> 'Character Permissions',
	'PERM_CREATE_CHARACTER'				=> 'Create Character',
	'PERM_ARCHIVE_CHARACTER'			=> 'Archive Character',
	'PERM_DELETE_CHARACTER'				=> 'Delete Character',
	'ENTRY_PERMISSIONS'					=> 'Entry Permissions',
	'PERM_EDIT_OWN'						=> 'Can Edit Own Entries',
	'PERM_DELETE_OWN'					=> 'Can Delete Own Entries',
	'DISCIPLINARY_ACTION_PERMISSIONS'	=> 'Action Access Matrix',
	'DISCIPLINARY_ACTION'				=> 'Disciplinary Action',
	'PERM_VIEW'							=> 'View',
	'PERM_VIEW_PRIVATE_NOTES'			=> 'View Private Notes',
	'PERM_ISSUE'						=> 'Issue',
	'PERM_ISSUE_PRIVATE_NOTES'			=> 'Issue Private Notes',
	'PERM_ARCHIVE'						=> 'Archive',
	'PERM_VIEW_ARCHIVED'				=> 'View Archived',
	'PERM_UNARCHIVE'					=> 'Unarchive',
	'PERM_EDIT'							=> 'Edit Others',
	'PERM_DELETE'						=> 'Delete Others',
	'PERM_COPY'							=> 'Copy to Clipboard',

	'CLIPBOARD_SETTINGS'				=> 'Clipboard Settings',
	'BOOSKIT_ICDISCIPLINARY_CLIPBOARD_TPL' => 'Clipboard Format',
	'BOOSKIT_ICDISCIPLINARY_CLIPBOARD_TPL_EXPLAIN' => 'Format string when copying an IC disciplinary record to the clipboard.<br />Available variables: {METADATA}, {CONTENT}, {PRIVATE}, {TYPE_COLOR}, {TYPE_ID}, {TYPE_NAME}, {TYPE_DESCRIPTION}, {TARGET_NAME}, {ISSUER_NAME}, {DATE}, {RECORD_ID}, {CHARACTER_NAME}.',

	'ID' => 'ID',
	'NAME' => 'Name',
	'DESCRIPTION' => 'Description',
	'COLOR' => 'Color',
	'ACCESS_LEVEL' => 'Access Level',
	'ACTION' => 'Action',
	'ADD' => 'Add',
	'UPDATE' => 'Update',
	'DELETE' => 'Delete',

    'LOG_IC_CHARACTER_ADDED'    => '<strong>IC Character added</strong><br />» %s',
    'LOG_IC_CHARACTER_ARCHIVED' => '<strong>IC Character archived</strong><br />» %s',
    'LOG_IC_CHARACTER_DELETED'  => '<strong>IC Character deleted</strong><br />» %s',
    'LOG_IC_RECORD_ADDED'       => '<strong>IC Record added</strong><br />» %s (Character: %s)',
    'LOG_IC_RECORD_EDITED'      => '<strong>IC Record edited</strong><br />» %s (Character: %s)',
    'LOG_IC_RECORD_DELETED'     => '<strong>IC Record deleted</strong><br />» %s (Character: %s)',
));
