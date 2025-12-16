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
));
