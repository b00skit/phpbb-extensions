<?php
/**
 *
 * @package booskit/threadprefixes
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
	'ACP_BOOSKIT_THREADPREFIXES_TITLE'		=> 'Thread Prefixes',
	'ACP_BOOSKIT_THREADPREFIXES_SETTINGS'	=> 'Settings',
	'ACP_BOOSKIT_THREADPREFIXES_EXPLAIN'	=> 'Manage thread prefixes/tags, their styling, and which forums they are bound to.',

	'BOOSKIT_THREADPREFIXES_CURRENT_TAGS'	=> 'Current Prefixes',
	'BOOSKIT_THREADPREFIXES_NO_TAGS'		=> 'No prefixes configured yet.',
	'BOOSKIT_THREADPREFIXES_NO_FORUMS'		=> 'Not bound to any forums (won\'t display anywhere).',
	'PREVIEW'								=> 'Preview',
	'TEXT'									=> 'Text',
	'FORUMS'								=> 'Allowed Forums',

	'BOOSKIT_THREADPREFIXES_ADD_TAG'		=> 'Add Prefix',
	'BOOSKIT_THREADPREFIXES_EDIT_TAG'		=> 'Edit Prefix',
	'BOOSKIT_THREADPREFIXES_TAG_TEXT'		=> 'Prefix Text',
	'BOOSKIT_THREADPREFIXES_TAG_TEXT_EXPLAIN' => 'The text that will show inside the pill (e.g., SOLVED, QUESTION, INFO).',
	'BOOSKIT_THREADPREFIXES_TAG_COLOR'		=> 'Text Color',
	'BOOSKIT_THREADPREFIXES_TAG_COLOR_EXPLAIN' => 'Hex color code of the pill text.',
	'BOOSKIT_THREADPREFIXES_TAG_BG_COLOR'	=> 'Background Color',
	'BOOSKIT_THREADPREFIXES_TAG_BG_COLOR_EXPLAIN' => 'Hex color code of the pill background.',
	'BOOSKIT_THREADPREFIXES_BIND_FORUMS'	=> 'Bind to Forums',
	'BOOSKIT_THREADPREFIXES_BIND_FORUMS_EXPLAIN' => 'Select the forums where users can apply this prefix. Ctrl-click to select multiple.',
	
	'BOOSKIT_THREADPREFIXES_TEXT_REQUIRED'	=> 'Prefix text cannot be empty!',
	'LOG_BOOSKIT_THREADPREFIXES_TAG_ADDED'	=> '<strong>Thread Prefixes:</strong> Added prefix "%s"',
	'LOG_BOOSKIT_THREADPREFIXES_TAG_UPDATED'=> '<strong>Thread Prefixes:</strong> Updated prefix "%s"',
	'LOG_BOOSKIT_THREADPREFIXES_TAG_DELETED'=> '<strong>Thread Prefixes:</strong> Deleted prefix "%s"',
));
