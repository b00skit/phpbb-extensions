<?php
/**
 *
 * @package booskit/icdisciplinary
 * @license MIT
 *
 */

namespace booskit\icdisciplinary\migrations;

class v105_fix_archive_reason_default extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\booskit\icdisciplinary\migrations\v104_add_clipboard_config'];
	}

	public function update_schema()
	{
		return [
			'change_columns' => [
				$this->table_prefix . 'booskit_ic_records' => [
					'archive_reason' => ['TEXT_UNI', null],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'change_columns' => [
				$this->table_prefix . 'booskit_ic_records' => [
					'archive_reason' => ['TEXT_UNI', null],
				],
			],
		];
	}
}
