<?php
/**
 *
 * @package booskit/disciplinary
 * @license MIT
 *
 */

namespace booskit\disciplinary\migrations;

class v106_add_edit_tracking extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'booskit_disciplinary_users', 'edited_by_user_id');
	}

	static public function depends_on()
	{
		return ['\booskit\disciplinary\migrations\v105_permission_groups'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'booskit_disciplinary_users' => [
					'edited_by_user_id' => ['UINT', 0],
					'last_edited_time'  => ['TIMESTAMP', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'booskit_disciplinary_users' => [
					'edited_by_user_id',
					'last_edited_time',
				],
			],
		];
	}
}
