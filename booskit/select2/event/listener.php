<?php
/**
 *
 * @package booskit/select2
 * @license MIT
 *
 */

namespace booskit\select2\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\path_helper */
	protected $path_helper;

	public function __construct(
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\config\config $config,
		\phpbb\path_helper $path_helper
	) {
		$this->template = $template;
		$this->user = $user;
		$this->db = $db;
		$this->config = $config;
		$this->path_helper = $path_helper;
	}

	public static function getSubscribedEvents()
	{
		return array(
			'core.page_header'     => 'inject_select2_data',
			'core.acp_page_header' => 'inject_select2_data',
		);
	}

	public function inject_select2_data($event)
	{
		$ranks_path = isset($this->config['ranks_path']) ? $this->config['ranks_path'] : 'images/ranks';
		$board_url = function_exists('generate_board_url') ? rtrim(generate_board_url(), '/') . '/' : '/';
		$ranks_base_url = $board_url . trim($ranks_path, '/') . '/';

		$sql = 'SELECT rank_id, rank_title, rank_image, rank_special
			FROM ' . RANKS_TABLE . '
			ORDER BY rank_title ASC';
		$result = $this->db->sql_query($sql);

		$ranks_data = array();
		while ($row = $this->db->sql_fetchrow($result)) {
			$rank_id = (int) $row['rank_id'];
			$image_name = (string) $row['rank_image'];
			$ranks_data[$rank_id] = array(
				'id'         => $rank_id,
				'title'      => (string) $row['rank_title'],
				'image_name' => $image_name,
				'image_url'  => $image_name ? $ranks_base_url . $image_name : '',
				'special'    => (int) $row['rank_special'],
			);
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars(array(
			'SELECT2_RANKS_JSON'     => json_encode($ranks_data),
			'SELECT2_RANKS_BASE_URL' => $ranks_base_url,
		));
	}
}
