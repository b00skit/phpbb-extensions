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
));
