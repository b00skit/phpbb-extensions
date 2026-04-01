<?php
/**
 *
 * @package booskit/forms
 * @license MIT
 *
 */

namespace booskit\forms\migrations;

class v104_add_slug_and_groups extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\booskit\forms\migrations\v103_add_field_desc');
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'booskit_forms' => array(
					'form_slug'		=> array('VCHAR:255', ''),
					'form_groups'	=> array('VCHAR:255', ''),
				),
			),
			'add_unique_index' => array(
				$this->table_prefix . 'booskit_forms' => array(
					'form_slug' => array('form_slug'),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'booskit_forms' => array(
					'form_slug',
					'form_groups',
				),
			),
		);
	}
}
