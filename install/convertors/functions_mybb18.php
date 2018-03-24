<?php
/**
*
* This file is part of the MyBB 1.8.x to phpBB 3.1.x Convertor.
*
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @copyright (c) prototech
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace prototech\mybb18_phpbb31_convertor;

class helper
{
	/** @var \convert */
	protected $convert;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\db\driver\driver_interface */
	protected $src_db;

	/** @var \phpbb\db\tools */
	protected $db_tools;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\passwords\manager */
	protected $passwords_manager;

	/** @var \parse_message */
	protected $message_parser;

	/** @var string */
	protected $src_table_prefix;

	/** @var string */
	protected $table_prefix;

	/** @var bool */
	protected $same_db;

	/** @var string */
	protected $phpbb_root_path;

	/** @var string */
	protected $php_ext;

	/** @var array */
	protected $store = array();

	/**
	* Constructor.
	*
	* @param \convert $convert
	* @param \phpbb\config\config $config
	* @Param \phpbb\db\driver\driver_interface $db
	* @param \phpbb\db\driver\driver_interface $src_db
	* @param \phpbb\db\tools $db_tools
	* @param \phpbb\user $user
	* @param \phpbb\passwords\manager $passwords_manager
	* @param string $src_table_prefix
	* @param string $table_prefix
	* @param bool $same_db
	* @param string $phpbb_root_path
	* @param string $php_ext
	*/
	public function __construct(\convert $convert, \phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\db\driver\driver_interface $src_db, \phpbb\db\tools $db_tools, \phpbb\user $user, \phpbb\passwords\manager $passwords_manager, $src_table_prefix, $table_prefix, $same_db, $phpbb_root_path, $php_ext)
	{
		$this->convert = $convert;
		$this->config = $config;
		$this->db = $db;
		$this->src_db = $src_db;
		$this->db_tools = $db_tools;
		$this->user = $user;
		$this->passwords_manager = $passwords_manager;
		$this->src_table_prefix = $src_table_prefix;
		$this->table_prefix = $table_prefix;
		$this->same_db = $same_db;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
	}

	protected function is_stored($name)
	{
		return isset($this->store[$name]);
	}

	protected function store($name, $value)
	{
		$this->store[$name] = $value;
	}

	protected function get_stored($name)
	{
		return ($this->is_stored($name)) ? $this->store[$name] : null;
	}

	public function set_message_parser(\parse_message $message_parser)
	{
		$this->message_parser = $message_parser;
	}

	public function run_initial_tasks()
	{
		$this->create_userconv_table();
		$this->insert_forums();
		$this->create_bbcodes();
		$this->add_user_salt_field();
		$this->enable_avatars();
		$this->convert_additional_settings();
		import_avatar_gallery();
	}

	public function not_empty($mixed)
	{
		return !empty($mixed);
	}

	/**
	* Function for recoding text with the default language
	*
	* @param string $text text to recode to utf8
	*/
	public function set_encoding($text)
	{
		$encoding = 'utf-8';

		return utf8_recode($text, $encoding);
	}

	public function clean_string($string)
	{
		return utf8_clean_string($this->htmlspecialchars($string));
	}

	public function htmlspecialchars($text)
	{
		return utf8_htmlspecialchars($this->set_encoding(null_to_str($text)));
	}

	public function get_file_ext($filename)
	{
		return strtolower(substr(strrchr($filename, '.'), 1));
	}

	public function has_thumbnail($thumbnail)
	{
		return (!$thumbnail || $thumbnail == 'SMALL') ? 0 : 1;
	}

	public function import_attachment($source)
	{
		$target = $this->get_user_id($this->convert->row['uid']) . '_' . md5(unique_id());
		import_attachment($source, $target);

		if ($this->has_thumbnail($this->convert->row['thumbnail']))
		{
			_import_check('upload_path', $this->convert->row['thumbnail'], 'thumb_' . $target);
		}

		return $target;
	}

	public function flag_attachments()
	{
		$sql = 'SELECT post_msg_id
			FROM ' . ATTACHMENTS_TABLE . '
			WHERE in_message = 0
			GROUP BY post_msg_id';
		$result = $this->db->sql_query($sql);
		$post_ids = array();
		$i = 0;

		$sql = 'UPDATE ' . POSTS_TABLE . '
			SET post_attachment = 1
			WHERE ';

		while ($post_id = $this->db->sql_fetchfield('post_msg_id'))
		{
			$post_ids[] = (int) $post_id;

			if ($i >= 250)
			{
				$this->db->sql_query($sql . $this->db->sql_in_set('post_id', $post_ids));
				$post_ids = array();
				$i = 0;
			}
		}

		if (!empty($post_ids))
		{
			$this->db->sql_query($sql . $this->db->sql_in_set('post_id', $post_ids));	
		}
	}

	/**
	* Set forum flags - only prune old polls by default
	*/
	public function get_forum_flags()
	{
		// Set forum flags
		$forum_flags = 0;

		// FORUM_FLAG_LINK_TRACK
		$forum_flags += 0;

		// FORUM_FLAG_PRUNE_POLL
		$forum_flags += FORUM_FLAG_PRUNE_POLL;

		// FORUM_FLAG_PRUNE_ANNOUNCE
		$forum_flags += 0;

		// FORUM_FLAG_PRUNE_STICKY
		$forum_flags += 0;

		// FORUM_FLAG_ACTIVE_TOPICS
		$forum_flags += 0;

		// FORUM_FLAG_POST_REVIEW
		$forum_flags += FORUM_FLAG_POST_REVIEW;

		return $forum_flags;
	}

	public function get_absolute_path($path)
	{
		$current_path = getcwd();
		chdir($this->convert->options['forum_path']);
		$path = realpath($path);
		chdir($current_path);
		return $path;
	}

	public function enable_avatars()
	{
		$settings = array(
			'allow_avatar',
			'allow_avatar_local',
			'allow_avatar_remote',
			'allow_avatar_upload',
		);
		foreach ($settings as $setting)
		{
			$this->config->set($setting, true);
		}
	}

	public function convert_additional_settings()
	{
		$settings = array(
			'sitename'				=> $this->set_encoding(get_config_value('bbname')),
			'smtp_delivery'			=> $this->smtp_delivery(get_config_value('mail_handler')),
			'avatar_max_height'		=> $this->max_avatar_height(get_config_value('maxavatardims')),
			'avatar_max_width'		=> $this->max_avatar_width(get_config_value('maxavatardims')),
		);
		foreach ($settings as $setting => $value)
		{
			$this->config->set($setting, $value);
		}
	}

	public function get_config_path_value($key)
	{
		return $this->get_absolute_path(get_config_value($key)) . '/';
	}

	public function max_avatar_height($dimensions)
	{
		$dimensions = explode('x', $dimensions);
		return trim($dimensions[1]);
	}

	public function max_avatar_width($dimensions)
	{
		$dimensions = explode('x', $dimensions);
		return trim($dimensions[0]);
	}

	public function smtp_delivery($handler)
	{
		return ($handler == 'smtp') ? 1 : 0;
	}

	/**
	* Calculate the left right id's for forums. This is a recursive function.
	*/
	public function left_right_ids($groups, $parent_id, &$forums, &$node)
	{
		foreach ($groups[$parent_id] as $forum_id)
		{
			$forums[$forum_id]['left_id'] = $node++;

			if (!empty($groups[$forum_id]))
			{
				$this->left_right_ids($groups, $forum_id, $forums, $node);
			}

			$forums[$forum_id]['right_id'] = $node++;
		}
	}

	/**
	* Insert/Convert forums
	*/
	public function insert_forums()
	{
		$this->truncate_table('forums');

		$sql = 'SELECT fid, name, description, linkto, type, pid, disporder, password, open, allowpicons 
			FROM ' . $this->src_table_prefix . 'forums
			ORDER BY disporder, fid';
		$result = $this->src_query($sql);

		$forums = $forum_groups = $last_topics = array();

		while ($row = $this->src_db->sql_fetchrow($result))
		{
			$forums[$row['fid']] = $row;
			$forum_groups[$row['pid']][] = $row['fid']; 
		}
		$this->db->sql_freeresult($result);

		$node = 1;
		$this->left_right_ids($forum_groups, 0, $forums, $node);

		foreach ($forums as $forum_id => $row)
		{
			$forum_type = FORUM_POST;

			if ($row['type'] == 'c')
			{
				$forum_type = FORUM_CAT;
			}
			else if ($row['linkto'])
			{
				$forum_type = FORUM_LINK;
			}

			// Define the new forums sql ary
			$sql_ary = array(
				'forum_id'			=> (int) $row['fid'],
				'forum_name'		=> $this->htmlspecialchars($row['name']),
				'parent_id'			=> (int) $row['pid'],
				'forum_parents'		=> '',
				'forum_desc'		=> $this->htmlspecialchars($row['description']),
				'forum_type'		=> $forum_type,
				'forum_status'		=> ($row['open']) ? ITEM_UNLOCKED : ITEM_LOCKED,
				'forum_password'	=> ($row['password']) ? $this->passwords_manager->hash($row['password']) : '',
				'forum_link'		=> $row['linkto'],
				'left_id'			=> $row['left_id'],
				'right_id'			=> $row['right_id'],
				'enable_icons'		=> $row['allowpicons'],
				'enable_prune'		=> 0,
				'prune_next'		=> 0,
				'prune_days'		=> 0,
				'prune_viewed'		=> 0,
				'prune_freq'		=> 0,

				'forum_flags'		=> $this->get_forum_flags(),
				'forum_options'		=> 0,

				// Default values
				'forum_desc_bitfield'		=> '',
				'forum_desc_options'		=> 7,
				'forum_desc_uid'			=> '',
				'forum_style'				=> 0,
				'forum_image'				=> '',
				'forum_rules'				=> '',
				'forum_rules_link'			=> '',
				'forum_rules_bitfield'		=> '',
				'forum_rules_options'		=> 7,
				'forum_rules_uid'			=> '',
				'forum_topics_per_page'		=> 0,
				'forum_posts_approved'		=> 0,
				'forum_posts_unapproved'	=> 0,
				'forum_posts_softdeleted'	=> 0,
				'forum_topics_approved'		=> 0,
				'forum_topics_unapproved'	=> 0,
				'forum_topics_softdeleted'	=> 0,
				'display_on_index'			=> 1,
				'enable_indexing'			=> 1,
			);

			$sql = 'INSERT INTO ' . FORUMS_TABLE . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
			$this->db->sql_query($sql);
		}

		switch ($this->db->sql_layer)
		{
			case 'postgres':
				$this->db->sql_query("SELECT SETVAL('" . FORUMS_TABLE . "_seq',(select case when max(forum_id)>0 then max(forum_id)+1 else 1 end from " . FORUMS_TABLE . '));');
			break;

			case 'mssql':
			case 'mssql_odbc':
			case 'mssqlnative':
				$this->db->sql_query('SET IDENTITY_INSERT ' . FORUMS_TABLE . ' OFF');
			break;

			case 'oracle':
				$result = $this->db->sql_query('SELECT MAX(forum_id) as max_id FROM ' . FORUMS_TABLE);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				$largest_id = (int) $row['max_id'];

				if ($largest_id)
				{
					$this->db->sql_query('DROP SEQUENCE ' . FORUMS_TABLE . '_seq');
					$this->db->sql_query('CREATE SEQUENCE ' . FORUMS_TABLE . '_seq START WITH ' . ($largest_id + 1));
				}
			break;
		}
	}

	public function set_last_posts()
	{
		$sql = 'SELECT lastposttid
			FROM ' . $this->src_table_prefix . 'forums';
		$result = $this->src_db->sql_query($sql);
		$topics = array();

		while ($row = $this->src_db->sql_fetchrow($result))
		{
			$topics[] = (int) $row['lastposttid'];
		}
		$this->src_db->sql_freeresult($result);

		// Update the last post id of the newest topic in each forum to ensure that
		// the last post info for forums is correct when the resync occurs at the end.
		$sql = 'SELECT MAX(pid) AS post_id, tid
			FROM ' . $this->src_table_prefix . 'posts
			WHERE ' . $this->src_db->sql_in_set('tid', $topics) . '
			GROUP BY tid';
		$result = $this->src_db->sql_query($sql);

		while ($row = $this->src_db->sql_fetchrow($result))
		{
			$sql = 'UPDATE ' . TOPICS_TABLE . '
				SET topic_last_post_id = ' . (int) $row['post_id'] . '
				WHERE topic_id = ' . (int) $row['tid'];
			$this->db->sql_query($sql);
		}
		$this->src_db->sql_freeresult($result);
	}

	public function close_extraneous_reports()
	{
		// Grab the latest report for reported posts.
		$sql = 'SELECT MAX(report_id) AS report_id, post_id
			FROM ' . REPORTS_TABLE . '
			WHERE report_closed = 0
				AND post_id <> 0
			GROUP BY post_id';
		$result = $this->db->sql_query($sql);

		$open_reports = array();

		while ($row = $this->db->sql_fetchrow($result))
		{
			$open_reports[] = (int) $row['report_id'];
		}
		$this->db->sql_freeresult($result);

		if (!empty($open_reports))
		{
			// Close all other reports
			$sql = 'UPDATE ' . REPORTS_TABLE . '
				SET report_closed = 1
				WHERE post_id <> 0
					AND report_closed = 0
					AND ' . $this->db->sql_in_set('report_id', $open_reports, true);
			$this->db->sql_query($sql);
		}
	}

	public function import_smiley($source)
	{
		return import_smiley($this->get_absolute_path($source));
	}

	public function import_icon($source)
	{
		$result = _import_check('icons_path', $this->get_absolute_path($source), false);
		return $result['target'];
	}

	public function get_icon_width($source)
	{
		return $this->get_icon_dim($source, 0);
	}

	public function get_icon_height($source)
	{
		return $this->get_icon_dim($source, 1);
	}

	public function get_icon_dim($source, $axis)
	{
		$source = $this->get_absolute_path($source);
		$dimensions = get_image_dim($source);

		return $dimensions[$axis];
	}

	/**
	* Return correct user id value
	*/
	public function get_user_id($user_id)
	{
		// Increment user id if the old forum is having a user with the id 1
		if (!isset($this->config['increment_user_id']))
		{
			// Now let us set a temporary config variable for user id incrementing
			$sql = "SELECT uid
				FROM {$this->src_table_prefix}users
				WHERE uid = 1";
			$result = $this->src_query($sql);
			$id = (int) $this->src_db->sql_fetchfield('uid');
			$this->src_db->sql_freeresult($result);

			// Try to get the maximum user id possible...
			$sql = "SELECT MAX(uid) AS max_user_id
				FROM {$this->src_table_prefix}users";
			$result = $this->src_query($sql);
			$max_id = (int) $this->src_db->sql_fetchfield('max_user_id');
			$this->src_db->sql_freeresult($result);

			// If there is a user id 1, we need to increment user ids. :/
			if ($id === 1)
			{
				$this->config->set('increment_user_id', ($max_id + 1), true);
				$config['increment_user_id'] = $max_id + 1;
			}
			else
			{
				$this->config->set('increment_user_id', 0, true);
				$config['increment_user_id'] = 0;
			}
		}

		// If the old user id is -1 in 2.0.x it is the anonymous user...
		if ($user_id == -1)
		{
			return ANONYMOUS;
		}

		if (!empty($this->config['increment_user_id']) && $user_id == 1)
		{
			return $this->config['increment_user_id'];
		}

		return (int) $user_id;
	}

	/**
	* Convert authentication
	* user, group and forum table has to be filled in order to work
	*/
	public function convert_authentication($mode)
	{
		if ($mode == 'start')
		{
			$this->truncate_table('acl_users');
			$this->truncate_table('acl_groups');

			// Grab users with admin permissions
			$sql = "SELECT uid, permissions
				FROM {$this->src_table_prefix}adminoptions
				WHERE uid >= 1";
			$result = $this->src_db->sql_query($sql);
			$admins = $founders = array();

			while ($row = $this->src_db->sql_fetchrow($result))
			{
				$user_id = (int) $this->get_user_id($row['uid']);
				$permissions = unserialize($row['permissions']);
				$admins[] = $user_id;

				if ($permissions['user']['admin_permissions'])
				{
					$founders[] = $user_id;
				} 
			}
			$this->src_db->sql_freeresult($result);

			// We'll set the users that can manage admin permissions as founders.
			$sql = 'UPDATE ' . USERS_TABLE . '
				SET user_type = ' . USER_FOUNDER . "
				WHERE " . $this->db->sql_in_set('user_id', $founders);
			$this->db->sql_query($sql);

			$bot_group_id = get_group_id('bots');

			user_group_auth('guests', 'SELECT user_id, {GUESTS} FROM ' . USERS_TABLE . ' WHERE user_id = ' . ANONYMOUS, false);
			user_group_auth('registered', 'SELECT user_id, {REGISTERED} FROM ' . USERS_TABLE . ' WHERE user_id <> ' . ANONYMOUS . " AND group_id <> $bot_group_id", false);

			$auth_sql = 'SELECT user_id, {ADMINISTRATORS} FROM ' . USERS_TABLE . ' WHERE ' . $this->db->sql_in_set('user_id', $admins);
			user_group_auth('administrators', $auth_sql, false);

			$auth_sql = 'SELECT user_id, {GLOBAL_MODERATORS} FROM ' . USERS_TABLE . ' WHERE ' . $this->db->sql_in_set('user_id', $admins);
			user_group_auth('global_moderators', $auth_sql, false);

			if (!function_exists('group_set_user_default'))
			{
				include($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
			}

			// Set the admin group as their default group.
			group_set_user_default(get_group_id('administrators'), $admins);
		}
		else if ($mode == 'first')
		{
			// Assign permission roles and other default permissions

			// guests having u_download and u_search ability
			$this->db->sql_query('INSERT INTO ' . ACL_GROUPS_TABLE . ' (group_id, forum_id, auth_option_id, auth_role_id, auth_setting) SELECT ' . get_group_id('guests') . ', 0, auth_option_id, 0, 1 FROM ' . ACL_OPTIONS_TABLE . " WHERE auth_option IN ('u_', 'u_download', 'u_search')");

			// administrators/global mods having full user features
			mass_auth('group_role', 0, 'administrators', 'USER_FULL');
			mass_auth('group_role', 0, 'global_moderators', 'USER_FULL');

			// By default all converted administrators are given full access
			mass_auth('group_role', 0, 'administrators', 'ADMIN_FULL');

			// All registered users are assigned the standard user role
			mass_auth('group_role', 0, 'registered', 'USER_STANDARD');
			mass_auth('group_role', 0, 'registered_coppa', 'USER_STANDARD');

			// Instead of administrators being global moderators we give the MOD_FULL role to global mods (admins already assigned to this group)
			mass_auth('group_role', 0, 'global_moderators', 'MOD_FULL');
		}
	}

	/**
	* Convert the group name, making sure to avoid conflicts with phpBB special groups
	*/
	public function convert_group_name($group_name)
	{
		$default_groups = array(
			'GUESTS',
			'REGISTERED',
			'REGISTERED_COPPA',
			'GLOBAL_MODERATORS',
			'ADMINISTRATORS',
			'BOTS',
		);

		if (in_array(strtoupper($group_name), $default_groups))
		{
			return 'MyBB - ' . $group_name;
		}

		return $this->htmlspecialchars($group_name);
	}

	/**
	* Convert the group type constants
	*/
	public function convert_group_type($group_type)
	{
		switch ($group_type)
		{
			case 1:
			case 2:
				return GROUP_CLOSED;
			break;

			case 3:
				return GROUP_FREE;
			break;

			case 4:
				return GROUP_OPEN;
			break;
		}

		// Never return GROUP_SPECIAL here, because only phpBB3's default groups are allowed to have this type set.
		return GROUP_HIDDEN;
	}

	public function get_draft_topic_id($topic_id)
	{
		// Check if this a topic draft.
		return (int) ($this->convert->row['visible'] == -2) ? 0 : $topic_id;
	}

	public function get_topic_status($closed)
	{
		if ($closed == 1)
		{
			return ITEM_LOCKED;
		}
		else if (strpos($closed, 'moved') === 0)
		{
			return ITEM_MOVED;
		}
		return ITEM_UNLOCKED;
	}

	public function get_topic_moved_id($info)
	{
		if (strpos($info, 'moved') === false)
		{
			return 0;
		}
		return (int) str_replace('moved|', '', $info);
	}

	/**
	* Convert the topic type constants
	*/
	public function convert_topic_type($sticky)
	{
		return ($sticky) ? POST_STICKY : POST_NORMAL;
	}

	public function get_poll_length($days)
	{
		$days = (int) $days;
		return $days * 86400;
	}

	public function insert_poll_options()
	{
		$options = $this->convert->row['options'];

		if (!$options)
		{
			return;
		}

		$options = explode('||~|~||', $options);
		$options = array_map(array($this, 'htmlspecialchars'), $options);
		$votes = explode('||~|~||', $this->convert->row['votes']);
		$votes = array_map('intval', $votes);
		$options = array_combine($options, $votes);

		$sql_ary = array();
		$id = 1;

		foreach ($options as $option => $total_votes)
		{
			$sql_ary[] = array(
				'poll_option_id'	=> $id,
				'topic_id'			=> (int) $this->convert->row['tid'],
				'poll_option_text'	=> $option,
				'poll_option_total'	=> $total_votes,
			);
			$id++;
		}
		$this->db->sql_multi_insert($this->get_table('poll_options'), $sql_ary);
	}

	public function get_visibility($visibility)
	{
		switch ($visibility)
		{
			case 0:
				return ITEM_UNAPPROVED;
			break;

			case -1:
				return ITEM_DELETED;
			break;
		}
		return ITEM_APPROVED;
	}

	public function convert_word_replacement($replacement)
	{
		if ($replacement)
		{
			return $this->htmlspecialchars($replacement);
		}
		return str_repeat('*', strlen($this->convert->row['badword']));
	}

	/**
	* Reformat inline attachment bbcode to the proper phpBB format.
	*/
	public function reformat_inline_attach(&$message, $post_id)
	{
		// Do a simple check for the presence of inline attachments.
		if (strpos($message, '[attachment=') !== false)
		{
			// The open tag is the same as phpBB's, so temporarily replace it to avoid replacements occurring more than once.
			$message = str_replace('[attachment=', '[ATTACH=', $message);

			// We need to grab some info from the database :-/
			$sql = 'SELECT aid, filename
				FROM ' . $this->src_table_prefix . 'attachments
				WHERE pid = ' . (int) $post_id . '
				ORDER BY aid DESC';
			$result = $this->src_query($sql);
			$i = 0;

			while ($row = $this->src_db->sql_fetchrow($result))
			{
				$find = "[ATTACH={$row['aid']}]";
				$replace = '[attachment=' . $i . ']' . $row['filename'] . '[/attachment]';

				$message = str_replace($find, $replace, $message);
				$i++;
			}
			$this->src_db->sql_freeresult($result);

			// Change back any that weren't replaced.
			$message = str_replace('[ATTACH=', '[attachment=', $message);
		}
	}

	public function fix_size_bbcode($match)
	{
		$sizes = array(
			'xx-small'	=> 70,
			'x-small'	=> 75,
			'small'		=> 100,
			'medium'	=> 125,
			'large'		=> 140,
			'x-large'	=> 190,
			'xx-large'	=> 200,
		);

		if (!isset($sizes[$match[1]]))
		{
			return $match[2];
		}
		return '[size=' . $sizes[$match[1]] . ']';
	}

	/**
	* Wrap smiley codes with space so phpBB can parse them correctly.
	*/
	public function reformat_smilies(&$message)
	{
		if (empty($this->message_parser))
		{
			$this->set_message_parser(new \parse_message);
		}

		if (!$this->is_stored('smilies'))
		{
			$sql = 'SELECT find
				FROM ' . $this->src_table_prefix . 'smilies';
			$result = $this->src_query($sql);
			$smilies = array();

			while ($row = $this->src_db->sql_fetchrow($result))
			{
				$smilies[] = $row['find'];
			}
			$this->src_db->sql_freeresult($result);

			if (empty($smilies))
			{
				return;
			}

			$smilies = array_map('trim', $smilies);
			$smilies = array_combine($smilies, $smilies);
			$smilies = array_map(array($this, 'pad_smiley'), $smilies);
			$this->store('smilies', $smilies);
		}
		$smilies = $this->get_stored('smilies');

		if (!empty($smilies))
		{
			$message = strtr($message, $smilies);
		}
	}

	/**
	* Wrap smiley code with space.
	*/
	public function pad_smiley($smiley)
	{
		return " $smiley ";
	}

	/**
	* Reparse the message stripping out the bbcode_uid values and adding new ones and setting the bitfield
	*/
	public function prepare_message($message)
	{
		if (!$message || !empty($this->convert->row['is_duplicate']))
		{
			$this->convert->row['mp_bbcode_bitfield'] = 0;
			return '';
		}

		if (!empty($this->convert->row['pid']))
		{
			$this->reformat_inline_attach($message, $this->convert->row['pid']);
		}

		$this->reformat_smilies($message);

		$message = preg_replace_callback('#\[size=(.*?)\]#s', array($this, 'fix_size_bbcode'), $message);

		$bbcodes = array(
			"#\[quote='(.*?)'(?:.*?)\]#i"	=> '[quote=&quot;\\1&quot;]',
			"#\[img=\d+x\d+\]#i"			=> '[img]',
		);
		$message = preg_replace(array_keys($bbcodes), $bbcodes, $message);

		$bbcodes = array(
			'[php]'		=> '[code=php]',
			'[/php]'	=> '[/code]',
			'[hr]'		=> '[hr][/hr]',
		);

		$message = str_replace(array_keys($bbcodes), $bbcodes, $message);

		$message = str_replace('<br />', "\n", $message);
		$message = str_replace('<', '&lt;', $message);
		$message = str_replace('>', '&gt;', $message);

		// make the post UTF-8
		$message = $this->set_encoding($message);

		$this->message_parser->warn_msg = array(); // Reset the errors from the previous message
		$this->message_parser->bbcode_uid = make_uid($this->convert->row['dateline']);
		$this->message_parser->message = $message;
		unset($message);

		// Make sure options are set.
		$enable_bbcode = true;
		$enable_smilies = (!isset($this->convert->row['smilieoff'])) ? true : !$this->convert->row['smilieoff'];
		$enable_magic_url = true;

		$this->message_parser->parse($enable_bbcode, $enable_magic_url, $enable_smilies);

		if (sizeof($this->message_parser->warn_msg))
		{
			$msg_id = isset($this->convert->row['pid']) ? $this->convert->row['pid'] : $this->convert->row['pmid'];
			$this->convert->p_master->error(
				'<span style="color:red">' . $this->user->lang['POST_ID'] . ': ' . $msg_id . ' ' .
					$this->user->lang['CONV_ERROR_MESSAGE_PARSER'] . ': <br /><br />' .
					implode('<br />', $this->message_parser->warn_msg),
				__LINE__,
				__FILE__,
				true
			);
		}

		$this->convert->row['mp_bbcode_bitfield'] = $this->message_parser->bbcode_bitfield;

		$message = $this->message_parser->message;
		unset($this->message_parser->message);

		return $message;
	}

	/**
	* Return the bitfield calculated by the previous function
	*/
	public function get_bbcode_bitfield()
	{
		return $this->convert->row['mp_bbcode_bitfield'];
	}

	public function prefix_hash($hash)
	{
		return '$CP$' . $hash;
	}

	/**
	* Convert the avatar type constants
	*/
	public function get_avatar_type($type)
	{
		switch ($type)
		{
			case 'upload':
				return AVATAR_UPLOAD;
			break;

			case 'remote':
			case 'gravatar':
				return AVATAR_REMOTE;
			break;

			case 'gallery':
				return AVATAR_GALLERY;
			break;
		}

		return 0;
	}

	/**
	* Transfer avatars, copying the image if it was uploaded
	*/
	public function import_avatar($user_avatar)
	{
		if ($user_avatar)
		{
			$date_pos = strpos($user_avatar, '?dateline=');

			if ($date_pos !== false)
			{
				$user_avatar = substr($user_avatar, 0, $date_pos);
			}
		}

		switch ($this->convert->row['avatartype'])
		{
			case 'upload':
				$user_avatar = $this->get_absolute_path($user_avatar);
				return import_avatar($user_avatar, false, $this->get_user_id($this->convert->row['uid']));
			break;

			case 'remote':
			case 'gravatar':
				return $user_avatar;
			break;

			case 'gallery':
				$source = $this->get_absolute_path($user_avatar);
				return substr($source, strlen($this->convert->convertor['avatar_gallery_path']));
			break;
			
		}
		return '';
	}


	/**
	* Find out about the avatar's dimensions
	*/
	public function get_avatar_height($dimensions)
	{
		if (!$dimensions)
		{
			return 0;
		}
		$dimensions = explode('|', $dimensions);
		return (int) $dimensions[1];
	}


	/**
	* Find out about the avatar's dimensions
	*/
	public function get_avatar_width($dimensions)
	{
		if (!$dimensions)
		{
			return 0;
		}
		$dimensions = explode('|', $dimensions);
		return (int) $dimensions[0];
	}

	public function user_notify($subscription_type)
	{
		return ($subscription_type == 2) ? 1 : 0;
	}

	/**
	* From phpbb/db/migration/data/v310/timezone.php
	*/
	public function get_timezone($timezone)
	{
		$offset = $timezone + $this->convert->row['dst'];

		switch ($timezone)
		{
			case '-12':
				return 'Etc/GMT+' . abs($offset);	//'[UTC - 12] Baker Island Time'
			case '-11':
				return 'Etc/GMT+' . abs($offset);	//'[UTC - 11] Niue Time, Samoa Standard Time'
			case '-10':
				return 'Etc/GMT+' . abs($offset);	//'[UTC - 10] Hawaii-Aleutian Standard Time, Cook Island Time'
			case '-9.5':
				return 'Pacific/Marquesas';			//'[UTC - 9:30] Marquesas Islands Time'
			case '-9':
				return 'Etc/GMT+' . abs($offset);	//'[UTC - 9] Alaska Standard Time, Gambier Island Time'
			case '-8':
				return 'Etc/GMT+' . abs($offset);	//'[UTC - 8] Pacific Standard Time'
			case '-7':
				return 'Etc/GMT+' . abs($offset);	//'[UTC - 7] Mountain Standard Time'
			case '-6':
				return 'Etc/GMT+' . abs($offset);	//'[UTC - 6] Central Standard Time'
			case '-5':
				return 'Etc/GMT+' . abs($offset);	//'[UTC - 5] Eastern Standard Time'
			case '-4.5':
				return 'America/Caracas';			//'[UTC - 4:30] Venezuelan Standard Time'
			case '-4':
				return 'Etc/GMT+' . abs($offset);	//'[UTC - 4] Atlantic Standard Time'
			case '-3.5':
				return 'America/St_Johns';			//'[UTC - 3:30] Newfoundland Standard Time'
			case '-3':
				return 'Etc/GMT+' . abs($offset);	//'[UTC - 3] Amazon Standard Time, Central Greenland Time'
			case '-2':
				return 'Etc/GMT+' . abs($offset);	//'[UTC - 2] Fernando de Noronha Time, South Georgia &amp; the South Sandwich Islands Time'
			case '-1':
				return 'Etc/GMT+' . abs($offset);	//'[UTC - 1] Azores Standard Time, Cape Verde Time, Eastern Greenland Time'
			case '0':
				return (!$this->convert->row['dst']) ? 'UTC' : 'Etc/GMT-1';	//'[UTC] Western European Time, Greenwich Mean Time'
			case '1':
				return 'Etc/GMT-' . $offset;		//'[UTC + 1] Central European Time, West African Time'
			case '2':
				return 'Etc/GMT-' . $offset;		//'[UTC + 2] Eastern European Time, Central African Time'
			case '3':
				return 'Etc/GMT-' . $offset;		//'[UTC + 3] Moscow Standard Time, Eastern African Time'
			case '3.5':
				return 'Asia/Tehran';				//'[UTC + 3:30] Iran Standard Time'
			case '4':
				return 'Etc/GMT-' . $offset;		//'[UTC + 4] Gulf Standard Time, Samara Standard Time'
			case '4.5':
				return 'Asia/Kabul';				//'[UTC + 4:30] Afghanistan Time'
			case '5':
				return 'Etc/GMT-' . $offset;		//'[UTC + 5] Pakistan Standard Time, Yekaterinburg Standard Time'
			case '5.5':
				return 'Asia/Kolkata';				//'[UTC + 5:30] Indian Standard Time, Sri Lanka Time'
			case '5.75':
				return 'Asia/Kathmandu';			//'[UTC + 5:45] Nepal Time'
			case '6':
				return 'Etc/GMT-' . $offset;		//'[UTC + 6] Bangladesh Time, Bhutan Time, Novosibirsk Standard Time'
			case '6.5':
				return 'Indian/Cocos';				//'[UTC + 6:30] Cocos Islands Time, Myanmar Time'
			case '7':
				return 'Etc/GMT-' . $offset;		//'[UTC + 7] Indochina Time, Krasnoyarsk Standard Time'
			case '8':
				return 'Etc/GMT-' . $offset;		//'[UTC + 8] Chinese Standard Time, Australian Western Standard Time, Irkutsk Standard Time'
			case '8.75':
				return 'Australia/Eucla';			//'[UTC + 8:45] Southeastern Western Australia Standard Time'
			case '9':
				return 'Etc/GMT-' . $offset;		//'[UTC + 9] Japan Standard Time, Korea Standard Time, Chita Standard Time'
			case '9.5':
				return 'Australia/ACT';				//'[UTC + 9:30] Australian Central Standard Time'
			case '10':
				return 'Etc/GMT-' . $offset;		//'[UTC + 10] Australian Eastern Standard Time, Vladivostok Standard Time'
			case '10.5':
				return 'Australia/Lord_Howe';		//'[UTC + 10:30] Lord Howe Standard Time'
			case '11':
				return 'Etc/GMT-' . $offset;		//'[UTC + 11] Solomon Island Time, Magadan Standard Time'
			case '11.5':
				return 'Pacific/Norfolk';			//'[UTC + 11:30] Norfolk Island Time'
			case '12':
				return 'Etc/GMT-12';				//'[UTC + 12] New Zealand Time, Fiji Time, Kamchatka Standard Time'
			case '12.75':
				return 'Pacific/Chatham';			//'[UTC + 12:45] Chatham Islands Time'
			case '13':
				return 'Pacific/Tongatapu';			//'[UTC + 13] Tonga Time, Phoenix Islands Time'
			case '14':
				return 'Pacific/Kiritimati';		//'[UTC + 14] Line Island Time'
			default:
				return 'UTC';
		}
	}

	public function insert_custom_folders()
	{
		$user_id = $this->get_user_id($this->convert->row['uid']);
		$folders = $this->set_encoding($this->convert->row['pmfolders']);
		$folders = explode('$%%$', $folders);
		$insert_ary = array();

		foreach ($folders as $folder)
		{
			if (!$folder)
			{
				continue;
			}
			$info = explode('**', $folder);
			$folder_id = (int) $info[0];

			if ($folder_id < 5 || empty($info[1]))
			{
				continue;
			}
			$insert_ary[] = array(
				'user_id'		=> $user_id,
				'folder_name'	=> $info[1],
				'pm_count'		=> 0,
			);
		}

		if (!empty($insert_ary))
		{
			$this->db->sql_multi_insert(PRIVMSGS_FOLDER_TABLE, $insert_ary);
		}
	}

	public function get_folder_id($folder)
	{
		$user_id = $this->get_user_id($this->convert->row['uid']);
		$stored_id = "folders_$user_id";
		$folder = (int) $folder;

		if (!$this->is_stored($stored_id))
		{
			$sql = 'SELECT folder_id
				FROM ' . PRIVMSGS_FOLDER_TABLE . '
				WHERE user_id = ' . $user_id . '
				ORDER BY folder_id ASC';
			$result = $this->db->sql_query($sql);
			$folders = array();
			$i = 5;

			while ($folder_id = $this->db->sql_fetchfield('folder_id'))
			{
				$folders[$i] = (int) $folder_id;
				$i++;
			}
			$this->db->sql_freeresult($result);
			$this->store($stored_id, $folders);
		}
		$folders = $this->get_stored($stored_id);

		return (isset($folders[$folder])) ? $folders[$folder] : PRIVMSGS_INBOX;
	}

	public function check_duplicate_pm()
	{
		$store_id = 'pm_' . $this->convert->row['poster_id'] . '_' . $this->convert->row['dateline'];

		if ($this->is_stored($store_id))
		{
			$this->convert->row['is_duplicate'] = true;
			return 1;
		}
		$this->store($store_id, true);
		$this->convert->row['is_duplicate'] = false;

		return 0;
	}

	/**
	* Calculate the correct to_address field for private messages
	*/
	public function pm_to_recipients($recipients)
	{
		return $this->pm_recipients($recipients, 'to');
	}

	public function pm_bcc_recipients($recipients)
	{
		return $this->pm_recipients($recipients, 'bcc');
	}

	public function pm_recipients($recipients, $var)
	{
		$recipients = unserialize($recipients);

		if (empty($recipients[$var]))
		{
			return '';
		}
		$to = array();

		foreach ($recipients[$var] as $user_id)
		{
			$to[] = 'u_' . $this->get_user_id($user_id);
		}

		return implode(':', $to);	
	}


	/**
	* Adjust disallowed names to phpBB format
	*/
	public function disallowed_username($username)
	{
		// Replace * with %
		return str_replace('*', '%', $this->htmlspecialchars($username));
	}

	public function rank_min($minimum)
	{
		$minimum = (int) $minimum;
		return ($minimum < 0) ? 0 : $minimum;
	}

	public function insert_additional_groups($groups)
	{
		if (!$groups)
		{
			return;
		}

		$user_id = $this->get_user_id($this->convert->row['uid']);
		$groups = explode(',', $groups);
		$insert_ary = array();

		foreach ($groups as $group_id)
		{
			$insert_ary[] = array(
				'group_id'		=> (int) $group_id,
				'user_id'		=> $user_id,
				'group_leader'	=> 0,
				'user_pending'	=> 0,
			);
		}

		if (!empty($insert_ary))
		{
			$this->db->sql_multi_insert(USER_GROUP_TABLE, $insert_ary);
		}
	}

	public function insert_zebra()
	{
		$user_id = $this->get_user_id($this->convert->row['uid']);
		$this->insert_zebra_rows($this->convert->row['ignorelist'], false, $user_id);
		$this->insert_zebra_rows($this->convert->row['buddylist'], true, $user_id);
	}

	public function insert_zebra_rows($list, $friend, $user_id)
	{
		if (!$list)
		{
			return;
		}
		$list = explode(',', $list);
		$insert_ary = array();

		foreach ($list as $zebra_id)
		{
			$insert_ary[] = array(
				'user_id'	=> $user_id,
				'zebra_id'	=> $this->get_user_id($zebra_id),
				'friend'	=> $friend,
				'foe'		=> !$friend,
			);
		}
		$this->db->sql_multi_insert(ZEBRA_TABLE, $insert_ary);
	}

	/**
	* Create temporary conversion table to store info about colliding usernames.
	*/
	public function create_userconv_table()
	{
		$userconv_table = $this->get_table('userconv');

		$changes = array(
			'drop_tables'	=> array(
				$userconv_table,
			),
			'add_tables'	=> array(
				$userconv_table	=> array(
					'COLUMNS'	=> array(
						'user_id'			=> array('UINT', 0),
						'username_clean'	=> array('VCHAR_CI', ''),
					),
				),
			),
		);
		$this->db_tools->perform_schema_changes($changes);
	}

	/**
	* Check whether the location field exists.
	*/
	public function user_from_col_exists()
	{
		$db_tools = new \phpbb\db\tools($this->src_db);

		return $db_tools->sql_column_exists($this->src_table_prefix . 'userfields', 'fid1');
	}

	/**
	* Add password salt field to the users table.
	*/
	public function add_user_salt_field()
	{
		$schema_changes = array(
			'add_columns'	=> array(
				USERS_TABLE	 => array(
					'user_passwd_salt'	=> array('VCHAR:10', ''),
				)
			)
		);

		$this->db_tools->perform_schema_changes($schema_changes);
	}

	/**
	* Create additional needed bbcodes so we can correctly transfer post formats
	*/
	public function create_bbcodes()
	{
		$templates = array(
			'[align={SIMPLETEXT}]{TEXT}[/align]'	=> '<div style="text-align: {SIMPLETEXT};">{TEXT}</div>',
			'[font={SIMPLETEXT}]{TEXT}[/font]'		=> '<span style="font-family: {SIMPLETEXT};">{TEXT}</span>',
			'[hr][/hr]'								=> '<hr />',
			'[s]{TEXT}[/s]'							=> '<span style="text-decoration: line-through;">{TEXT}</span>',
		);

		if (!class_exists('acp_bbcodes'))
		{
			include($this->phpbb_root_path . 'includes/acp/acp_bbcodes.' . $this->php_ext);
		}

		$bbcode = new \acp_bbcodes();
		$bbcode_settings = array();

		// Get the bbcode regex
		foreach ($templates as $match => $tpl)
		{
			$settings = $bbcode->build_regexp($match, $tpl);

			$bbcode_settings[$settings['bbcode_tag']] = array_merge($settings, array(
				'bbcode_match'			=> $match,
				'bbcode_tpl'			=> $tpl,
				'display_on_posting'	=> 1,
				'bbcode_helpline'		=> '',
			));
		}

		$sql = 'SELECT MAX(bbcode_id) AS max_bbcode_id
			FROM ' . BBCODES_TABLE;
		$result = $this->db->sql_query($sql);
		$max_bbcode_id = (int) $this->db->sql_fetchfield('max_bbcode_id');
		$this->db->sql_freeresult($result);

		if ($max_bbcode_id)
		{
			$sql = 'SELECT bbcode_tag
				FROM ' . BBCODES_TABLE . '
				WHERE ' . $this->db->sql_in_set('bbcode_tag', array_keys($bbcode_settings));
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				unset($bbcode_settings[$row['bbcode_tag']]);
			}
			$this->db->sql_freeresult($result);
		}
		else
		{
			$max_bbcode_id = NUM_CORE_BBCODES;
		}

		if (!empty($bbcode_settings))
		{
			// Reset the keys so that sql_multi_insert works...
			$bbcode_settings = array_values($bbcode_settings);

			foreach ($bbcode_settings as $index => $bbcode)
			{
				$bbcode_settings[$index]['bbcode_id'] = ++$max_bbcode_id;
			}
			$this->db->sql_multi_insert(BBCODES_TABLE, $bbcode_settings);
		}
	}

	public function check_username_collisions()
	{
		global $lang;

		$userconv_table = $this->get_table('userconv');

		// now find the clean version of the usernames that collide
		$sql = "SELECT username_clean
			FROM $userconv_table
			GROUP BY username_clean
			HAVING COUNT(user_id) > 1";
		$result = $this->db->sql_query($sql);

		$colliding_names = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$colliding_names[] = $row['username_clean'];
		}
		$this->db->sql_freeresult($result);

		// there was at least one collision, the admin will have to solve it before conversion can continue
		if (sizeof($colliding_names))
		{
			$sql = "SELECT user_id, username_clean
				FROM $userconv_table
				WHERE " . $this->db->sql_in_set('username_clean', $colliding_names);
			$result = $this->db->sql_query($sql);
			unset($colliding_names);

			$colliding_user_ids = array();
			while ($row = $this->db->sql_fetchrow($result))
			{
				$colliding_user_ids[(int) $row['user_id']] = $row['username_clean'];
			}
			$this->db->sql_freeresult($result);

			$sql = 'SELECT username, uid, postnub
				FROM ' . $this->src_table_prefix . 'users
				WHERE ' . $this->src_db->sql_in_set('uid', array_keys($colliding_user_ids));
			$result = $this->src_db->sql_query($sql);

			$colliding_users = array();
			while ($row = $this->src_db->sql_fetchrow($result))
			{
				$row['user_id'] = (int) $row['id'];
				if (isset($colliding_user_ids[$row['user_id']]))
				{
					$colliding_users[$colliding_user_ids[$row['user_id']]][] = $row;
				}
			}
			$this->src_db->sql_freeresult($result);
			unset($colliding_user_ids);

			$list = '';
			foreach ($colliding_users as $username_clean => $users)
			{
				$list .= $this->user->lang('COLLIDING_CLEAN_USERNAME', $username_clean) . "<br />\n";
				foreach ($users as $i => $row)
				{
					$list .= $this->user->lang('COLLIDING_USER', $row['user_id'], $this->set_encoding($row['username']), $row['numposts']) . "<br />\n";
				}
			}

			$lang['INST_ERR_FATAL'] = $this->user->lang['CONV_ERR_FATAL'];
			$this->convert->p_master->error(
				'<span style="color:red">' . $this->user->lang['COLLIDING_USERNAMES_FOUND'] . '</span></b><br /><br />' .
				$list . '<b>', __LINE__, __FILE__
			);
		}

		$changes = array(
			'drop_tables' => array($userconv_table),
		);
		$this->db_tools->perform_schema_changes($changes);
	}

	public function src_query($sql)
	{
		if ($this->convert->mysql_convert && $this->same_db)
		{
			$this->src_db->sql_query("SET NAMES 'binary'");
		}

		$result = $this->src_db->sql_query($sql);

		if ($this->convert->mysql_convert && $this->same_db)
		{
			$this->src_db->sql_query("SET NAMES 'utf8'");
		}
		return $result;
	}

	public function get_truncate_statement($table)
	{
		return array('target', $this->convert->truncate_statement . $this->table_prefix . $table);
	}

	public function truncate_table($table)
	{
		$this->db->sql_query($this->convert->truncate_statement . $this->table_prefix . $table);
	}

	public function get_table($table)
	{
		return $this->table_prefix . $table;
	}
}
