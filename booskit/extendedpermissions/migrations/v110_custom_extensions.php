<?php
/**
 *
 * Extended Permissions. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace booskit\extendedpermissions\migrations;

class v110_custom_extensions extends \phpbb\db\migration\migration
{
	/**
	 * {@inheritDoc}
	 */
	public static function depends_on()
	{
		return ['\booskit\extendedpermissions\migrations\install'];
	}

	/**
	 * {@inheritDoc}
	 */
	public function effectively_installed()
	{
		return isset($this->config['booskit_extperm_perm_system']) && $this->db_tools->sql_table_exists($this->table_prefix . 'booskit_extperm_groups');
	}

	/**
	 * {@inheritDoc}
	 */
	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'booskit_extperm_groups' => [
					'COLUMNS' => [
						'perm_group_id'      => ['UINT', null, 'auto_increment'],
						'group_name'         => ['VCHAR:255', ''],
						'applies_to'         => ['TEXT_UNI', ''],
						'can_manage_module'  => ['TINT:1', 0],
						'allowed_extensions' => ['TEXT_UNI', ''],
						'permissions'        => ['TEXT_UNI', ''],
					],
					'PRIMARY_KEY' => 'perm_group_id',
				],
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'booskit_extperm_groups',
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function update_data()
	{
		return [
			['config.add', ['booskit_extperm_perm_system', 'groups']],
			['config.add', ['booskit_extperm_module_access', '']],
			['module.add', [
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_EXTENSIONS_MANAGER',
			]],
			['module.add', [
				'acp',
				'ACP_EXTENSIONS_MANAGER',
				[
					'module_basename' => '\booskit\extendedpermissions\acp\custom_extensions_module',
					'modes'           => ['custom_extensions'],
				],
			]],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function revert_data()
	{
		return [
			['config.remove', ['booskit_extperm_perm_system']],
			['config.remove', ['booskit_extperm_module_access']],
			['module.remove', [
				'acp',
				'ACP_EXTENSIONS_MANAGER',
				[
					'module_basename' => '\booskit\extendedpermissions\acp\custom_extensions_module',
					'modes'           => ['custom_extensions'],
				],
			]],
			['module.remove', [
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_EXTENSIONS_MANAGER',
			]],
		];
	}
}
