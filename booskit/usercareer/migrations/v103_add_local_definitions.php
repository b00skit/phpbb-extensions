<?php
/**
 *
 * @package booskit/usercareer
 * @license MIT
 *
 */

namespace booskit\usercareer\migrations;

class v103_add_local_definitions extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_career_source']);
	}

	static public function depends_on()
	{
		return ['\booskit\usercareer\migrations\v102_add_global_view_access'];
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'booskit_career_definitions' => [
					'COLUMNS' => [
						'def_id'		=> ['UINT', null, 'auto_increment'],
						'career_id'		=> ['VCHAR:255', ''],
						'career_name'	=> ['VCHAR:255', ''],
						'career_desc'	=> ['TEXT', ''],
						'career_icon'	=> ['VCHAR:50', ''],
					],
					'PRIMARY_KEY' => 'def_id',
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'booskit_career_definitions',
			],
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['booskit_career_source', 'url']],
		];
	}
}
