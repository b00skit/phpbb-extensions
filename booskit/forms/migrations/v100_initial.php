<?php
/**
 *
 * @package booskit/forms
 * @license MIT
 *
 */

namespace booskit\forms\migrations;

class v100_initial extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v330\v330');
	}

	public function update_data()
	{
		return array(
			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BOOSKIT_FORMS_TITLE'
			)),
			array('module.add', array(
				'acp',
				'ACP_BOOSKIT_FORMS_TITLE',
				array(
					'module_basename'	=> '\booskit\forms\acp\main_module',
					'modes'				=> array('manage'),
				),
			)),
		);
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'booskit_forms' => array(
					'COLUMNS' => array(
						'form_id'			=> array('UINT', null, 'auto_increment'),
						'form_name'			=> array('VCHAR:255', ''),
						'form_desc'			=> array('TEXT_UNI', ''),
						'form_header'		=> array('TEXT_UNI', ''),
						'form_template'		=> array('TEXT_UNI', ''),
						'form_subject_tpl'	=> array('VCHAR:255', ''),
						'form_fields'		=> array('TEXT_UNI', ''),
						'forum_id'			=> array('UINT', 0),
						'poster_id'			=> array('UINT', 0),
						'enabled'			=> array('BOOL', 1),
					),
					'PRIMARY_KEY' => 'form_id',
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'booskit_forms',
			),
		);
	}
}
