<?php
/**
 *
 * @package booskit/awards
 * @license MIT
 *
 */

namespace booskit\awards\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	protected $template;
	protected $user;
	protected $helper;
	protected $auth;
	protected $award_manager;

	public function __construct(\phpbb\template\template $template, \phpbb\user $user, \phpbb\controller\helper $helper, \phpbb\auth\auth $auth, \booskit\awards\service\award_manager $award_manager)
	{
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->auth = $auth;
		$this->award_manager = $award_manager;
	}

	public static function getSubscribedEvents()
	{
		return array(
			'core.user_setup'                           => 'load_language_on_setup',
			'core.memberlist_view_profile'              => 'memberlist_view_profile',
			'core.text_formatter_s9e_configure_after'   => 'configure_s9e_bbcode',
			'core.text_formatter_s9e_render_after'      => 'render_s9e_bbcode',
			'core.modify_format_display_text_after'     => 'render_display_text_bbcode',
		);
	}

	public function load_language_on_setup($event)
	{
		$this->user->add_lang_ext('booskit/awards', 'awards');
	}

	public function memberlist_view_profile($event)
	{
		$this->user->add_lang_ext('booskit/awards', 'awards');

		$member_id = $event['member']['user_id'];
		$viewer_id = $this->user->data['user_id'];

		if (!$this->award_manager->can_view_awards($viewer_id, $member_id))
		{
			return;
		}

		$can_add = $this->award_manager->can_add_award($viewer_id, $member_id);
		$can_remove = $this->award_manager->can_remove_award($viewer_id, $member_id);

		// Load awards for this user
		$user_awards = $this->award_manager->get_user_awards($member_id);

		foreach ($user_awards as $award)
		{
			$definition = $this->award_manager->get_definition($award['award_definition_id']);
			if ($definition)
			{
				// Parse BBCode
				$bbcode_uid = isset($award['bbcode_uid']) ? $award['bbcode_uid'] : '';
				$bbcode_bitfield = isset($award['bbcode_bitfield']) ? $award['bbcode_bitfield'] : '';
				$bbcode_options = isset($award['bbcode_options']) ? $award['bbcode_options'] : 7;
				$comment_html = generate_text_for_display($award['comment'], $bbcode_uid, $bbcode_bitfield, $bbcode_options);

				$this->template->assign_block_vars('user_awards', array(
					'NAME' => $definition['name'],
					'IMAGE' => $definition['image'],
					'MAX_WIDTH' => isset($definition['max-width']) ? $definition['max-width'] : '',
					'MAX_HEIGHT' => isset($definition['max-height']) ? $definition['max-height'] : '',
					'DATE' => $this->user->format_date($award['issue_date'], 'D M d, Y'),
					'COMMENT' => $comment_html,
					'U_REMOVE' => $can_remove ? $this->helper->route('booskit_awards_remove_award', array('award_id' => $award['award_id'])) : '',
				));
			}
		}

		if ($can_add)
		{
			$this->template->assign_vars(array(
				'U_ADD_AWARD' => $this->helper->route('booskit_awards_add_award', array('user_id' => $member_id)),
			));
		}
	}

	public function configure_s9e_bbcode($event)
	{
		$configurator = $event['configurator'];
		if (!isset($configurator->BBCodes['userawards']))
		{
			$configurator->BBCodes->addCustom(
				'[userawards={NUMBER?}]{TEXT}[/userawards]',
				'<span class="phpbb-userawards"><xsl:if test="@size"><xsl:attribute name="data-size"><xsl:value-of select="@size"/></xsl:attribute></xsl:if><xsl:apply-templates/></span>'
			);
		}
	}

	public function render_s9e_bbcode($event)
	{
		if (isset($event['html']))
		{
			$event['html'] = $this->process_userawards_bbcode($event['html']);
		}
	}

	public function render_display_text_bbcode($event)
	{
		if (isset($event['text']))
		{
			$event['text'] = $this->process_userawards_bbcode($event['text']);
		}
	}

	protected function process_userawards_bbcode($html)
	{
		if (empty($html) || (strpos($html, 'phpbb-userawards') === false && strpos($html, '[userawards') === false))
		{
			return $html;
		}

		// Match s9e rendered html tags
		$html = preg_replace_callback(
			'#<span\s+class="phpbb-userawards"(?:\s+data-size="(\d+)")?\s*>(.*?)</span>#isU',
			function($matches) {
				$size = !empty($matches[1]) ? $matches[1] : null;
				$username = strip_tags($matches[2]);
				return $this->award_manager->render_user_awards_bbcode($username, $size);
			},
			$html
		);

		// Match raw BBCode tags fallback
		$html = preg_replace_callback(
			'#\[userawards(?:=(?:&quot;|")?(\d+)(?:&quot;|")?)?\](.*?)\[/userawards\]#isU',
			function($matches) {
				$size = !empty($matches[1]) ? $matches[1] : null;
				$username = strip_tags($matches[2]);
				return $this->award_manager->render_user_awards_bbcode($username, $size);
			},
			$html
		);

		return $html;
	}
}
