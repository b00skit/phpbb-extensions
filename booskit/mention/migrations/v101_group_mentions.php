<?php
/**
 *
 * @package booskit/mention
 * @license MIT
 *
 */

namespace booskit\mention\migrations;

class v101_group_mentions extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_mention_group_enabled']);
	}

	public static function depends_on()
	{
		return ['\booskit\mention\migrations\v100_initial'];
	}

	public function update_data()
	{
		return [
			['config.add', ['booskit_mention_group_enabled', 1]],
			['config_text.add', ['booskit_mention_group_matrix', '{}']],
			['module.add', ['acp', 'ACP_CAT_DOT_MODS', 'ACP_BOOSKIT_MENTION_TITLE']],
			['module.add', ['acp', 'ACP_BOOSKIT_MENTION_TITLE', [
				'module_basename' => '\booskit\mention\acp\mention_module',
				'module_langname' => 'ACP_BOOSKIT_MENTION_SETTINGS',
				'module_mode'     => 'settings',
				'module_auth'     => 'acl_a_board',
			]]],
		];
	}
}
