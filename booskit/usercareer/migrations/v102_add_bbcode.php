<?php
/**
 *
 * @package booskit/usercareer
 * @license MIT
 *
 */

namespace booskit\usercareer\migrations;

class v102_add_bbcode extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\booskit\usercareer\migrations\v101_add_view_access');
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'booskit_career_notes' => array(
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
				$this->table_prefix . 'booskit_career_notes' => array(
					'bbcode_uid',
					'bbcode_bitfield',
					'bbcode_options',
				),
			),
		);
	}
}
