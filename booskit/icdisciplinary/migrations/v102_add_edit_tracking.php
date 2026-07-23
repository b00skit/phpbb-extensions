<?php
/**
 *
 * @package booskit/icdisciplinary
 * @license MIT
 *
 */

namespace booskit\icdisciplinary\migrations;

class v102_add_edit_tracking extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'booskit_ic_records', 'edited_by_user_id');
	}

	static public function depends_on()
	{
		return ['\booskit\icdisciplinary\migrations\v101_permission_groups'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'booskit_ic_records' => [
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
				$this->table_prefix . 'booskit_ic_records' => [
					'edited_by_user_id',
					'last_edited_time',
				],
			],
		];
	}
}
