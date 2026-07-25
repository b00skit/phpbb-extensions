<?php
/**
 *
 * @package booskit/usercommandcenter
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
	'ACP_BOOSKIT_UCC_TITLE' => 'User Command Center',
	'LOG_UCC_VIEWED' => '<strong>Viewed User Command Center dashboard</strong>',
	'LOG_UCC_MODULE_VIEWED' => '<strong>Viewed User Command Center module</strong><br />» %s',
));
