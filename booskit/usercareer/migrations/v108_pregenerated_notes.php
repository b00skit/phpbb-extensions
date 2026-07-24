<?php
/**
 *
 * @package booskit/usercareer
 * @license MIT
 *
 */

namespace booskit\usercareer\migrations;

class v108_pregenerated_notes extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\booskit\usercareer\migrations\v107_permission_groups'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'booskit_career_definitions' => [
					'enable_note_template'		=> ['TINT:1', 0],
					'inherit_note_fields'		=> ['TINT:1', 0],
					'note_template_body_tpl'	=> ['TEXT_UNI', ''],
					'note_template_fields'		=> ['TEXT_UNI', ''],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'booskit_career_definitions' => [
					'enable_note_template',
					'inherit_note_fields',
					'note_template_body_tpl',
					'note_template_fields',
				],
			],
		];
	}
}
