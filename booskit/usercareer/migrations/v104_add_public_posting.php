<?php
/**
 *
 * @package booskit/usercareer
 * @license MIT
 *
 */

namespace booskit\usercareer\migrations;

class v104_add_public_posting extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\booskit\usercareer\migrations\v103_add_local_definitions'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'booskit_career_definitions' => [
					'enable_public_posting' => ['TINT:1', 0],
					'public_posting_poster_id' => ['UINT', 0],
					'public_posting_forum_id' => ['UINT', 0],
					'public_posting_subject_tpl' => ['VCHAR:255', ''],
					'public_posting_body_tpl' => ['TEXT', ''],
					'public_posting_fields' => ['TEXT', ''],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'booskit_career_definitions' => [
					'enable_public_posting',
					'public_posting_poster_id',
					'public_posting_forum_id',
					'public_posting_subject_tpl',
					'public_posting_body_tpl',
					'public_posting_fields',
				],
			],
		];
	}
}
