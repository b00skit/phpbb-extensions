<?php
/**
 *
 * @package booskit/awards
 * @license MIT
 *
 */

namespace booskit\awards\notification;

class award_notification extends \phpbb\notification\type\base
{
	/** @var \phpbb\user_loader */
	protected $user_loader;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $phpbb_root_path;

	/** @var string */
	protected $php_ext;

	public function get_type()
	{
		return 'booskit.awards.notification.award';
	}

	protected $language_key = 'NOTIFICATION_AWARD';

	public static $notification_option = array(
		'lang' => 'NOTIFICATION_TYPE_AWARD',
		'group' => 'NOTIFICATION_GROUP_MISC',
	);

	public function __construct(\phpbb\user_loader $user_loader, \phpbb\config\config $config, \phpbb\user $user, $root_path, $php_ext)
	{
		$this->user_loader = $user_loader;
		$this->config = $config;
		$this->user = $user;
		$this->phpbb_root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	public function is_available()
	{
		return true;
	}

	public function get_item_id($data)
	{
		return (int) $data['award_id'];
	}

	public function get_item_parent_id($data)
	{
		return (int) $data['user_id'];
	}

	public function find_users_for_notification($data, $options = array())
	{
		return array((int) $data['user_id']);
	}

	public function get_title()
	{
		return $this->user->lang('NOTIFICATION_AWARD_TITLE', $this->get_data('award_name'));
	}

	public function get_url()
	{
		return append_sid($this->phpbb_root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $this->item_parent_id);
	}

	public function create_insert_array($data, $options = array())
	{
		$this->set_data('award_id', $data['award_id']);
		$this->set_data('user_id', $data['user_id']);
		$this->set_data('issuer_id', $data['issuer_id']);
		$this->set_data('award_name', $data['award_name']);

		return parent::create_insert_array($data, $options);
	}

	public function get_avatar()
	{
		return $this->user_loader->get_avatar($this->get_data('issuer_id'));
	}

	/**
	 * Get email template
	 *
	 * @return string|bool
	 */
	public function get_email_template()
	{
		return false;
	}
}
