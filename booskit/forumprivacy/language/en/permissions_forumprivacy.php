<?php
/**
 *
 * @package booskit/forumprivacy
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
	'ACL_F_VIEW_OTHERS_TOPICS'	=> 'Can view other’s topics',
	'ACL_F_POST_OTHERS_TOPICS'	=> 'Can post on other’s topics',
	'ACL_F_SEARCH_OTHERS_TOPICS' => 'Can search other’s topics',
));
