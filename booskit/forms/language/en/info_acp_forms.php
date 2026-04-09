<?php
/**
 *
 * @package booskit/forms
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
	'ACP_BOOSKIT_FORMS_TITLE'			=> 'Custom Forms',
	'ACP_BOOSKIT_FORMS_EXPLAIN'			=> 'Manage custom forms that users can fill out to automatically create posts.',
	'ACP_BOOSKIT_FORMS_MANAGE'			=> 'Manage Forms',

	'FORM_NAME'							=> 'Form Name',
	'FORM_SLUG'							=> 'URL Variable (Slug)',
	'FORM_SLUG_EXPLAIN'					=> 'Used in the URL: /forms/YOUR_SLUG. Leave empty to use form ID.',
	'FORM_GROUPS'						=> 'Restrict to Groups',
	'FORM_GROUPS_EXPLAIN'				=> 'Comma-separated group IDs allowed to access this form. Leave empty for public access.',
	'FORM_PUBLIC'						=> 'Allow Public Access',
	'FORM_PUBLIC_EXPLAIN'				=> 'If enabled, non-logged in users (guests) can view and submit this form.',
	'FORM_DESC'							=> 'Form Description',
	'FORM_HEADER'						=> 'Form Header/Instructions',
	'FORM_SUBJECT_TPL'					=> 'Subject Template',
	'FORM_SUBJECT_TPL_EXPLAIN'			=> 'The subject of the created topic. Use {{variable_name}} for field values. For checkboxes, you can use loops: {{#variable_name}}{{value}}{{/variable_name}}.',
	'FORM_TEMPLATE'						=> 'Body Template',
	'FORM_TEMPLATE_EXPLAIN'				=> 'The content of the created post. Use {{variable_name}} for field values.',
	'FORM_LOOPS_HELP'					=> '<strong>Loop Usage for Checkboxes:</strong><br />For fields like checkboxes that can have multiple values, you can use loops in your subject or body template:<br /><code>{{#variable_name}} - {{label}}<br />{{/variable_name}}</code><br />Inside the loop, you can use <code>{{label}}</code> for the display name and <code>{{value}}</code> for the raw value.',
	
	'FORM_FIELDS'						=> 'Form Fields',
	'FORM_FIELDS_EXPLAIN'				=> 'Define the fields for this form.',
	'FIELD_LABEL'						=> 'Label',
	'FIELD_NAME'						=> 'Variable Name',
	'FIELD_DESC'						=> 'Description',
	'FIELD_TYPE'						=> 'Type',
	'FIELD_OPTIONS'						=> 'Options',
	'FIELD_REQUIRED'					=> 'Required',
	'ADD_FIELD'							=> 'Add Field',
	'MOVE'								=> 'Move',
	'MOVE_UP'							=> 'Move Up',
	'MOVE_DOWN'							=> 'Move Down',

	'FORUM_ID'							=> 'Target Forum ID',
	'POSTER_ID'							=> 'Poster ID',
	'POSTER_ID_EXPLAIN'					=> 'User ID to post as. Set to 0 to post as the submitting user.',
	'ENABLED'							=> 'Enabled',

	'ADD_FORM'							=> 'Add New Form',
	'FORM_SETTINGS'						=> 'Form Settings',
	'NO_FORMS'							=> 'No forms have been created yet.',
	'FORM_DELETED'						=> 'Form deleted successfully.',

	'LOG_BOOSKIT_FORM_ADDED'			=> '<strong>Added new custom form</strong><br />» %s',
	'LOG_BOOSKIT_FORM_UPDATED'			=> '<strong>Updated custom form</strong><br />» %s',
	'LOG_BOOSKIT_FORM_DELETED'			=> '<strong>Deleted custom form</strong><br />» ID: %s',

	// Re-add potentially missing common keys if needed by template
	'BACK'								=> 'Back',
	'ACTION'							=> 'Action',
	'YES'								=> 'Yes',
	'NO'								=> 'No',
	'SUBMIT'							=> 'Submit',
	'RESET'								=> 'Reset',
	'DELETE'							=> 'Delete',

	// Award title was used in template by mistake or copy-paste, ensuring it works
	'ACP_BOOSKIT_AWARDS_TITLE'			=> 'Custom Forms',
));
