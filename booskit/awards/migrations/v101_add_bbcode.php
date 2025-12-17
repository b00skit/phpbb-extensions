<?php
/**
 *
 * @package booskit/awards
 * @license MIT
 *
 */

namespace booskit\awards\migrations;

class v101_add_bbcode extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\booskit\awards\migrations\v100_initial');
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'booskit_awards_users' => array(
					'bbcode_uid'      => array('VCHAR:8', ''),
					'bbcode_bitfield' => array('VCHAR:255', ''),
					'bbcode_options'  => array('UINT', 7),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'booskit_awards_users' => array(
					'bbcode_uid',
					'bbcode_bitfield',
					'bbcode_options',
				),
			),
		);
	}
}
