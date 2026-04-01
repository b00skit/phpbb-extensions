<?php
/**
 *
 * @package booskit/forms
 * @license MIT
 *
 */

namespace booskit\forms\migrations;

class v103_add_field_desc extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\booskit\forms\migrations\v102_add_field_table');
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'booskit_form_fields' => array(
					'field_desc' => array('TEXT_UNI', ''),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'booskit_form_fields' => array(
					'field_desc',
				),
			),
		);
	}
}
