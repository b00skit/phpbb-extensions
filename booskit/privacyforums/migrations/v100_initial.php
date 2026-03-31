<?php
/**
 *
 * @package booskit/privacyforums
 * @license MIT
 *
 */

namespace booskit\privacyforums\migrations;

class v100_initial extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v330\v330');
	}

	public function update_data()
	{
		return array(
			array('permission.add', array('f_view_others', false)),
			array('permission.add', array('f_post_others', false)),
			array('permission.permission_set', array('REGISTERED', 0, 'f_view_others', 'group', true)),
			array('permission.permission_set', array('REGISTERED', 0, 'f_post_others', 'group', true)),
			array('permission.permission_set', array('GUESTS', 0, 'f_view_others', 'group', true)),
			array('permission.permission_set', array('GUESTS', 0, 'f_post_others', 'group', true)),
		);
	}
}
