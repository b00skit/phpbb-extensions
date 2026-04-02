<?php
/**
 *
 * Send As. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace booskit\sendas\migrations;

class install_sendas_table extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'booskit_sendas');
	}

	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v320\v320'];
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'booskit_sendas' => [
					'COLUMNS' => [
						'sendas_id' => ['UINT', null, 'auto_increment'],
						'msg_id' => ['UINT', 0],
						'user_id' => ['UINT', 0],
						'altchar_id' => ['UINT', 0],
					],
					'PRIMARY_KEY' => 'sendas_id',
					'KEYS' => [
						'msg_id' => ['INDEX', 'msg_id'],
					],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'booskit_sendas',
			],
		];
	}
}
