<?php
/**
 *
 * @package booskit/dashboard
 * @license MIT
 *
 */

namespace booskit\dashboard\migrations;

class v102_fix_view_time_column_types extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\booskit\dashboard\migrations\v101_dashboard_enhancements');
	}

	public function update_schema()
	{
		return array(
			'change_columns' => array(
				$this->table_prefix . 'booskit_dashboard_topic_views' => array(
					'view_time' => array('TIMESTAMP', 0),
				),
				$this->table_prefix . 'booskit_dashboard_forum_views' => array(
					'view_time' => array('TIMESTAMP', 0),
				),
				$this->table_prefix . 'booskit_dashboard_user_views' => array(
					'view_time' => array('TIMESTAMP', 0),
				),
				$this->table_prefix . 'booskit_dashboard_profile_views' => array(
					'view_time' => array('TIMESTAMP', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'change_columns' => array(
				$this->table_prefix . 'booskit_dashboard_topic_views' => array(
					'view_time' => array('TIMESTAMP', 0),
				),
				$this->table_prefix . 'booskit_dashboard_forum_views' => array(
					'view_time' => array('TIMESTAMP', 0),
				),
				$this->table_prefix . 'booskit_dashboard_user_views' => array(
					'view_time' => array('TIMESTAMP', 0),
				),
				$this->table_prefix . 'booskit_dashboard_profile_views' => array(
					'view_time' => array('TIMESTAMP', 0),
				),
			),
		);
	}
}
