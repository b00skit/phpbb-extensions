<?php
/**
 *
 * @package booskit/disciplinary
 * @license MIT
 *
 */

namespace booskit\disciplinary\migrations;

class v102_add_bbcode extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\booskit\disciplinary\migrations\v101_access_levels');
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'booskit_disciplinary_users' => array(
					'reason_bbcode_uid'      => array('VCHAR:8', ''),
					'reason_bbcode_bitfield' => array('VCHAR:255', ''),
					'reason_bbcode_options'  => array('UINT', 7),
					'evidence_bbcode_uid'      => array('VCHAR:8', ''),
					'evidence_bbcode_bitfield' => array('VCHAR:255', ''),
					'evidence_bbcode_options'  => array('UINT', 7),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'booskit_disciplinary_users' => array(
					'reason_bbcode_uid',
					'reason_bbcode_bitfield',
					'reason_bbcode_options',
					'evidence_bbcode_uid',
					'evidence_bbcode_bitfield',
					'evidence_bbcode_options',
				),
			),
		);
	}
}
