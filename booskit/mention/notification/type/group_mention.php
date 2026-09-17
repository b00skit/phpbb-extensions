<?php
/**
 *
 * @package booskit/mention
 * @license MIT
 *
 */

namespace booskit\mention\notification\type;

class group_mention extends \phpbb\notification\type\post
{
	/** @var \booskit\mention\service\mention_manager */
	protected $mention_manager;

	/**
	 * Set mention manager
	 *
	 * @param \booskit\mention\service\mention_manager $mention_manager
	 */
	public function set_mention_manager(\booskit\mention\service\mention_manager $mention_manager)
	{
		$this->mention_manager = $mention_manager;
	}

	/**
	 * Get notification type name
	 *
	 * @return string
	 */
	public function get_type()
	{
		return 'booskit.mention.notification.type.group_mention';
	}

	/**
	 * Language key used to output the text
	 *
	 * @var string
	 */
	protected $language_key = 'NOTIFICATION_GROUP_MENTION';

	/**
	 * Notification option data (for outputting to the user in UCP)
	 *
	 * @var array
	 */
	static public $notification_option = [
		'lang'  => 'NOTIFICATION_TYPE_GROUP_MENTION',
		'group' => 'NOTIFICATION_GROUP_POSTING',
	];

	/**
	 * Is available
	 *
	 * @return bool
	 */
	public function is_available()
	{
		if ($this->mention_manager)
		{
			return $this->mention_manager->is_group_mention_enabled();
		}
		return true;
	}

	/**
	 * Find users in mentioned groups who want to receive notifications
	 *
	 * @param array $post
	 * @param array $options
	 * @return array
	 */
	public function find_users_for_notification($post, $options = [])
	{
		$options = array_merge([
			'ignore_users' => [],
		], $options);

		if (empty($post['post_text']))
		{
			return [];
		}

		if ($this->mention_manager && !$this->mention_manager->is_group_mention_enabled())
		{
			return [];
		}

		$text = $post['post_text'];
		$group_ids = [];
		$group_names = [];

		// Match [mentiongroup=5]GroupName[/mentiongroup] or <MENTIONGROUP mentiongroup="5">
		if (preg_match_all('/(?:\[mentiongroup=(\d+)\]|<MENTIONGROUP\b[^>]*\bmentiongroup="(\d+)")/is', $text, $matches))
		{
			foreach ($matches[1] as $idx => $id1)
			{
				$gid = !empty($id1) ? (int) $id1 : (int) $matches[2][$idx];
				if ($gid > 0)
				{
					$group_ids[] = $gid;
				}
			}
		}

		// Match [mentiongroup]GroupName[/mentiongroup]
		if (preg_match_all('/\[mentiongroup\](.*?)\[\/mentiongroup\]/is', $text, $matches))
		{
			foreach ($matches[1] as $gname)
			{
				$gname = trim(strip_tags($gname));
				if ($gname !== '')
				{
					$group_names[] = $gname;
				}
			}
		}

		// Handle s9e parsed XML without mentiongroup attribute: <MENTIONGROUP><s>[mentiongroup]</s>Name<e>[/mentiongroup]</e></MENTIONGROUP>
		if (preg_match_all('/<MENTIONGROUP(?:\s+[^>]*)?>(?:<s>\[mentiongroup\]<\/s>)?(.*?)(?:<e>\[\/mentiongroup\]<\/e>)?<\/MENTIONGROUP>/is', $text, $matches))
		{
			foreach ($matches[1] as $gname)
			{
				$gname = trim(strip_tags($gname));
				if ($gname !== '')
				{
					$group_names[] = $gname;
				}
			}
		}

		// Lookup group names to IDs
		if (!empty($group_names))
		{
			$groups_table = defined('GROUPS_TABLE') ? GROUPS_TABLE : $this->phpbb_root_path . 'groups';
			$sql = 'SELECT group_id, group_name
				FROM ' . $groups_table . '
				WHERE ' . $this->db->sql_in_set('group_name', $group_names);
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$group_ids[] = (int) $row['group_id'];
			}
			$this->db->sql_freeresult($result);
		}

		if (empty($group_ids))
		{
			return [];
		}

		$group_ids = array_unique($group_ids);
		$poster_id = isset($post['poster_id']) ? (int) $post['poster_id'] : 0;

		// Filter groups based on permissions: Can the author mention each group?
		$allowed_groups = [];
		foreach ($group_ids as $gid)
		{
			if ($this->mention_manager)
			{
				if ($this->mention_manager->can_user_mention_group($poster_id, $gid))
				{
					$allowed_groups[] = $gid;
				}
			}
			else
			{
				$allowed_groups[] = $gid;
			}
		}

		if (empty($allowed_groups))
		{
			return [];
		}

		// Find members of allowed target groups
		$user_group_table = defined('USER_GROUP_TABLE') ? USER_GROUP_TABLE : $this->phpbb_root_path . 'user_group';
		$sql = 'SELECT DISTINCT user_id
			FROM ' . $user_group_table . '
			WHERE ' . $this->db->sql_in_set('group_id', $allowed_groups) . '
				AND user_pending = 0';
		$result = $this->db->sql_query($sql);

		$user_ids = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$user_ids[] = (int) $row['user_id'];
		}
		$this->db->sql_freeresult($result);

		if (empty($user_ids))
		{
			return [];
		}

		$user_ids = array_unique($user_ids);
		$anonymous_id = defined('ANONYMOUS') ? ANONYMOUS : 1;

		// Filter out the author and anonymous
		$user_ids = array_filter($user_ids, function ($uid) use ($poster_id, $anonymous_id) {
			return $uid > 0 && $uid !== $poster_id && $uid !== $anonymous_id;
		});

		if (empty($user_ids))
		{
			return [];
		}

		$forum_id = isset($post['forum_id']) ? (int) $post['forum_id'] : 0;
		return $this->get_authorised_recipients(array_values($user_ids), $forum_id, $options, true);
	}

	/**
	 * Do not notify on edit
	 *
	 * @param array $post
	 * @return bool
	 */
	public function update_notifications($post)
	{
		return false;
	}

	/**
	 * Redirect directly to the post
	 *
	 * @return string
	 */
	public function get_redirect_url()
	{
		return $this->get_url();
	}

	/**
	 * Email template for group mentions
	 *
	 * @return string
	 */
	public function get_email_template()
	{
		return '@booskit_mention/group_mention.txt';
	}

	/**
	 * Email template variables
	 *
	 * @return array
	 */
	public function get_email_template_variables()
	{
		$poster_id = (int) $this->get_data('poster_id');
		$user_data = $this->user_loader->get_user($poster_id);
		$author_name = !empty($user_data['username']) ? $user_data['username'] : $this->get_data('post_username');

		return array_merge(parent::get_email_template_variables(), [
			'AUTHOR_NAME' => html_entity_decode($author_name, ENT_COMPAT),
		]);
	}
}
