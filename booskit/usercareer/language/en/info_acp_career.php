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

	'PUBLIC_POST' => 'Public Post',
	'PUBLIC_POST_SETTINGS' => 'Public Post Settings',
	'ENABLE_PUBLIC_POSTING' => 'Enable Public Posting',
	'POSTER_ID' => 'Poster User ID',
	'FORUM_ID' => 'Target Forum ID',
	'CUSTOM_FIELDS' => 'Custom Fields',
	'CUSTOM_FIELDS_EXPLAIN' => 'Define additional fields that the user must fill out when creating a note of this type.',
	'FIELD_NAME' => 'Field Label',
	'FIELD_DESC' => 'Field Description',
	'PLACEHOLDER' => 'Placeholder',
	'VARIABLE' => 'Variable Name',
	'TYPE' => 'Type',
	'POST_TEMPLATE' => 'Post Template',
	'POST_TEMPLATE_EXPLAIN' => 'Define the Subject and Body of the public post. You can use the following default variables:',
	'CUSTOM_VARIABLES_EXPLAIN' => 'You can also use variables from your Custom Fields using <code>{@variable_name}</code>.',
	'SUBJECT' => 'Subject',
	'BODY' => 'Body',
	'ADD_FIELD' => 'Add Field',
	'EDIT' => 'Edit',
));
