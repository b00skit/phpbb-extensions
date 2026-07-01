<?php
/**
 *
 * @package booskit/threadprefixes
 * @license MIT
 *
 */

namespace booskit\threadprefixes\service;

class prefix_manager
{
	protected $db;
	protected $table_tags;
	protected $tag_cache;

	public function __construct(\phpbb\db\driver\driver_interface $db, $table_tags)
	{
		$this->db = $db;
		$this->table_tags = $table_tags;
		$this->tag_cache = [];
	}

	public function get_tags()
	{
		$sql = 'SELECT * FROM ' . $this->table_tags . ' ORDER BY tag_id ASC';
		$result = $this->db->sql_query($sql);
		$tags = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$row['forums_array'] = !empty($row['tag_forums']) ? json_decode($row['tag_forums'], true) : [];
			if (!is_array($row['forums_array']))
			{
				$row['forums_array'] = [];
			}
			$tags[] = $row;
			$this->tag_cache[(int) $row['tag_id']] = $row;
		}
		$this->db->sql_freeresult($result);
		return $tags;
	}

	public function get_tag_by_id($tag_id)
	{
		$tag_id = (int) $tag_id;
		if (isset($this->tag_cache[$tag_id]))
		{
			return $this->tag_cache[$tag_id];
		}

		$sql = 'SELECT * FROM ' . $this->table_tags . ' WHERE tag_id = ' . $tag_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($row)
		{
			$row['forums_array'] = !empty($row['tag_forums']) ? json_decode($row['tag_forums'], true) : [];
			if (!is_array($row['forums_array']))
			{
				$row['forums_array'] = [];
			}
			$this->tag_cache[$tag_id] = $row;
		}
		return $row;
	}

	public function is_tag_allowed_for_forum($tag_id, $forum_id)
	{
		$tag = $this->get_tag_by_id($tag_id);
		if (!$tag)
		{
			return false;
		}
		return in_array((int) $forum_id, $tag['forums_array']);
	}

	public function add_tag($text, $color, $bg_color, array $forums)
	{
		$data = [
			'tag_text'     => (string) $text,
			'tag_color'    => (string) $color,
			'tag_bg_color' => (string) $bg_color,
			'tag_forums'   => (string) json_encode(array_values(array_unique(array_map('intval', $forums)))),
		];
		$sql = 'INSERT INTO ' . $this->table_tags . ' ' . $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);
		$this->tag_cache = []; // Clear cache
	}

	public function update_tag($tag_id, $text, $color, $bg_color, array $forums)
	{
		$data = [
			'tag_text'     => (string) $text,
			'tag_color'    => (string) $color,
			'tag_bg_color' => (string) $bg_color,
			'tag_forums'   => (string) json_encode(array_values(array_unique(array_map('intval', $forums)))),
		];
		$sql = 'UPDATE ' . $this->table_tags . '
			SET ' . $this->db->sql_build_array('UPDATE', $data) . '
			WHERE tag_id = ' . (int) $tag_id;
		$this->db->sql_query($sql);
		$this->tag_cache = []; // Clear cache
	}

	public function delete_tag($tag_id)
	{
		$tag_id = (int) $tag_id;

		// Delete tag definition
		$sql = 'DELETE FROM ' . $this->table_tags . ' WHERE tag_id = ' . $tag_id;
		$this->db->sql_query($sql);

		// Remove tag from topics using it
		$sql = 'UPDATE ' . TOPICS_TABLE . '
			SET topic_prefix_id = 0
			WHERE topic_prefix_id = ' . $tag_id;
		$this->db->sql_query($sql);

		$this->tag_cache = []; // Clear cache
	}
}
