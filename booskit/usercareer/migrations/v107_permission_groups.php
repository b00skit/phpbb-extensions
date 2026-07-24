<?php
/**
 *
 * @package booskit/usercareer
 * @license MIT
 *
 */

namespace booskit\usercareer\migrations;

class v107_permission_groups extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_career_perm_system']) && $this->db_tools->sql_table_exists($this->table_prefix . 'booskit_career_perm_groups');
	}

	static public function depends_on()
	{
		return ['\booskit\usercareer\migrations\v106_add_automation_settings'];
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'booskit_career_perm_groups' => [
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
				$this->table_prefix . 'booskit_career_perm_groups',
			],
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['booskit_career_perm_system', 'legacy']],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['booskit_career_perm_system']],
		];
	}
}
