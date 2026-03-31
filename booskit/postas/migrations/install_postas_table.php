<?php
/**
 *
 * Post As. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\postas\migrations;

/**
 * Migration to create postas table
 */
class install_postas_table extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'postas');
	}

	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v320\v320'];
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'postas' => [
					'COLUMNS' => [
						'postas_id' => ['UINT', null, 'auto_increment'],
						'post_id' => ['UINT', 0],
						'user_id' => ['UINT', 0],
						'altchar_id' => ['UINT', 0],
					],
					'PRIMARY_KEY' => 'postas_id',
					'KEYS' => [
						'post_id' => ['INDEX', 'post_id'],
					],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'postas',
			],
		];
	}
}
