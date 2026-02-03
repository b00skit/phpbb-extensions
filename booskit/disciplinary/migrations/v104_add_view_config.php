<?php
/**
 *
 * @package booskit/disciplinary
 * @license MIT
 *
 */

namespace booskit\disciplinary\migrations;

class v104_add_view_config extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\booskit\disciplinary\migrations\v103_add_local_definitions'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'booskit_disciplinary_definitions' => [
					'locally_viewable' => ['TINT:1', 0],
					'globally_viewable' => ['TINT:1', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'booskit_disciplinary_definitions' => [
					'locally_viewable',
					'globally_viewable',
				],
			],
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['booskit_disciplinary_access_view_local', '']],
			['config.add', ['booskit_disciplinary_access_view_exempted', '']],
			['config.add', ['booskit_disciplinary_access_view_limited', '']],
			['config.add', ['booskit_disciplinary_access_view_global', '']],
			['config.add', ['booskit_disciplinary_access_view_limited_map', '']],
		];
	}
}
