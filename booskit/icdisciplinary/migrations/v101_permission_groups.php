<?php
/**
 *
 * @package booskit/icdisciplinary
 * @license MIT
 *
 */

namespace booskit\icdisciplinary\migrations;

class v101_permission_groups extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_icdisciplinary_perm_system']) && $this->db_tools->sql_table_exists($this->table_prefix . 'booskit_icdisciplinary_perm_groups');
	}

	static public function depends_on()
	{
		return ['\booskit\icdisciplinary\migrations\v100_initial'];
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'booskit_icdisciplinary_perm_groups' => [
					'COLUMNS' => [
						'perm_group_id'		=> ['UINT', null, 'auto_increment'],
						'group_name'		=> ['VCHAR:255', ''],
						'applies_to'		=> ['TEXT_UNI', ''],
						'power_over_all'	=> ['TINT:1', 0],
						'power_over_self'	=> ['TINT:1', 0],
						'power_over_groups'	=> ['TEXT_UNI', ''],
						'exclude_groups'	=> ['TEXT_UNI', ''],
						'permissions'		=> ['TEXT_UNI', ''],
					],
					'PRIMARY_KEY' => 'perm_group_id',
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'booskit_icdisciplinary_perm_groups',
			],
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['booskit_icdisciplinary_perm_system', 'legacy']],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['booskit_icdisciplinary_perm_system']],
		];
	}
}
