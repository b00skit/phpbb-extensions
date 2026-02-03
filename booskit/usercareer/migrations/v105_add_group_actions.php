<?php
/**
 *
 * @package booskit/usercareer
 * @license MIT
 *
 */

namespace booskit\usercareer\migrations;

class v105_add_group_actions extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\booskit\usercareer\migrations\v104_add_public_posting'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'booskit_career_definitions' => [
					'enable_group_action' => ['TINT:1', 0],
					'group_action_add' => ['TEXT', ''],
					'group_action_remove' => ['TEXT', ''],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'booskit_career_definitions' => [
					'enable_group_action',
					'group_action_add',
					'group_action_remove',
				],
			],
		];
	}
}
