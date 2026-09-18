<?php
/**
 *
 * @package booskit/dashboard
 * @license MIT
 *
 */

namespace booskit\dashboard\migrations;

class v101_dashboard_enhancements extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_dashboard_perm_system']) && $this->db_tools->sql_table_exists($this->table_prefix . 'booskit_dashboard_perm_groups');
	}

	static public function depends_on()
	{
		return array('\booskit\dashboard\migrations\v100_initial');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'booskit_dashboard_perm_groups' => array(
					'COLUMNS' => array(
						'perm_group_id'     => array('UINT', null, 'auto_increment'),
						'group_name'        => array('VCHAR:255', ''),
						'applies_to'        => array('TEXT_UNI', ''),
						'power_over_all'    => array('TINT:1', 0),
						'power_over_self'   => array('TINT:1', 0),
						'power_over_groups' => array('TEXT_UNI', ''),
						'exclude_groups'    => array('TEXT_UNI', ''),
						'permissions'       => array('TEXT_UNI', ''),
					),
					'PRIMARY_KEY' => 'perm_group_id',
				),
				$this->table_prefix . 'booskit_dashboard_forum_views' => array(
					'COLUMNS' => array(
						'view_id'    => array('UINT', null, 'auto_increment'),
						'user_id'    => array('UINT', 0),
						'forum_id'   => array('UINT', 0),
						'view_time'  => array('TIMESTAMP', 0),
						'view_count' => array('UINT', 1),
					),
					'PRIMARY_KEY' => 'view_id',
					'KEYS' => array(
						'user_forum' => array('INDEX', array('user_id', 'forum_id')),
						'user_view'  => array('INDEX', array('user_id', 'view_time')),
					),
				),
				$this->table_prefix . 'booskit_dashboard_user_views' => array(
					'COLUMNS' => array(
						'view_id'        => array('UINT', null, 'auto_increment'),
						'user_id'        => array('UINT', 0),
						'viewed_user_id' => array('UINT', 0),
						'view_time'      => array('TIMESTAMP', 0),
						'view_count'     => array('UINT', 1),
					),
					'PRIMARY_KEY' => 'view_id',
					'KEYS' => array(
						'user_viewed' => array('INDEX', array('user_id', 'viewed_user_id')),
						'user_time'   => array('INDEX', array('user_id', 'view_time')),
					),
				),
				$this->table_prefix . 'booskit_dashboard_profile_views' => array(
					'COLUMNS' => array(
						'view_id'        => array('UINT', null, 'auto_increment'),
						'user_id'        => array('UINT', 0),
						'viewed_user_id' => array('UINT', 0),
						'view_time'      => array('TIMESTAMP', 0),
						'view_count'     => array('UINT', 1),
					),
					'PRIMARY_KEY' => 'view_id',
					'KEYS' => array(
						'prof_viewed' => array('INDEX', array('user_id', 'viewed_user_id')),
						'prof_time'   => array('INDEX', array('user_id', 'view_time')),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'booskit_dashboard_perm_groups',
				$this->table_prefix . 'booskit_dashboard_forum_views',
				$this->table_prefix . 'booskit_dashboard_user_views',
				$this->table_prefix . 'booskit_dashboard_profile_views',
			),
		);
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_dashboard_perm_system', 'groups')),
			array('config.add', array('booskit_dashboard_group_metric_group', 0)),
			array('config.add', array('booskit_dashboard_group_metric_label', '')),
			array('config.add', array('booskit_dashboard_visited_limit', 30)),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('booskit_dashboard_perm_system')),
			array('config.remove', array('booskit_dashboard_group_metric_group')),
			array('config.remove', array('booskit_dashboard_group_metric_label')),
			array('config.remove', array('booskit_dashboard_visited_limit')),
		);
	}
}
