<?php
/**
 *
 * @package booskit/forms
 * @license MIT
 *
 */

namespace booskit\forms\migrations;

class v105_add_public_access extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\booskit\forms\migrations\v104_add_slug_and_groups');
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'booskit_forms' => array(
					'form_public' => array('BOOL', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'booskit_forms' => array(
					'form_public',
				),
			),
		);
	}
}
