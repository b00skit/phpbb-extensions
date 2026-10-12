<?php
/**
 *
 * @package booskit/dashboard
 * @license MIT
 *
 */

namespace booskit\dashboard\migrations;

class v106_add_group_views_module extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'booskit_dashboard_group_views');
	}

	static public function depends_on()
	{
		return array('\booskit\dashboard\migrations\v105_add_post_roles_tracking');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'booskit_dashboard_group_views' => array(
					'COLUMNS' => array(
						'view_id'        => array('UINT', null, 'auto_increment'),
						'view_name'      => array('VCHAR:255', ''),
						'view_desc'      => array('TEXT_UNI', ''),
						'include_groups' => array('TEXT_UNI', ''),
						'include_mode'   => array('VCHAR:10', 'all'),
						'exclude_groups' => array('TEXT_UNI', ''),
						'allowed_groups' => array('TEXT_UNI', ''),
						'view_order'     => array('UINT', 0),
						'enabled'        => array('TINT:1', 1),
					),
					'PRIMARY_KEY' => 'view_id',
					'KEYS' => array(
						'view_order' => array('INDEX', 'view_order'),
						'enabled'    => array('INDEX', 'enabled'),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'booskit_dashboard_group_views',
			),
		);
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_dashboard_include_group_views_tab', 1)),

			array('module.add', array(
				'acp',
				'ACP_BOOSKIT_DASHBOARD_TITLE',
				array(
					'module_basename' => '\booskit\dashboard\acp\main_module',
					'modes'           => array('group_views'),
				),
			)),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('booskit_dashboard_include_group_views_tab')),

			array('module.remove', array(
				'acp',
				'ACP_BOOSKIT_DASHBOARD_TITLE',
				array(
					'module_basename' => '\booskit\dashboard\acp\main_module',
					'modes'           => array('group_views'),
				),
			)),
		);
	}
}
