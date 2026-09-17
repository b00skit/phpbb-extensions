<?php
/**
 *
 * @package booskit/mention
 * @license MIT
 *
 */

namespace booskit\mention\notification\type;

class mention extends \phpbb\notification\type\post
{
	/**
	 * Get notification type name
	 *
	 * @return string
	 */
	public function get_type()
	{
		return 'booskit.mention.notification.type.mention';
	}

	/**
	 * Language key used to output the text
	 *
	 * @var string
	 */
	protected $language_key = 'NOTIFICATION_MENTION';

	/**
	 * Notification option data (for outputting to the user in UCP)
	 *
	 * @var array
	 */
	static public $notification_option = [
		'lang'  => 'NOTIFICATION_TYPE_MENTION',
		'group' => 'NOTIFICATION_GROUP_POSTING',
	];

	/**
	 * Is available
	 *
	 * @return bool
	 */
	public function is_available()
	{
		return true;
	}

	/**
	 * Find the users who want to receive notifications
	 *
	 * @param array $post Data from submit_post
	 * @param array $options Options for finding users for notification
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

		$text = $post['post_text'];
		$user_ids = [];
		$usernames = [];

		// 1. Match [mention=123]User[/mention] BBCode or <MENTION mention="123"> XML
		if (preg_match_all('/(?:\[mention=(\d+)\]|<MENTION\b[^>]*\bmention="(\d+)")/is', $text, $matches))
		{
			foreach ($matches[1] as $idx => $id1)
			{
				$id = !empty($id1) ? (int) $id1 : (int) $matches[2][$idx];
				if ($id > 0)
				{
					$user_ids[] = $id;
				}
			}
		}

		// 2. Match [mention]Username[/mention] BBCode or <MENTION>Username</MENTION> XML
		if (preg_match_all('/\[mention\](.*?)\[\/mention\]/is', $text, $matches))
		{
			foreach ($matches[1] as $uname)
			{
				$uname = trim(strip_tags($uname));
				if ($uname !== '')
				{
					$usernames[] = $uname;
				}
			}
		}

		// Also handle s9e parsed XML without mention attribute: <MENTION><s>[mention]</s>User<e>[/mention]</e></MENTION>
		if (preg_match_all('/<MENTION(?:\s+[^>]*)?>(?:<s>\[mention\]<\/s>)?(.*?)(?:<e>\[\/mention\]<\/e>)?<\/MENTION>/is', $text, $matches))
		{
			foreach ($matches[1] as $uname)
			{
				$uname = trim(strip_tags($uname));
				if ($uname !== '')
				{
					$usernames[] = $uname;
				}
			}
		}

		// Lookup usernames to user_ids
		if (!empty($usernames))
		{
			$cleaned_names = array_unique(array_map(function($n) {
				return function_exists('utf8_clean_string') ? utf8_clean_string($n) : strtolower($n);
			}, $usernames));

			$sql = 'SELECT user_id
				FROM ' . USERS_TABLE . '
				WHERE ' . $this->db->sql_in_set('username_clean', $cleaned_names);
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$user_ids[] = (int) $row['user_id'];
			}
			$this->db->sql_freeresult($result);
		}

		if (empty($user_ids))
		{
			return [];
		}

		$user_ids = array_unique($user_ids);
		$poster_id = isset($post['poster_id']) ? (int) $post['poster_id'] : 0;
		$anonymous_id = defined('ANONYMOUS') ? ANONYMOUS : 1;

		// Filter out self mentions and anonymous
		$user_ids = array_filter($user_ids, function($uid) use ($poster_id, $anonymous_id) {
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
	 * Do not update notifications on post edit
	 *
	 * @param array $post
	 * @return bool
	 */
	public function update_notifications($post)
	{
		return false;
	}

	/**
	 * Redirect URL directly to the mentioned post
	 *
	 * @return string
	 */
	public function get_redirect_url()
	{
		return $this->get_url();
	}

	/**
	 * Email template
	 *
	 * @return string
	 */
	public function get_email_template()
	{
		return '@booskit_mention/mention.txt';
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
