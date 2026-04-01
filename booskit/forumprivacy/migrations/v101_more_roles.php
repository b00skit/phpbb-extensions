<?php
/**
 *
 * @package booskit/forumprivacy
 * @license MIT
 *
 */

namespace booskit\forumprivacy\migrations;

class v101_more_roles extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\booskit\forumprivacy\migrations\v100_initial');
	}

	public function update_data()
	{
		return array(
			// Read Only Access
			array('permission.permission_set', array('ROLE_FORUM_READONLY', 'f_view_others_topics', 'role', true)),
			array('permission.permission_set', array('ROLE_FORUM_READONLY', 'f_search_others_topics', 'role', true)),

			// On Moderation Queue
			array('permission.permission_set', array('ROLE_FORUM_ONQUEUE', 'f_view_others_topics', 'role', true)),
			array('permission.permission_set', array('ROLE_FORUM_ONQUEUE', 'f_post_others_topics', 'role', true)),
			array('permission.permission_set', array('ROLE_FORUM_ONQUEUE', 'f_search_others_topics', 'role', true)),

			// Bot Access
			array('permission.permission_set', array('ROLE_FORUM_BOT', 'f_search_others_topics', 'role', true)),

			// Newly Registered User Access
			array('permission.permission_set', array('ROLE_FORUM_NEW_MEMBER', 'f_view_others_topics', 'role', true)),
			array('permission.permission_set', array('ROLE_FORUM_NEW_MEMBER', 'f_post_others_topics', 'role', true)),
			array('permission.permission_set', array('ROLE_FORUM_NEW_MEMBER', 'f_search_others_topics', 'role', true)),
		);
	}
}
