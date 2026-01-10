<?php
/**
 *
 * @package booskit/awards
 * @license MIT
 *
 */

namespace booskit\awards\migrations;

class v102_add_local_definitions extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_awards_source']);
	}

	static public function depends_on()
	{
		return ['\booskit\awards\migrations\v101_add_bbcode'];
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'booskit_awards_definitions' => [
					'COLUMNS' => [
						'def_id'		=> ['UINT', null, 'auto_increment'],
						'award_id'		=> ['VCHAR:255', ''],
						'award_name'	=> ['VCHAR:255', ''],
						'award_desc'	=> ['TEXT', ''],
						'award_img'		=> ['VCHAR:255', ''],
						'award_w'		=> ['VCHAR:20', ''],
						'award_h'		=> ['VCHAR:20', ''],
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
				$this->table_prefix . 'booskit_awards_definitions',
			],
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['booskit_awards_source', 'url']],
		];
	}
}
