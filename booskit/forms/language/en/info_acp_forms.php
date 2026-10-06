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
	'FORM_SUBJECT_TPL_EXPLAIN'			=> 'The subject of the created topic. Use {{variable_name}} for field values. For options/radios/selects/checkboxes, you can iterate all options with {{$variable_name}} or loops.',
	'FORM_TEMPLATE'						=> 'Body Template',
	'FORM_TEMPLATE_EXPLAIN'				=> 'The content of the created post. Use {{variable_name}} for field values, loops, and {{$variable_name}} to iterate options with selected indicators.',
	'FORM_VARS_GUIDE_TITLE'			=> 'Template Variable Guide',
	'FORM_VARS_GUIDE_INTRO'			=> 'Use variables in your Subject and Body templates to output user submitted data. Below is how each variable type works:',
	'FORM_VARS_SINGLE_TITLE'		=> 'Single Variable (Standard Fields)',
	'FORM_VARS_SINGLE_DESC'			=> 'Outputs the selected value or entered text from any single field (Text, Textarea, Select, Radio, etc.).',
	'FORM_VARS_CHECKBOX_TITLE'		=> 'Checkbox Field',
	'FORM_VARS_CHECKBOX_DESC'		=> 'Outputs multiple selected options. Use simple tag for comma-separated values, or a loop to format as a list with {{label}} and {{value}}.',
	'FORM_VARS_OPTIONS_TITLE'		=> 'Iterate All Options (Radios, Selects & Checkboxes)',
	'FORM_VARS_OPTIONS_DESC'		=> 'Iterate through all options of a field to show selected vs unselected indicators (e.g. [X] vs [ ]). Use {{$variable_name}} with {selected}...{/selected}, {not_selected}...{/not_selected}, and {option}.',
	'FORM_VARS_LOOPS_TITLE'			=> 'Field & Global Loops',
	'FORM_VARS_LOOPS_DESC'			=> 'Use {{#fields}}...{{/fields}} with {{name}}, {{label}}, {{value}} to automatically list all filled fields in the form.',
	'FORM_VARS_GROUP_TITLE'			=> 'Standard Group',
	'FORM_VARS_GROUP_DESC'			=> 'Standard input groups organize fields into columns visually. Use child variable names directly inside your body template.',
	'FORM_VARS_MULTI_GROUP_TITLE'	=> 'Multi Group (Repeater)',
	'FORM_VARS_MULTI_GROUP_DESC'	=> 'Allows users to dynamically add multiple rows of responses. Loop over the Group Name to output each row.',
	
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
	'LOG_BOOSKIT_FORM_WEBHOOK_FAILED'	=> '<strong>Custom form webhook delivery failed</strong><br />» Form: %s<br />» URL: %s<br />» Error: %s',

	'WEBHOOK'							=> 'Webhook',
	'WEBHOOK_SETTINGS'					=> 'Webhook Settings',
	'WEBHOOK_ENABLED'					=> 'Enable Webhook',
	'WEBHOOK_ENABLED_EXPLAIN'			=> 'Send submission data to one or more external webhooks (Discord, Slack, Zapier, custom APIs).',
	'WEBHOOK_URL'						=> 'Webhook URL(s)',
	'WEBHOOK_URL_EXPLAIN'				=> 'The webhook endpoint URL. Supports Discord webhooks or generic HTTP JSON webhooks. Enter multiple URLs separated by newlines.',
	'WEBHOOK_TEMPLATE'					=> 'Webhook JSON Template',
	'WEBHOOK_TEMPLATE_EXPLAIN'			=> 'Customize the JSON payload sent on submission. Supports {{variable_name}}, {{FIELDS_JSON}}, {{DISCORD_FIELDS_JSON}}, and template loops. Leave blank to automatically format for Discord or generic JSON.',
	'WEBHOOK_TEMPLATE_PLACEHOLDER'		=> 'Leave blank for automatic formatting, or enter custom JSON structure...',
	'WEBHOOK_PRESET_DISCORD'			=> 'Discord Embed Preset',
	'WEBHOOK_PRESET_DISCORD_SIMPLE'		=> 'Discord Simple Message Preset',
	'WEBHOOK_PRESET_GENERIC'			=> 'Generic JSON Preset',
	'WEBHOOK_CLEAR'						=> 'Clear Template',
	'WEBHOOK_VARS_GUIDE_TITLE'			=> 'Webhook JSON Variable Guide',
	'WEBHOOK_VARS_GUIDE_INTRO'			=> 'Use variables in your webhook JSON template. Special JSON characters like quotes and newlines in values are safely escaped automatically.',
	'WEBHOOK_VAR_DISCORD_FIELDS_TITLE'	=> 'Discord Embed Fields (Pre-formatted)',
	'WEBHOOK_VAR_DISCORD_FIELDS_DESC'	=> 'Ready-to-use array of Discord embed field objects [{"name": "...", "value": "..."}]. Use unquoted as the value of "fields".',
	'WEBHOOK_VAR_FIELDS_JSON_TITLE'		=> 'Generic Fields JSON (Pre-formatted)',
	'WEBHOOK_VAR_FIELDS_JSON_DESC'		=> 'A JSON object containing all filled form fields {"field_name": "value"}. Use unquoted.',
	'WEBHOOK_VAR_SYSTEM_TITLE'			=> 'System & Forum Variables',
	'WEBHOOK_VAR_SYSTEM_DESC'			=> 'Include form and post metadata like {{FORM_NAME}}, {{FORM_ID}}, {{USERNAME}}, {{USER_ID}}, {{POST_ID}}, {{TOPIC_ID}}, {{POST_URL}}, {{DATE}}, {{TIME}}, {{ISO_DATE}}.',
	'WEBHOOK_VAR_FIELD_TITLE'			=> 'Custom Field Variables',
	'WEBHOOK_VAR_FIELD_DESC'			=> 'Reference specific fields by their variable name: "{{your_field_name}}".',

	// Re-add potentially missing common keys if needed by template
	'BACK'								=> 'Back',
	'ACTION'							=> 'Action',
	'YES'								=> 'Yes',
	'NO'								=> 'No',
	'SUBMIT'							=> 'Submit',
	'RESET'								=> 'Reset',
	'DELETE'							=> 'Delete',
));

