<?php

namespace booskit\datacollector\migrations;

class initial_schema extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_datacollector_post_url']);
	}

	static public function depends_on()
	{
		return ['\phpbb\db\migration\data\v330\v330'];
	}

	public function update_data()
	{
		return [
			['config.add', ['booskit_datacollector_post_url', '']],
			['config.add', ['booskit_datacollector_group_id', 0]],
			['config.add', ['booskit_datacollector_forum_id', 0]],

			['module.add', [
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_DATACOLLECTOR_TITLE'
			]],
			['module.add', [
				'acp',
				'ACP_DATACOLLECTOR_TITLE',
				[
					'module_basename'	=> '\booskit\datacollector\acp\main_module',
					'modes'				=> ['settings'],
				]
			]],
		];
	}
}
