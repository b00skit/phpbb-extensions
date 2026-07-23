<?php
/**
 *
 * Hide Content Extension for phpBB.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace booskit\hidecontent\migrations;

class v100_initial extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v330\v330');
	}

	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'topics', 'topic_hidden') &&
			$this->db_tools->sql_column_exists($this->table_prefix . 'posts', 'post_hidden');
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'topics' => array(
					'topic_hidden' => array('UINT:1', 0),
				),
				$this->table_prefix . 'posts' => array(
					'post_hidden'  => array('UINT:1', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'topics' => array(
					'topic_hidden',
				),
				$this->table_prefix . 'posts' => array(
					'post_hidden',
				),
			),
		);
	}

	public function update_data()
	{
		return array(
			// Add moderator permissions (default OFF)
			array('permission.add', array('m_hide', true)),
			array('permission.add', array('m_view_hidden', true)),
		);
	}

	public function revert_data()
	{
		return array(
			array('permission.remove', array('m_hide')),
			array('permission.remove', array('m_view_hidden')),
		);
	}
}
