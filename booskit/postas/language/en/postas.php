<?php
/**
 *
 * Post As. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'POSTAS_YOURSELF' => 'Yourself',
	'POSTAS_SELECT' => 'Post as:',
	'POSTAS_EXPLAIN' => 'Select which character to post as. This will change the displayed name color and rank image.',
	'POSTAS_REVERT_TO_ORIGINAL' => 'Revert to original poster',
	'POSTAS_REVERT_EXPLAIN' => 'Check this box to remove the alternate character and revert the post to the original poster.',
]);
