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

	'BOOSKIT_CAREER_PERM_SYSTEM' => 'Permission System',
	'BOOSKIT_CAREER_PERM_SYSTEM_EXPLAIN' => 'Select whether to use legacy access role levels or the permission groups system.',
	'BOOSKIT_CAREER_PERM_SYSTEM_LEGACY' => 'Legacy Access Levels',
	'BOOSKIT_CAREER_PERM_SYSTEM_GROUPS' => 'Permission Groups',

	'PERMISSION_GROUPS' => 'Permission Groups',
	'PERMISSION_GROUPS_EXPLAIN' => 'Configure permission groups to control view and submission authority over user groups.',
	'PERMISSION_GROUP_NAME' => 'Permission Group Name',
	'APPLIES_TO_GROUPS' => 'Applies to User Groups',
	'APPLIES_TO_GROUPS_EXPLAIN' => 'Users belonging to any selected group will be granted these permissions.',
	'POWER_OVER_GROUPS' => 'Power Over Target Groups',
	'POWER_OVER_GROUPS_EXPLAIN' => 'Select which target user groups this permission group has authority over.',
	'ALL_GROUPS' => 'All Groups',
	'SELF_GROUP' => 'Self Only',
	'EXCLUDE_GROUPS' => 'Exclude Target Groups',
	'EXCLUDE_GROUPS_EXPLAIN' => 'Target users in these groups will be exempt from this permission group.',
	'ENTRY_PERMISSIONS' => 'Permissions',
	'PERM_VIEW' => 'View',
	'PERM_SUBMIT' => 'Submit',
	'PERM_EDIT_OWN' => 'Edit Own',
	'PERM_DELETE_OWN' => 'Delete Own',
	'PERM_EDIT_ALL' => 'Edit All',
	'PERM_DELETE_ALL' => 'Delete All',
	'ADD_PERMISSION_GROUP' => 'Add Permission Group',

	'BOOSKIT_CAREER_SOURCE' => 'Definitions Source',
	'BOOSKIT_CAREER_SOURCE_EXPLAIN' => 'Choose whether to load career definitions from an external JSON URL or manage them locally.',
	'BOOSKIT_CAREER_SOURCE_URL' => 'External JSON URL',
	'BOOSKIT_CAREER_SOURCE_LOCAL' => 'Local Settings (Database)',

	'BOOSKIT_CAREER_JSON_URL' => 'Definitions JSON URL',
	'BOOSKIT_CAREER_JSON_URL_EXPLAIN' => 'URL to the JSON file containing career type definitions.',

	'CAREER_JSON_URL' => 'Definitions JSON URL',
	'CAREER_JSON_URL_EXPLAIN' => 'URL to the JSON file containing career type definitions.',

	'BOOSKIT_CAREER_ACCESS_VIEW' => 'Local View Access Group IDs',
	'BOOSKIT_CAREER_ACCESS_VIEW_EXPLAIN' => 'Comma separated list of Group IDs.',
	'BOOSKIT_CAREER_ACCESS_VIEW_GLOBAL' => 'Global View Access Group IDs',
	'BOOSKIT_CAREER_ACCESS_VIEW_GLOBAL_EXPLAIN' => 'Comma separated list of Group IDs.',
	'BOOSKIT_CAREER_ACCESS_L1' => 'Level 1 Access Group IDs',
	'BOOSKIT_CAREER_ACCESS_L1_EXPLAIN' => 'Comma separated list of Group IDs.',
	'BOOSKIT_CAREER_ACCESS_L2' => 'Level 2 Access Group IDs',
	'BOOSKIT_CAREER_ACCESS_L2_EXPLAIN' => 'Comma separated list of Group IDs.',
	'BOOSKIT_CAREER_ACCESS_L3' => 'Level 3 Access Group IDs',
	'BOOSKIT_CAREER_ACCESS_L3_EXPLAIN' => 'Comma separated list of Group IDs.',
	'BOOSKIT_CAREER_ACCESS_FULL' => 'Full Access Group IDs',
	'BOOSKIT_CAREER_ACCESS_FULL_EXPLAIN' => 'Comma separated list of Group IDs.',

	'CAREER_ACCESS_VIEW' => 'Local View Access Group IDs',
	'CAREER_ACCESS_VIEW_GLOBAL' => 'Global View Access Group IDs',
	'CAREER_ACCESS_L1' => 'Level 1 Access Group IDs',
	'CAREER_ACCESS_L2' => 'Level 2 Access Group IDs',
	'CAREER_ACCESS_L3' => 'Level 3 Access Group IDs',
	'CAREER_ACCESS_FULL' => 'Full Access Group IDs',
	'CAREER_ACCESS_LEVEL_EXPLAIN' => 'Comma separated list of Group IDs.',

	'RULES' => 'Ruleset',
	'RULES_EXPLAIN' => 'The message displayed at the top of the management form. BBCode is supported.',

	'BOOSKIT_CAREER_LOCAL_DEFINITIONS' => 'Local Definitions',
	'BOOSKIT_CAREER_LOCAL_DEFINITIONS_EXPLAIN' => 'Manage the career definitions here when "Local Settings" is selected.',

	'ID' => 'ID',
	'NAME' => 'Name',
	'DESCRIPTION' => 'Description',
	'ICON' => 'Icon (FontAwesome)',
	'ACTION' => 'Action',
	'ADD' => 'Add',
	'UPDATE' => 'Update',
	'DELETE' => 'Delete',

	'LOG_CAREER_ADDED' => '<strong>Added career note to user</strong><br />» %s',
	'LOG_CAREER_EDITED' => '<strong>Edited career note for user</strong><br />» %s',
	'LOG_CAREER_DELETED' => '<strong>Deleted career note from user</strong><br />» %s',

	'PREGENERATED_NOTE_SETTINGS' => 'Pre-generated Note Settings',
	'ENABLE_NOTE_TEMPLATE' => 'Enable Pre-generated Notes',
	'NOTE_CUSTOM_FIELDS' => 'Note Template Custom Fields',
	'NOTE_CUSTOM_FIELDS_EXPLAIN' => 'Define custom fields that the user will be prompted to fill when creating a pre-generated note.',
	'NOTE_TEMPLATE_BODY' => 'Note Template Body',
	'NOTE_TEMPLATE_BODY_EXPLAIN' => 'Template used to transform custom field entries into the note description.',
	'MANDATORY' => 'Mandatory?',

	'PUBLIC_POST' => 'Public Post',
	'PUBLIC_POST_SETTINGS' => 'Public Post Settings',
	'ENABLE_PUBLIC_POSTING' => 'Enable Public Posting',
	'INHERIT_NOTE_FIELDS' => 'Inherit Note Template Fields',
	'INHERIT_NOTE_FIELDS_EXPLAIN' => 'When checked, the public post template will consume custom fields defined in Pre-generated Note Settings.',
	'INHERITED_FIELDS_ACTIVE' => 'Field Inheritance Active',
	'INHERITED_FIELDS_ACTIVE_EXPLAIN' => 'Public post custom fields are currently inherited from Pre-generated Note Fields.',
	'POSTER_ID' => 'Poster User ID',
	'FORUM_ID' => 'Target Forum ID',
	'CUSTOM_FIELDS' => 'Custom Fields',
	'CUSTOM_FIELDS_EXPLAIN' => 'Define additional fields that the user must fill out when creating a note of this type.',
	'FIELD_NAME' => 'Field Label',
	'FIELD_DESC' => 'Field Description',
	'DEFAULT_OPTIONS' => 'Default Value / Options',
	'VARIABLE' => 'Variable Name',
	'TYPE' => 'Type',
	'POST_TEMPLATE' => 'Post Template',
	'POST_TEMPLATE_EXPLAIN' => 'Define the Subject and Body of the public post. You can use the following default variables:',
	'CUSTOM_VARIABLES_EXPLAIN' => '<code>{#userGroup}</code> (User\'s primary group), <code>{#posterGroup}</code> (Poster\'s primary group). You can also use variables from your Custom Fields using <code>{@variable_name}</code>.',
	'SUBJECT' => 'Subject',
	'BODY' => 'Body',
	'ADD_FIELD' => 'Add Field',
	'EDIT' => 'Edit',

	'FORUM_GROUP_ACTIONS' => 'Forum Group Actions',
	'ENABLE_GROUP_ACTION' => 'Enable Forum Group Actions',
	'GROUPS_ADD' => 'Groups to Add',
	'GROUPS_ADD_EXPLAIN' => 'Comma separated list of Group IDs to add the user to.',
	'GROUPS_REMOVE' => 'Groups to Remove',
	'GROUPS_REMOVE_EXPLAIN' => 'Comma separated list of Group IDs to remove the user from. Use <code>*</code> to remove from all groups except Administrators and Global Moderators.',

	'AUTOMATION_SETTINGS' => 'Automation Settings',
	'AUTOMATION_SETTINGS_EXPLAIN' => 'Configure different automation settings for this career type. Each setting can add/remove groups, set primary group, and enable name changing.',
	'SETTING_NAME' => 'Setting Name',
	'REMOVE_ALL' => 'Remove All?',
	'PRIMARY_GROUP' => 'Primary Group ID',
	'CHANGE_NAME' => 'Change Name?',
	'ADD_AUTOMATION_SETTING' => 'Add Automation Setting',
));
