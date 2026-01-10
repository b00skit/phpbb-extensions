<?php
/**
 *
 * @package booskit/disciplinary
 * @license MIT
 *
 */

namespace booskit\disciplinary\migrations;

class v103_add_local_definitions extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_disciplinary_source']);
	}

	static public function depends_on()
	{
		return ['\booskit\disciplinary\migrations\v102_add_bbcode'];
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'booskit_disciplinary_definitions' => [
					'COLUMNS' => [
						'def_id'		=> ['UINT', null, 'auto_increment'],
						'disc_id'		=> ['VCHAR:255', ''],
						'disc_name'		=> ['VCHAR:255', ''],
						'disc_desc'		=> ['TEXT', ''],
						'disc_color'	=> ['VCHAR:20', ''],
						'access_level'	=> ['UINT', 0],
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
				$this->table_prefix . 'booskit_disciplinary_definitions',
			],
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['booskit_disciplinary_source', 'url']],
		];
	}
}
