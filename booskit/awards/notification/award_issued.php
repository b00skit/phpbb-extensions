<?php
/**
 *
 * @package booskit/awards
 * @license MIT
 *
 */

namespace booskit\awards\notification;

class award_issued extends \phpbb\notification\type\base
{
	/**
	 * Get notification type
	 *
	 * @return string
	 */
	public function get_type()
	{
		return 'booskit.awards.notification.type.award_issued';
	}

	/**
	 * Language key used to output the notification
	 *
	 * @return string
	 */
	public function get_title()
	{
		return 'NOTIFICATION_AWARD_ISSUED';
	}

	/**
	 * Get the text for the notification
	 *
	 * @return string
	 */
	public function get_text()
	{
		$data = unserialize($this->data['notification_data']);
		return $this->language->lang($this->get_title(), $data['award_name']);
	}

	/**
	 * Get reference defined in services.yml for this notification
	 *
	 * @return string
	 */
	public function get_reference()
	{
		return 'booskit.awards.notification.type.award_issued';
	}

	/**
	 * Get item type
	 *
	 * @return string
	 */
	public function get_item_type()
	{
		return 'award';
	}

	/**
	 * Get item parent type
	 *
	 * @return string
	 */
	public function get_item_parent_type()
	{
		return 'user';
	}

	/**
	 * Get item id
	 *
	 * @param array $data The data from the notification table
	 *
	 * @return int
	 */
	public static function get_item_id($data)
	{
		return (int) $data['item_id'];
	}

	/**
	 * Get item parent id
	 *
	 * @param array $data The data from the notification table
	 *
	 * @return int
	 */
	public static function get_item_parent_id($data)
	{
		return (int) $data['item_parent_id'];
	}

	/**
	 * Is this notification available to the user?
	 *
	 * @return bool
	 */
	public function is_available()
	{
		return true;
	}

	/**
	 * Get the url to the item
	 *
	 * @return string
	 */
	public function get_url()
	{
		return append_sid($this->phpbb_root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $this->item_parent_id);
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

	/**
	 * Get email template variables
	 *
	 * @return array
	 */
	public function get_email_template_variables()
	{
		return array();
	}

	/**
	 * Function that creates an array of data to be inserted into the notification table
	 *
	 * @param array $data The data that is being inserted
	 * @param array $options Optional options
	 *
	 * @return array
	 */
	public function create_insert_array($data, $options = array())
	{
		$input_data = $data;
		$data = parent::create_insert_array($data, $options);

		// Store award name in data so we can display it without fetching again
		$data['notification_data'] = serialize(array(
			'award_name' => $input_data['award_name'],
		));

		return $data;
	}

	/**
	 * Find the users to notify
	 *
	 * @param array $data The data that is being inserted
	 * @param array $options Optional options
	 *
	 * @return array Array of user_ids
	 */
	public function find_users_for_notification($data, $options = array())
	{
		// Notify the user who received the award (item_parent_id)
		return array((int) $data['item_parent_id']);
	}

	/**
	 * Users needed to query to calculate the notification
	 *
	 * @return array
	 */
	public function users_to_query()
	{
		return array(
			(int) $this->item_parent_id, // The user who received the award
			(int) $this->user_id, // The issuer (actor)
		);
	}

	/**
	 * Pre-load data?
	 */
}
