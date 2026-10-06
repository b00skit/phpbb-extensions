<?php
/**
 *
 * @package booskit/forms
 * @license MIT
 *
 */

namespace booskit\forms\migrations;

class v106_add_webhooks extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'booskit_forms', 'webhook_url');
	}

	static public function depends_on()
	{
		return array('\booskit\forms\migrations\v105_add_public_access');
	}

	public function update_schema()
	{
		return array(
			'add_columns' => array(
				$this->table_prefix . 'booskit_forms' => array(
					'webhook_enabled'	=> array('BOOL', 0),
					'webhook_url'		=> array('TEXT_UNI', ''),
					'webhook_template'	=> array('TEXT_UNI', ''),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'booskit_forms' => array(
					'webhook_enabled',
					'webhook_url',
					'webhook_template',
				),
			),
		);
	}
}
