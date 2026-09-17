<?php
/**
 *
 * @package booskit/mention
 * @license MIT
 *
 */

namespace booskit\mention\acp;

class mention_info
{
	public function module()
	{
		return [
			'filename' => '\booskit\mention\acp\mention_module',
			'title'    => 'ACP_BOOSKIT_MENTION_TITLE',
			'modes'    => [
				'settings' => [
					'title' => 'ACP_BOOSKIT_MENTION_SETTINGS',
					'auth'  => 'ext_booskit/mention && acl_a_board',
					'cat'   => ['ACP_BOOSKIT_MENTION_TITLE'],
				],
			],
		];
	}
}
