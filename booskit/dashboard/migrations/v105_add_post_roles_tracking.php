<?php
/**
 *
 * @package booskit/dashboard
 * @license MIT
 *
 */

namespace booskit\dashboard\migrations;

class v105_add_post_roles_tracking extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'booskit_dashboard_post_roles');
	}

	static public function depends_on()
	{
		return array('\booskit\dashboard\migrations\v104_add_stat_usage_permissions');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'booskit_dashboard_post_roles' => array(
					'COLUMNS' => array(
						'post_id'    => array('UINT', 0),
						'poster_id'  => array('UINT', 0),
						'user_roles' => array('VCHAR:255', ''),
						'post_time'  => array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY' => 'post_id',
					'KEYS' => array(
						'poster_id' => array('INDEX', 'poster_id'),
						'post_time' => array('INDEX', 'post_time'),
					),
				),
			),
			'add_columns' => array(
				$this->table_prefix . 'booskit_dashboard_stat_posts' => array(
					'poster_groups' => array('VCHAR:255', ''),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'booskit_dashboard_post_roles',
			),
			'drop_columns' => array(
				$this->table_prefix . 'booskit_dashboard_stat_posts' => array(
					'poster_groups',
				),
			),
		);
	}
}
