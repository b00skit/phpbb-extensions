<?php
/**
 *
 * Topic Log Views extension for phpBB.
 *
 * @copyright (c) 2026 Booskit
 * @license MIT
 *
 */

namespace booskit\topiclogviews\acp;

class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\booskit\topiclogviews\acp\main_module',
			'title'		=> 'ACP_TOPICLOGVIEWS_TITLE',
			'modes'		=> array(
				'settings'	=> array(
					'title' => 'ACP_TOPICLOGVIEWS_SETTINGS',
					'auth'  => 'ext_booskit/topiclogviews && acl_a_board',
					'cat'   => array('ACP_TOPICLOGVIEWS_TITLE')
				),
			),
		);
	}
}
