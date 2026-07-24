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
	'CAREER_ADD_NOTE' => 'Add Career Note',
	'CAREER_EDIT_NOTE' => 'Edit Career Note',
	'CAREER_DELETE_NOTE' => 'Delete Career Note',
	'CAREER_NOTE_ADDED' => 'Career note added successfully.',
	'CAREER_NOTE_UPDATED' => 'Career note updated successfully.',
	'CAREER_NOTE_DELETED' => 'Career note deleted successfully.',
	'NO_CAREER_TYPE_SELECTED' => 'No career type selected.',
	'DELETE_CAREER_CONFIRM' => 'Are you sure you want to delete this career note?',
	'NO_CAREER_NOTE_RECORD' => 'Career note not found.',
	'CAREER_TYPE' => 'Type',
	'CAREER_DATE' => 'Date',
	'CAREER_DESCRIPTION' => 'Description',
	'CAREER_TIMELINE' => 'Career Timeline',
	'VIEW_FULL_TIMELINE' => 'View Full Timeline',
	'CAREER_TIMELINE_FOR' => 'Career Timeline for %s',
	'NO_ENTRIES' => 'No entries found.',
	'BACK_TO_PROFILE' => 'Back to Profile',

	'NOTE_INPUT_MODE' => 'Note Type Mode',
	'NOTE_MODE_USER' => 'User Note (Custom Text)',
	'NOTE_MODE_PREGENERATED' => 'Pre-generated Note (Form Template)',
	'PREGENERATED_NOTE_FORM' => 'Pre-generated Note Form',
	'MANDATORY_FIELD_REQUIRED' => 'The field "%s" is mandatory and cannot be left blank.',
	'INHERITED_PUBLIC_POST_USER_EXPLAIN' => 'Public post notice will automatically inherit and use the information provided in the Pre-generated Note Form.',

	'MAKE_PUBLIC_POST' => 'Make Public Notice Post',
	'PUBLIC_POST_SETTINGS' => 'Public Post Settings',

	'FORUM_GROUP_ACTIONS' => 'Forum Group Actions',
	'ENABLE_GROUP_ACTION' => 'Execute Forum Group Actions',
	'GROUPS_ADD' => 'Groups Added',
	'GROUPS_REMOVE' => 'Groups Removed',
	'SELECT_AUTOMATION_SETTING' => 'Select Automation Action',
	'NEW_USERNAME' => 'New Username',
	'PRIMARY_GROUP' => 'Primary Group',
));
