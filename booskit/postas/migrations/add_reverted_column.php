<?php
/**
 *
 * Post As. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace booskit\postas\migrations;

/**
 * Migration to add reverted column to postas table
 */
class add_reverted_column extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'postas', 'reverted');
	}

	public static function depends_on()
	{
		return ['\booskit\postas\migrations\install_postas_table'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'postas' => [
					'reverted' => ['BOOL', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'postas' => [
					'reverted',
				],
			],
		];
	}
}