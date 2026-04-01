<?php
/**
 *
 * @package booskit/forms
 * @license MIT
 *
 */

namespace booskit\forms\migrations;

class v101_add_bbcode extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\booskit\forms\migrations\v100_initial');
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'booskit_forms' => array(
					'form_desc_uid'			=> array('VCHAR:8', ''),
					'form_desc_bitfield'	=> array('VCHAR:255', ''),
					'form_desc_options'		=> array('UINT:11', 7),
					'form_header_uid'		=> array('VCHAR:8', ''),
					'form_header_bitfield'	=> array('VCHAR:255', ''),
					'form_header_options'	=> array('UINT:11', 7),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'booskit_forms' => array(
					'form_desc_uid',
					'form_desc_bitfield',
					'form_desc_options',
					'form_header_uid',
					'form_header_bitfield',
					'form_header_options',
				),
			),
		);
	}
}
