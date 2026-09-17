<?php
/**
 *
 * @package booskit/dashboard
 * @license MIT
 *
 */

namespace booskit\dashboard\migrations;

class v100_initial extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_dashboard_enabled']);
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v330\v330');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'booskit_dashboard_topic_views' => array(
					'COLUMNS' => array(
						'view_id'   => array('UINT', null, 'auto_increment'),
						'user_id'   => array('UINT', 0),
						'topic_id'  => array('UINT', 0),
						'forum_id'  => array('UINT', 0),
						'view_time' => array('UINT', 0),
					),
					'PRIMARY_KEY' => 'view_id',
					'KEYS' => array(
						'user_view'  => array('INDEX', array('user_id', 'view_time')),
						'topic_view' => array('INDEX', array('topic_id', 'view_time')),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'booskit_dashboard_topic_views',
			),
		);
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_dashboard_enabled', 1)),
			array('config.add', array('booskit_dashboard_allowed_groups', '')),
			array('config.add', array('booskit_dashboard_include_awards', 1)),
			array('config.add', array('booskit_dashboard_include_career', 1)),
			array('config.add', array('booskit_dashboard_include_commendations', 1)),
			array('config.add', array('booskit_dashboard_include_disciplinary', 1)),
			array('config.add', array('booskit_dashboard_include_ic_disciplinary', 1)),
			array('config.add', array('booskit_dashboard_group_profile_access', '')),
			array('config.add', array('booskit_dashboard_profile_admin_override', 1)),
			array('config.add', array('booskit_dashboard_issued_groups', '')),
			array('config.add', array('booskit_dashboard_issued_allow_self', 1)),
			array('config.add', array('booskit_dashboard_recent_topics_groups', '')),
			array('config.add', array('booskit_dashboard_recent_topics_allow_self', 1)),

			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BOOSKIT_DASHBOARD_TITLE'
			)),
			array('module.add', array(
				'acp',
				'ACP_BOOSKIT_DASHBOARD_TITLE',
				array(
					'module_basename' => '\booskit\dashboard\acp\main_module',
					'modes'           => array('settings'),
				),
			)),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('booskit_dashboard_enabled')),
			array('config.remove', array('booskit_dashboard_allowed_groups')),
			array('config.remove', array('booskit_dashboard_include_awards')),
			array('config.remove', array('booskit_dashboard_include_career')),
			array('config.remove', array('booskit_dashboard_include_commendations')),
			array('config.remove', array('booskit_dashboard_include_disciplinary')),
			array('config.remove', array('booskit_dashboard_include_ic_disciplinary')),
			array('config.remove', array('booskit_dashboard_group_profile_access')),
			array('config.remove', array('booskit_dashboard_profile_admin_override')),
			array('config.remove', array('booskit_dashboard_issued_groups')),
			array('config.remove', array('booskit_dashboard_issued_allow_self')),
			array('config.remove', array('booskit_dashboard_recent_topics_groups')),
			array('config.remove', array('booskit_dashboard_recent_topics_allow_self')),

			array('module.remove', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BOOSKIT_DASHBOARD_TITLE'
			)),
			array('module.remove', array(
				'acp',
				'ACP_BOOSKIT_DASHBOARD_TITLE',
				array(
					'module_basename' => '\booskit\dashboard\acp\main_module',
					'modes'           => array('settings'),
				),
			)),
		);
	}
}
