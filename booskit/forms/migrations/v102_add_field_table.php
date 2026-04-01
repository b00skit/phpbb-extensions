<?php
/**
 *
 * @package booskit/forms
 * @license MIT
 *
 */

namespace booskit\forms\migrations;

class v102_add_field_table extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\booskit\forms\migrations\v101_add_bbcode');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'booskit_form_fields' => array(
					'COLUMNS' => array(
						'field_id'			=> array('UINT', null, 'auto_increment'),
						'form_id'			=> array('UINT', 0),
						'field_label'		=> array('VCHAR:255', ''),
						'field_name'		=> array('VCHAR:255', ''),
						'field_type'		=> array('VCHAR:255', ''),
						'field_options'		=> array('TEXT_UNI', ''),
						'field_required'	=> array('BOOL', 0),
						'field_order'		=> array('UINT', 0),
					),
					'PRIMARY_KEY' => 'field_id',
					'KEYS' => array(
						'form_id' => array('INDEX', 'form_id'),
					),
				),
			),
			'drop_columns' => array(
				$this->table_prefix . 'booskit_forms' => array(
					'form_fields',
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'booskit_form_fields',
			),
			'add_columns' => array(
				$this->table_prefix . 'booskit_forms' => array(
					'form_fields' => array('TEXT_UNI', ''),
				),
			),
		);
	}
}
