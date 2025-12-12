<?php
/**
 *
 * @package booskit/awards
 * @license MIT
 *
 */

namespace booskit\awards\service;

class award_manager
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $table;

	protected $cached_definitions = null;

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\user $user, $table)
	{
		$this->config = $config;
		$this->db = $db;
		$this->user = $user;
		$this->table = $table;
	}

	public function get_definitions()
	{
		if ($this->cached_definitions !== null)
		{
			return $this->cached_definitions;
		}

		$json_url = $this->config['booskit_awards_json_url'];
		$definitions = [];

		if (!empty($json_url))
		{
			// Suppress errors and try to fetch
			$context = stream_context_create(['http' => ['timeout' => 5]]);
			$content = @file_get_contents($json_url, false, $context);
			if ($content !== false)
			{
				$data = json_decode($content, true);
				if (is_array($data))
				{
					$definitions = $data;
				}
			}
		}

		if (empty($definitions))
		{
			// Fallback example
			$definitions = [
				[
					'id' => 'example_award',
					'name' => 'Example Award',
					'description' => 'This is an internal example award.',
					'image' => 'https://via.placeholder.com/150',
					'max-height' => '50px',
					'max-width' => '50px',
				],
				[
					'id' => 'another_award',
					'name' => 'Another Award',
					'description' => 'Another internal example.',
					'image' => 'https://via.placeholder.com/150/0000FF/808080',
					'max-height' => '50px',
					'max-width' => '50px',
				]
			];
		}

		$this->cached_definitions = $definitions;
		return $definitions;
	}

	public function get_definition($id)
	{
		$definitions = $this->get_definitions();
		foreach ($definitions as $def)
		{
			if (isset($def['id']) && $def['id'] == $id)
			{
				return $def;
			}
		}
		return null;
	}

	public function get_user_awards($user_id)
	{
		$sql = 'SELECT * FROM ' . $this->table . ' WHERE user_id = ' . (int) $user_id . ' ORDER BY issue_date DESC';
		$result = $this->db->sql_query($sql);

		$awards = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$awards[] = $row;
		}
		$this->db->sql_freeresult($result);

		return $awards;
	}

	public function add_award($user_id, $award_definition_id, $issue_date, $comment, $issuer_user_id)
	{
		$sql_ary = [
			'user_id' => (int) $user_id,
			'award_definition_id' => $award_definition_id,
			'issue_date' => (int) $issue_date,
			'comment' => $comment,
			'issuer_user_id' => (int) $issuer_user_id,
		];

		$sql = 'INSERT INTO ' . $this->table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);

		return $this->db->sql_nextid();
	}
}
