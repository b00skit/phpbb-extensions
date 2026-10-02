<?php
/**
 *
 * @package booskit/dashboard
 * @license MIT
 *
 */

namespace booskit\dashboard\migrations;

class v104_add_stat_usage_permissions extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'booskit_dashboard_stat_defs', 'use_allowed_groups');
	}

	static public function depends_on()
	{
		return array('\booskit\dashboard\migrations\v103_add_statistics_module');
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'booskit_dashboard_stat_cats' => array(
					'use_allowed_groups' => array('TEXT_UNI', ''),
				),
				$this->table_prefix . 'booskit_dashboard_stat_defs' => array(
					'use_allowed_groups' => array('TEXT_UNI', ''),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'booskit_dashboard_stat_cats' => array(
					'use_allowed_groups',
				),
				$this->table_prefix . 'booskit_dashboard_stat_defs' => array(
					'use_allowed_groups',
				),
			),
		);
	}
}