<?php
/**
 *
 * @package booskit/disciplinary
 * @license MIT
 *
 */

namespace booskit\disciplinary\migrations;

class v109_fix_archive_reason_default extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\booskit\disciplinary\migrations\v108_add_clipboard_config'];
	}

	public function update_schema()
	{
		return [
			'change_columns' => [
				$this->table_prefix . 'booskit_disciplinary_users' => [
					'archive_reason' => ['TEXT_UNI', null],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'change_columns' => [
				$this->table_prefix . 'booskit_disciplinary_users' => [
					'archive_reason' => ['TEXT_UNI', null],
				],
			],
		];
	}
}
