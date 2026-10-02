<?php
/**
 *
 * @package booskit/dashboard
 * @license MIT
 *
 */

namespace booskit\dashboard\migrations;

class v103_add_statistics_module extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'booskit_dashboard_stat_cats');
	}

	static public function depends_on()
	{
		return array('\booskit\dashboard\migrations\v102_fix_view_time_column_types');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'booskit_dashboard_stat_cats' => array(
					'COLUMNS' => array(
						'cat_id'         => array('UINT', null, 'auto_increment'),
						'cat_name'       => array('VCHAR:255', ''),
						'cat_desc'       => array('TEXT_UNI', ''),
						'cat_order'          => array('UINT', 0),
						'allowed_groups'     => array('TEXT_UNI', ''),
						'use_allowed_groups' => array('TEXT_UNI', ''),
					),
					'PRIMARY_KEY' => 'cat_id',
				),
				$this->table_prefix . 'booskit_dashboard_stat_defs' => array(
					'COLUMNS' => array(
						'stat_id'            => array('UINT', null, 'auto_increment'),
						'stat_tag'           => array('VCHAR:100', ''),
						'stat_title'         => array('VCHAR:255', ''),
						'cat_id'             => array('UINT', 0),
						'stat_color'         => array('VCHAR:30', '#2563eb'),
						'stat_desc'          => array('TEXT_UNI', ''),
						'stat_order'         => array('UINT', 0),
						'allowed_groups'     => array('TEXT_UNI', ''),
						'use_allowed_groups' => array('TEXT_UNI', ''),
					),
					'PRIMARY_KEY' => 'stat_id',
					'KEYS' => array(
						'stat_tag' => array('INDEX', 'stat_tag'),
						'cat_id'   => array('INDEX', 'cat_id'),
					),
				),
				$this->table_prefix . 'booskit_dashboard_stat_posts' => array(
					'COLUMNS' => array(
						'id'         => array('UINT', null, 'auto_increment'),
						'stat_tag'   => array('VCHAR:100', ''),
						'post_id'    => array('UINT', 0),
						'topic_id'   => array('UINT', 0),
						'forum_id'   => array('UINT', 0),
						'poster_id'  => array('UINT', 0),
						'post_time'  => array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY' => 'id',
					'KEYS' => array(
						'tag_post'         => array('INDEX', array('stat_tag', 'post_id')),
						'poster_stat_time' => array('INDEX', array('poster_id', 'stat_tag', 'post_time')),
						'stat_time'        => array('INDEX', array('stat_tag', 'post_time')),
						'post_id'          => array('INDEX', 'post_id'),
					),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'booskit_dashboard_stat_cats',
				$this->table_prefix . 'booskit_dashboard_stat_defs',
				$this->table_prefix . 'booskit_dashboard_stat_posts',
			),
		);
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_dashboard_include_stats_tab', 1)),

			array('module.add', array(
				'acp',
				'ACP_BOOSKIT_DASHBOARD_TITLE',
				array(
					'module_basename' => '\booskit\dashboard\acp\main_module',
					'modes'           => array('statistics'),
				),
			)),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('booskit_dashboard_include_stats_tab')),

			array('module.remove', array(
				'acp',
				'ACP_BOOSKIT_DASHBOARD_TITLE',
				array(
					'module_basename' => '\booskit\dashboard\acp\main_module',
					'modes'           => array('statistics'),
				),
			)),
		);
	}
}
