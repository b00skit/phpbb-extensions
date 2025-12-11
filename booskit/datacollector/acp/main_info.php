<?php

namespace booskit\datacollector\acp;

class main_info
{
	function module()
	{
		return [
			'filename'	=> '\booskit\datacollector\acp\main_module',
			'title'		=> 'ACP_DATACOLLECTOR_TITLE',
			'modes'		=> [
				'settings'	=> [
					'title' => 'ACP_DATACOLLECTOR_SETTINGS',
					'auth'  => 'ext_booskit/datacollector && acl_a_board',
					'cat'   => ['ACP_DATACOLLECTOR_TITLE']
				],
			],
		];
	}
}
