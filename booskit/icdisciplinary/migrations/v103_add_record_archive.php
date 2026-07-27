<?php
/**
 *
 * @package booskit/icdisciplinary
 * @license MIT
 *
 */

namespace booskit\icdisciplinary\migrations;

class v103_add_record_archive extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'booskit_ic_records', 'is_archived');
	}

	static public function depends_on()
	{
		return ['\booskit\icdisciplinary\migrations\v102_add_edit_tracking'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'booskit_ic_records' => [
					'is_archived'         => ['TINT:1', 0],
					'archive_reason'      => ['TEXT_UNI', ''],
					'archived_by_user_id' => ['UINT', 0],
					'archive_date'        => ['TIMESTAMP', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'booskit_ic_records' => [
					'is_archived',
					'archive_reason',
					'archived_by_user_id',
					'archive_date',
				],
			],
		];
	}
}
