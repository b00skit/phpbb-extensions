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
	'FORM_NOT_FOUND'			=> 'The requested form could not be found.',
	'FORM_NOT_AUTHORIZED'		=> 'You are not authorized to access this form.',
	'LOGIN_REQUIRED_FOR_FORM'	=> 'You must be logged in to submit this form.',
	'FIELD_REQUIRED'			=> 'The field "%s" is required.',
	'FORM_SUBMITTED_SUCCESS'	=> 'The form has been submitted successfully and a post has been created.',
	'BACK_TO_FORM'				=> 'Back to the form',
	'GO_BACK'					=> 'Go back',
	'PREVIEW'					=> 'Preview',
	'TOPIC_SUBJECT'				=> 'Topic Subject',
	'REQUIRED_FIELDS_EXPLAIN'	=> 'Denotes a required field',
));
