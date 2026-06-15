<?php
/**
 *
 * @package booskit/usercareer
 * @license MIT
 *
 */

namespace booskit\usercareer\migrations;

class v106_add_automation_settings extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\booskit\usercareer\migrations\v105_add_group_actions'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'booskit_career_definitions' => [
					'automation_settings' => ['TEXT', ''],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'booskit_career_definitions' => [
					'automation_settings',
				],
			],
		];
	}
}
