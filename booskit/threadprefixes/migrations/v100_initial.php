<?php
/**
 *
 * @package booskit/threadprefixes
 * @license MIT
 *
 */

namespace booskit\threadprefixes\migrations;

class v100_initial extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v330\v330');
	}

	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'booskit_threadprefixes_tags') &&
			$this->db_tools->sql_column_exists($this->table_prefix . 'topics', 'topic_prefix_id');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'booskit_threadprefixes_tags' => array(
					'COLUMNS' => array(
						'tag_id'       => array('UINT', null, 'auto_increment'),
						'tag_text'     => array('VCHAR:100', ''),
						'tag_color'    => array('VCHAR:7', '#ffffff'),
						'tag_bg_color' => array('VCHAR:7', '#000000'),
						'tag_forums'   => array('TEXT_UNI', ''),
					),
					'PRIMARY_KEY' => 'tag_id',
				),
			),
			'add_columns' => array(
				$this->table_prefix . 'topics' => array(
					'topic_prefix_id' => array('UINT', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'topics' => array(
					'topic_prefix_id',
				),
			),
			'drop_tables' => array(
				$this->table_prefix . 'booskit_threadprefixes_tags',
			),
		);
	}

	public function update_data()
	{
		return array(
			// Add custom forum permission
			array('permission.add', array('f_apply_prefix', false)),

			// Set defaults for common roles
			array('permission.permission_set', array('ROLE_FORUM_FULL', 'f_apply_prefix', 'role', true)),

			// Register ACP Dot Mods category container module
			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BOOSKIT_THREADPREFIXES_TITLE'
			)),

			// Register Settings sub-module
			array('module.add', array(
				'acp',
				'ACP_BOOSKIT_THREADPREFIXES_TITLE',
				array(
					'module_basename'	=> '\booskit\threadprefixes\acp\threadprefixes_module',
					'modes'				=> array('settings'),
				),
			)),
		);
	}

	public function revert_data()
	{
		return array(
			// Remove custom forum permission
			array('permission.remove', array('f_apply_prefix')),

			// Unregister ACP modules
			array('module.remove', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BOOSKIT_THREADPREFIXES_TITLE'
			)),
			array('module.remove', array(
				'acp',
				'ACP_BOOSKIT_THREADPREFIXES_TITLE',
				array(
					'module_basename'	=> '\booskit\threadprefixes\acp\threadprefixes_module',
					'modes'				=> array('settings'),
				),
			)),
		);
	}
}
