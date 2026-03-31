<?php
/**
 *
 * @package booskit/forumprivacy
 * @license MIT
 *
 */

namespace booskit\forumprivacy\migrations;

class v100_initial extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v330\v330');
	}

	public function update_data()
	{
		return array(
			// Add permissions
			array('permission.add', array('f_view_others_topics', false)),
			array('permission.add', array('f_post_others_topics', false)),
			array('permission.add', array('f_search_others_topics', false)),

			// Set defaults for roles
			array('permission.permission_set', array('ROLE_FORUM_STANDARD', 'f_view_others_topics', 'role', true)),
			array('permission.permission_set', array('ROLE_FORUM_STANDARD', 'f_post_others_topics', 'role', true)),
			array('permission.permission_set', array('ROLE_FORUM_STANDARD', 'f_search_others_topics', 'role', true)),

			array('permission.permission_set', array('ROLE_FORUM_LIMITED', 'f_view_others_topics', 'role', true)),
			array('permission.permission_set', array('ROLE_FORUM_LIMITED', 'f_post_others_topics', 'role', true)),
			array('permission.permission_set', array('ROLE_FORUM_LIMITED', 'f_search_others_topics', 'role', true)),

			array('permission.permission_set', array('ROLE_FORUM_POLLS', 'f_view_others_topics', 'role', true)),
			array('permission.permission_set', array('ROLE_FORUM_POLLS', 'f_post_others_topics', 'role', true)),
			array('permission.permission_set', array('ROLE_FORUM_POLLS', 'f_search_others_topics', 'role', true)),

			array('permission.permission_set', array('ROLE_FORUM_LIMITED_POLLS', 'f_view_others_topics', 'role', true)),
			array('permission.permission_set', array('ROLE_FORUM_LIMITED_POLLS', 'f_post_others_topics', 'role', true)),
			array('permission.permission_set', array('ROLE_FORUM_LIMITED_POLLS', 'f_search_others_topics', 'role', true)),

			array('permission.permission_set', array('ROLE_FORUM_FULL', 'f_view_others_topics', 'role', true)),
			array('permission.permission_set', array('ROLE_FORUM_FULL', 'f_post_others_topics', 'role', true)),
			array('permission.permission_set', array('ROLE_FORUM_FULL', 'f_search_others_topics', 'role', true)),
		);
	}
}
