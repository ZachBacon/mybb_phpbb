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

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

$phpbb_config_php_file = new \phpbb\config_php_file($phpbb_root_path, $phpEx);
extract($phpbb_config_php_file->get_all());
unset($dbpasswd);

/**
* $convertor_data provides some basic information about this convertor which is
* used on the initial list of convertors and to populate the default settings
*/
$convertor_data = array(
	'forum_name'	=> 'MyBB 1.8.x',
	'version'		=> '1.0.3',
	'phpbb_version'	=> '3.2.2',
	'author'		=> '<a href="https://www.phpbb.com/community/memberlist.php?mode=viewprofile&u=304651">prototech</a>',
	'dbms'			=> $dbms,
	'dbhost'		=> $dbhost,
	'dbport'		=> $dbport,
	'dbuser'		=> $dbuser,
	'dbpasswd'		=> '',
	'dbname'		=> $dbname,
	'table_prefix'	=> 'mybb_',
	'forum_path'	=> '../forums',
	'author_notes'	=> '',
);

/**
* $tables is a list of the tables (minus prefix) which we expect to find in the
* source forum. It is used to guess the prefix if the specified prefix is incorrect
*/
$tables = array(
	'adminlog',
	'adminoptions',
	'adminsessions',
	'adminviews',
	'announcements',
	'attachments',
	'attachtypes',
	'awaitingactivation',
	'badwords',
	'banfilters',
	'banned',
	'buddyrequests',
	'calendarpermissions',
	'calendars',
	'captcha',
	'datacache',
	'delayedmoderation',
	'events',
	'forumpermissions',
	'forums',
	'forumsread',
	'forumsubscriptions',
	'groupleaders',
	'helpdocs',
	'helpsections',
	'icons',
	'joinrequests',
	'mailerrors',
	'maillogs',
	'mailqueue',
	'massemails',
	'moderatorlog',
	'moderators',
	'modtools',
	'mycode',
	'polls',
	'pollvotes',
	'posts',
	'privatemessages',
	'profilefields',
	'promotionlogs',
	'promotions',
	'questions',
	'questionsessions',
	'reportedcontent',
	'reputation',
	'searchlog',
	'sessions',
	'settinggroups',
	'settings',
	'smilies',
	'spamlog',
	'spiders',
	'stats',
	'tasklog',
	'tasks',
	'templategroups',
	'templates',
	'templatesets',
	'themes',
	'themestylesheets',
	'threadprefixes',
	'threadratings',
	'threads',
	'threadsread',
	'threadsubscriptions',
	'threadviews',
	'userfields',
	'usergroups',
	'users',
	'usertitles',
	'warninglevels',
	'warnings',
	'warningtypes',
);

/**
* $config_schema details how the board configuration information is stored in the source forum.
*
* 'table_format' can take the value 'file' to indicate a config file. In this case array_name
* is set to indicate the name of the array the config values are stored in
* Example of using a file:
* $config_schema = array(
* 	'table_format'	=>	'file',
* 	'filename'	=>	'NAME OF FILE', // If the file is not in the root directory, the path needs to be added with no leading slash
* 	'array_name' => 'NAME OF ARRAY', // Only used if the configuration file stores the setting in an array.
* 	'settings'		=>	array(
*        'board_email' => 'SUPPORT_EMAIL', // target config name => source target name
* 	)
* );
* 'table_format' can be an array if the values are stored in a table which is an assosciative array
* (as per phpBB 2.0.x)
* If left empty, values are assumed to be stored in a table where each config setting is
* a column (as per phpBB 1.x)
*
* In either of the latter cases 'table_name' indicates the name of the table in the database
*
* 'settings' is an array which maps the name of the config directive in the source forum
* to the config directive in phpBB3. It can either be a direct mapping or use a function.
* Please note that the contents of the old config value are passed to the function, therefore
* an in-built function requiring the variable passed by reference is not able to be used. Since
* empty() is such a function we created the function is_empty() to be used instead.
*/
$config_schema = array(
	'table_name'	=>	'settings',
	'table_format'	=>	array('name' => 'value'),
	'settings'		=>	array(
		'allow_emailreuse'		=> 'allowmultipleemails',
		'allow_privmsg'			=> 'enablepms',
		'allow_quick_reply'		=> 'quickreply',
		'allow_sig_bbcode'		=> 'sigmycode',
		'allow_sig_img'			=> 'sigimgcode',
		'allow_sig_smilies'		=> 'sigsmilies',
		'auth_bbcode_pm'		=> 'pmsallowmycode',
		'auth_img_pm'			=> 'pmsallowimgcode',
		'auth_smilies_pm'		=> 'pmsallowsmilies',
		'board_timezone'		=> 'timezoneoffset',
		'edit_time'				=> 'edittimelimit',
		'flood_interval'		=> 'postfloodsecs',
		'gzip_compress'			=> 'gzipoutput',
		'limit_load'			=> 'load',
		'load_db_track'			=> 'dotfolders',
		'load_jumpbox'			=> 'enableforumjump',
		'hot_threshold'			=> 'hottopic',
		'min_name_chars'		=> 'minnamelength',
		'min_pass_chars'		=> 'minpasswordlength',
		'min_post_chars'		=> 'minmessagelength',
		'max_attachments'		=> 'maxattachments',
		'max_login_attempts'	=> 'failedlogincount',
		'max_name_chars'		=> 'maxnamelength',
		'max_pass_chars'		=> 'maxpasswordlength',
		'max_poll_options'		=> 'maxpolloptions',
		'max_post_chars'		=> 'maxmessagelenfth',
		'max_quote_depth'		=> 'maxquotedepth',
		'max_sig_chars'			=> 'siglength',
		'posts_per_page'		=> 'postsperpage',
		'topics_per_page'		=> 'threadsperpage',
		'smtp_host'				=> 'smtp_host',
		'smtp_username'			=> 'smtp_user',
		'smtp_password'			=> 'smtp_password',
	)
);

/**
* $test_file is the name of a file which is present on the source
* forum which can be used to check that the path specified by the
* user was correct
*/
$test_file = 'editpost.php';

/**
* If this is set then we are not generating the first page of information but getting the conversion information.
*/
if (!$get_info)
{
	if (empty($convert->conversion_started))
	{
		global $phpbb_container;

		$convert->conversion_started = true;
		$helper = new \prototech\mybb18_phpbb31_convertor\helper(
			$convert,
			$config,
			$db,
			$src_db,
			new \phpbb\db\tools($db),
			$user,
			$phpbb_container->get('passwords.manager'),
			$convert->src_table_prefix,
			$table_prefix,
			$same_db,
			$phpbb_root_path,
			$phpEx
		);

		// Allow us to use the helper inside the jump() function.
		if ($request->is_set('jump'))
		{
			$convert->helper = $helper;
		}
	}

	// Overwrite maximum avatar width/height
	@define('DEFAULT_AVATAR_X_CUSTOM', get_config_value('avatar_max_width'));
	@define('DEFAULT_AVATAR_Y_CUSTOM', get_config_value('avatar_max_height'));

	// Check whether the user location field exists.
	@define('USER_FROM_EXISTS', $helper->user_from_col_exists());

	$convertor = array(
		'test_file'				=> 'editpost.php',

		// The avatar sources in the db are from the board root.
		'avatar_path'			=> $helper->get_config_path_value('avataruploadpath'),
		'avatar_gallery_path'	=> $helper->get_config_path_value('avatardir'),
		'smilies_path'			=> $helper->get_absolute_path('images/smilies'),
		'upload_path'			=> $helper->get_config_path_value('uploadspath'),
		'icons_path'			=> '',
		'thumbnails'			=> '',
		'ranks_path'			=> $helper->get_absolute_path('images/groupimages'),
		'source_path_absolute'	=> true,

		// We empty some tables to have clean data available
		'query_first'			=> array(
			$helper->get_truncate_statement('search_results'),
			$helper->get_truncate_statement('search_wordlist'),
			$helper->get_truncate_statement('search_wordmatch'),
			$helper->get_truncate_statement('log'),
		),

		'execute_first'	=> '
			$helper->run_initial_tasks();
		',

		'execute_last'	=> array('
			add_bots();
		', '
			update_folder_pm_count();
		', '
			update_unread_count();
		', '
			$convert->helper->close_extraneous_reports();
		', '
			$convert->helper->set_last_posts();
		', '
			$convert->helper->flag_attachments();
		', '
			$convert->helper->convert_authentication(\'start\');
		', '
			$convert->helper->convert_authentication(\'first\');
		'),

		'schema' => array(
			array(
				'target'		=> $helper->get_table('userconv'),
				'query_first'   => $helper->get_truncate_statement('userconv'),

				array('user_id',			'users.uid',		 				''),
				array('username_clean',		'users.username',					array($helper, 'clean_string')),
			),

			array(
				'target'		=> $helper->get_table('attachments'),
				'primary'		=> 'attachments.aid',
				'query_first'	=> $helper->get_truncate_statement('attachments'),
				'autoincrement'	=> 'attach_id',

				array('attach_id',				'attachments.aid',				''),
				array('post_msg_id',			'attachments.pid',				''),
				array('topic_id',				'posts.tid',					''),
				array('in_message',				0,								''),
				array('is_orphan',				0,								''),
				array('poster_id',				'attachments.uid',				array($helper, 'get_user_id')),
				array('physical_filename',		'attachments.attachname',		array($helper, 'import_attachment')),
				array('real_filename',			'attachments.filename',			array($helper, 'htmlspecialchars')),
				array('download_count',			'attachments.downloads',		''),
				array('attach_comment',			'',								''),
				array('extension',				'attachments.filename',			array($helper, 'get_file_ext')),
				array('mimetype',				'attachments.filetype',			''),
				array('filesize',				'attachments.filesize',			''),
				array('filetime',				'attachments.dateuploaded',		''),
				array('thumbnail',				'attachments.thumbnail',		array($helper, 'has_thumbnail')),

				'where'			=> 'posts.pid = attachments.pid',
			),

			array(
				'target'		=> $helper->get_table('banlist'),
				'execute_first'	=> '$helper->check_username_collisions();',
				'query_first'	=> $helper->get_truncate_statement('banlist'),

				array('ban_ip',					'',								''),
				array('ban_userid',				'banned.uid',					array($helper, 'get_user_id')),
				array('ban_email',				'',								''),
				array('ban_reason',				'banned.reason',				array($helper, 'htmlspecialchars')),
				array('ban_give_reason',		'',								''),
				array('ban_start',				'banned.dateline',				''),
				array('ban_end',				'banned.lifted',				''),
			),

			array(
				'target'		=> $helper->get_table('banlist'),

				array('ban_ip',					'banfilters.filter',			array($helper, 'htmlspecialchars')),

				'where'			=> 'type = 1',
			),

			array(
				'target'		=> $helper->get_table('disallow'),
				'query_first'	=> $helper->get_truncate_statement('disallow'),

				array('disallow_username',		'banfilters.filter',			array($helper, 'disallowed_username')),

				'where'		=> 'type = 2',
			),

			array(
				'target'		=> $helper->get_table('ranks'),
				'query_first'	=> $helper->get_truncate_statement('ranks'),
				'autoincrement'	=> 'rank_id',

				array('rank_id',					'usertitles.utid',			''),
				array('rank_title',					'usertitles.title',			array($helper, 'htmlspecialchars')),
				array('rank_min',					'usertitles.posts',			array($helper, 'rank_min')),
				array('rank_special',				0,							''),
				array('rank_image',					'',							'import_rank'),
			),


			array(
				'target'		=> $helper->get_table('icons'),
				'query_first'	=> $helper->get_truncate_statement('icons'),
				'autoincrement'	=> 'icons_id',

				array('icons_id',					'icons.iid',				''),
				array('icons_url',					'icons.path',				array($helper, 'import_icon')),
				array('icons_width',				'icons.path',				array($helper, 'get_icon_width')),
				array('icons_height',				'icons.path',				array($helper, 'get_icon_height')),
				array('icons_order',				'icons.iid',				''),
				array('display_on_posting',			1,							''),
			),

			array(
				'target'		=> $helper->get_table('topics'),
				'query_first'	=> array(
					$helper->get_truncate_statement('topics'),
					$helper->get_truncate_statement('poll_options'),
				),
				'primary'		=> 'threads.tid',
				'autoincrement'	=> 'topic_id',

				array('topic_id',				'threads.tid',					''),
				array('forum_id',				'threads.fid',					''),
				array('icon_id',				'threads.icon',					''),
				array('topic_poster',			'threads.uid AS poster_id',		array($helper, 'get_user_id')),
				array('topic_attachment',		'threads.attachmentcount',		array($helper, 'not_empty')),
				array('topic_title',			'threads.subject',				array($helper, 'htmlspecialchars')),
				array('topic_time',				'threads.dateline',				'intval'),
				array('topic_views',			'threads.views',				''),
				array('topic_posts_approved',	'threads.replies',				''),
				array('topic_posts_unapproved',	'threads.unapprovedposts',		''),
				array('topic_status',			'threads.closed',				array($helper, 'get_topic_status')),
				array('topic_moved_id',			'threads.closed',				array($helper, 'get_topic_moved_id')),
				array('topic_type',				'threads.sticky',				array($helper, 'convert_topic_type')),
				array('topic_first_post_id',	'threads.firstpost',			''),
				array('topic_last_view_time',	'threads.lastpost',				''),
				array('topic_last_post_id',		0,								''),
				array('topic_last_post_time',	'threads.lastpost',				''),
				array('poll_title',				'polls.question',				array($helper, 'htmlspecialchars')),
				array('poll_start',				'polls.dateline',				'null_to_zero'),
				array('poll_length',			'polls.timeout',				array($helper, 'get_poll_length')),
				array('poll_max_options',		1,								''),
				array('poll_vote_change',		0,								''),
				array('',						'polls.votes',					''),
				array('',						'polls.options',				array($helper, 'insert_poll_options')),
				array('topic_visibility',		'threads.visible',				array($helper, 'get_visibility')),

				'left_join'		=>	'threads LEFT JOIN polls ON (threads.tid = polls.tid)',

				'where'			=> 'threads.visible <> -2',
			),

			array(
				'target'		=> $helper->get_table('forums_track'),
				'primary'		=> 'forumsread.fid',
				'query_first'	=> $helper->get_truncate_statement('forums_track'),

				array('user_id',				'forumsread.uid',					array($helper, 'get_user_id')),
				array('forum_id',				'forumsread.fid',					''),
				array('mark_time',				'forumsread.dateline',				''),
			),

			array(
				'target'		=> $helper->get_table('topics_track'),
				'primary'		=> 'threadsread.tid',
				'query_first'	=> $helper->get_truncate_statement('topics_track'),

				array('user_id',				'threadsread.uid',					array($helper, 'get_user_id')),
				array('topic_id',				'threadsread.tid',					''),
				array('forum_id',				'threads.fid',						''),
				array('mark_time',				'threadsread.dateline',				''),

				'where'		=> 'threadsread.tid = threads.tid',
			),

			array(
				'target'		=> $helper->get_table('forums_watch'),
				'primary'		=> 'forumsubscriptions.fsid',
				'query_first'	=> $helper->get_truncate_statement('forums_watch'),

				array('forum_id',				'forumsubscriptions.fid',			''),
				array('user_id',				'forumsubscriptions.uid',			array($helper, 'get_user_id')),
				array('notify_status',			NOTIFY_YES,							''),
			),

			array(
				'target'		=> $helper->get_table('topics_watch'),
				'primary'		=> 'threadsubscriptions.tid',
				'query_first'	=> $helper->get_truncate_statement('topics_watch'),

				array('topic_id',				'threadsubscriptions.tid',			''),
				array('user_id',				'threadsubscriptions.uid',			array($helper, 'get_user_id')),
				array('notify_status',			NOTIFY_YES,							''),

				'where'			=> 'notification = 1',
			),

			// We'll convert subscriptions that do not send out emails to bookmarks.
			array(
				'target'		=> $helper->get_table('bookmarks'),
				'primary'		=> 'threadsubscriptions.tid',
				'query_first'	=> $helper->get_truncate_statement('bookmarks'),

				array('topic_id',				'threadsubscriptions.tid',			''),
				array('user_id',				'threadsubscriptions.uid',			array($helper, 'get_user_id')),

				'where'		=> 'notification = 0',
			),

			array(
				'target'		=> $helper->get_table('smilies'),
				'query_first'	=> $helper->get_truncate_statement('smilies'),
				'autoincrement'	=> 'smiley_id',

				array('smiley_id',				'smilies.sid',						''),
				array('code',					'smilies.find',						array($helper, 'htmlspecialchars')),
				array('emotion',				'smilies.name',						array($helper, 'htmlspecialchars')),
				array('smiley_url',				'smilies.image',					array($helper, 'import_smiley')),
				array('smiley_width',			'smilies.image',					'get_smiley_width'),
				array('smiley_height',			'smilies.image',					'get_smiley_height'),
				array('smiley_order',			'smilies.disporder',				''),
				array('display_on_posting',		'smilies.showclickable',			''),
			),

			array(
				'target'		=> $helper->get_table('poll_votes'),
				'primary'		=> 'pollvotes.vid',
				'query_first'	=> $helper->get_truncate_statement('poll_votes'),

				array('poll_option_id',			'pollvotes.voteoption',				''),
				array('topic_id',				'polls.tid',						''),
				array('vote_user_id',			'pollvotes.uid',					array($helper, 'get_user_id')),
				array('vote_user_ip',			'',									''),

				'where'			=> 'pollvotes.pid = polls.pid',
			),

			array(
				'target'		=> $helper->get_table('words'),
				'primary'		=> 'badwords.bid',
				'query_first'	=> $helper->get_truncate_statement('words'),
				'autoincrement'	=> 'word_id',

				array('word_id',				'badwords.bid',						''),
				array('word',					'badwords.badword',					array($helper, 'htmlspecialchars')),
				array('replacement',			'badwords.replacement',				array($helper, 'convert_word_replacement')),
			),

			array(
				'target'		=> $helper->get_table('posts'),
				'primary'		=> 'posts.pid',
				'autoincrement'	=> 'post_id',
				'query_first'	=> $helper->get_truncate_statement('posts'),
				'execute_first'	=> '
					$config["max_post_chars"] = 0;
					$config["min_post_chars"] = 0;
					$config["max_quote_depth"] = 0;
				',

				array('post_id',				'posts.pid',						''),
				array('topic_id',				'posts.tid',						''),
				array('forum_id',				'posts.fid',						''),
				array('poster_id',				'posts.uid',						array($helper, 'get_user_id')),
				array('icon_id',				'posts.icon',						''),
				array('poster_ip',				'posts.ipaddress',					'inet_ntop'),
				array('post_time',				'posts.dateline',					''),
				array('enable_bbcode',			1,									''),
				array('enable_smilies',			'posts.smilieoff',					'is_empty'),
				array('enable_sig',				'posts.includesig',					''),
				array('enable_magic_url',		1,									''),
				array('post_username',			'',									''),
				array('post_subject',			'posts.subject',					array($helper, 'htmlspecialchars')),
				array('post_attachment',		0,									''),
				array('post_edit_time',			'posts.edittime',					''),
				array('post_edit_count',		'posts.edittime',					array($helper, 'not_empty')),
				array('post_edit_reason',		'',									''),
				array('post_edit_user',			'posts.edituid',					array($helper, 'get_user_id')),

				array('bbcode_uid',				'posts.dateline',					'make_uid'),
				array('post_text',				'posts.message',					array($helper, 'prepare_message')),
				array('bbcode_bitfield',		'',									array($helper, 'get_bbcode_bitfield')),
				array('post_checksum',			'',									''),
				array('post_visibility',		'posts.visible',					array($helper, 'get_visibility')),

				'where'		=> 'posts.visible <> -2',
			),

			array(
				'target'		=> $helper->get_table('drafts'),
				'primary'		=> 'posts.pid',
				'autoincrement'	=> 'draft_id',
				'query_first'	=> $helper->get_truncate_statement('drafts'),

				array('draft_id',				0,									''),
				array('user_id',				'posts.uid',						array($helper, 'get_user_id')),
				array('',						'threads.visible',					''),
				array('topic_id',				'posts.tid',						array($helper, 'get_draft_topic_id')),
				array('forum_id',				'posts.fid',						''),
				array('save_time',				'posts.dateline',					''),
				array('draft_subject',			'posts.subject',					array($helper, 'htmlspecialchars')),
				array('draft_message',			'posts.message',					array($helper, 'htmlspecialchars')),

				'where'		=> 'posts.visible = -2 AND posts.tid = threads.tid',
			),

			array(
				'target'		=> $helper->get_table('drafts'),
				'primary'		=> 'privatemessages.pmid',
				'autoincrement'	=> 'draft_id',

				array('draft_id',				0,									''),
				array('user_id',				'privatemessages.fromid',			array($helper, 'get_user_id')),
				array('topic_id',				0,									''),
				array('forum_id',				0,									''),
				array('save_time',				'privatemessages.dateline',			''),
				array('draft_subject',			'privatemessages.subject',			array($helper, 'htmlspecialchars')),
				array('draft_message',			'privatemessages.message',			array($helper, 'htmlspecialchars')),

				'where'		=> 'privatemessages.folder = 3',
			),

			array(
				'target'		=> $helper->get_table('reports'),
				'primary'		=> 'reportedcontent.rid',
				'autoincrement'	=> 'report_id',
				'query_first'	=> $helper->get_truncate_statement('reports'),

				array('report_id',				'reportedcontent.rid',				''),
				array('reason_id',				4,									''), // "Other"
				array('post_id',				'reportedcontent.id',				''),
				array('pm_id',					0,									''),
				array('user_id',				'reportedcontent.uid',				array($helper, 'get_user_id')),
				array('user_notify',			0,									''),
				array('report_closed',			'reportedcontent.reportstatus',		''),
				array('report_text',			'reportedcontent.reason',			array('function1' => array($helper, 'set_encoding'), 'function2' => 'trim')),
				array('reported_post_text',		'',									''),
				array('report_time',			'reportedcontent.dateline',			''),

				'where'			=> 'reportedcontent.type = "post"',
			),

			array(
				'target'		=> $helper->get_table('privmsgs_folder'),
				'primary'		=> 'users.uid',
				'query_first'	=> $helper->get_truncate_statement('privmsgs_folder'),

				array('',						'users.uid',						''),
				array('',						'users.pmfolders',					array($helper, 'insert_custom_folders')),

				'where'			=> 'users.pmfolders ' . $db->sql_like_expression($db->any_char . '**$%%$5**' . $db->any_char),
			),

			array(
				'target'		=> $helper->get_table('privmsgs'),
				'primary'		=> 'privatemessages.pmid',
				'autoincrement'	=> 'msg_id',
				'query_first'	=> array(
					$helper->get_truncate_statement('privmsgs'),
					$helper->get_truncate_statement('privmsgs_rules'),
				),

				'execute_first'	=> '
					$config["max_post_chars"] = 0;
					$config["min_post_chars"] = 0;
					$config["max_quote_depth"] = 0;
				',

				array('msg_id',					'privatemessages.pmid',				''),
				array('root_level',				0,									''),
				array('author_id',				'privatemessages.fromid AS poster_id',	array($helper, 'get_user_id')),
				array('icon_id',				'privatemessages.icon',				''),
				array('author_ip',				'',									''),
				array('message_time',			'privatemessages.dateline',			''),
				array('enable_bbcode',			1,									''),
				array('enable_smilies',			'privatemessages.smilieoff',		''),
				array('enable_magic_url',		1,									''),
				array('enable_sig',				'privatemessages.includesig',		''),
				array('message_subject',		'privatemessages.subject',			array($helper, 'htmlspecialchars')),
				array('message_attachment',		0,									''),
				array('message_edit_reason',	'',									''),
				array('message_edit_user',		0,									''),
				array('message_edit_time',		0,									''),
				array('message_edit_count',		0,									''),

				array('bbcode_uid',				'privatemessages.dateline AS post_time',	'make_uid'),
				array('message_text',			'privatemessages.message',			array($helper, 'prepare_message')),
				array('bbcode_bitfield',		'',									array($helper, 'get_bbcode_bitfield')),
				array('to_address',				'privatemessages.recipients',		array($helper, 'pm_to_recipients')),
				array('bcc_address',			'privatemessages.recipients',		array($helper, 'pm_bcc_recipients')),

				// Do not include drafts or those in the trash
				'where'			=>	'privatemessages.folder <> 3
										AND privatemessages.folder <> 4
										AND privatemessages.deletetime = 0',
			),

			// Inbox
			array(
				'target'		=> $helper->get_table('privmsgs_to'),
				'primary'		=> 'privatemessages.pmid',
				'query_first'	=> $helper->get_truncate_statement('privmsgs_to'),

				array('msg_id',					'privatemessages.pmid',				''),
				array('user_id',				'privatemessages.uid',				array($helper, 'get_user_id')),
				array('author_id',				'privatemessages.fromid',			array($helper, 'get_user_id')),
				array('pm_deleted',				0,									''),
				array('pm_new',					'privatemessages.readtime',			'is_empty'),
				array('pm_unread',				'privatemessages.readtime',			'is_empty'),
				array('pm_replied',				0,									''),
				array('pm_marked',				0,									''),
				array('pm_forwarded',			0,									''),
				array('folder_id',				PRIVMSGS_INBOX,						''),

				'where'			=>	'privatemessages.folder = 1
										AND privatemessages.deletetime = 0',
			),

			// Sentbox
			array(
				'target'		=> $helper->get_table('privmsgs_to'),
				'primary'		=> 'privatemessages.pmid',

				array('msg_id',					'privatemessages.pmid',				''),
				array('user_id',				'privatemessages.uid',				array($helper, 'get_user_id')),
				array('author_id',				'privatemessages.fromid',			array($helper, 'get_user_id')),
				array('pm_deleted',				0,									''),
				array('pm_new',					0,									''),
				array('pm_unread',				0,									''),
				array('pm_replied',				0,									''),
				array('pm_marked',				0,									''),
				array('pm_forwarded',			0,									''),
				array('folder_id',				PRIVMSGS_SENTBOX,					''),

				'where'			=>	'privatemessages.folder = 2
										AND privatemessages.deletetime = 0',
			),

			// Custom folders
			array(
				'target'		=> $helper->get_table('privmsgs_to'),
				'primary'		=> 'privatemessages.pmid',

				array('msg_id',					'privatemessages.pmid',				''),
				array('user_id',				'privatemessages.uid',				array($helper, 'get_user_id')),
				array('author_id',				'privatemessages.fromid',			array($helper, 'get_user_id')),
				array('pm_deleted',				0,									''),
				array('pm_new',					'privatemessages.readtime',			'is_empty'),
				array('pm_unread',				'privatemessages.readtime',			'is_empty'),
				array('pm_replied',				0,									''),
				array('pm_marked',				0,									''),
				array('pm_forwarded',			0,									''),
				array('folder_id',				'privatemessages.folder',			array($helper, 'get_folder_id')),

				'where'			=>	'privatemessages.folder > 4
										AND privatemessages.deletetime = 0',
			),

			array(
				'target'		=> $helper->get_table('groups'),
				'autoincrement'	=> 'group_id',
				'query_first'	=> $helper->get_truncate_statement('groups'),

				array('group_id',				'usergroups.gid',					''),
				array('group_type',				'usergroups.type',					array($helper, 'convert_group_type')),
				array('group_display',			0,									''),
				array('group_legend',			0,									''),
				array('group_name',				'usergroups.title',					array($helper, 'convert_group_name')),
				array('group_desc',				'usergroups.description',			array($helper, 'htmlspecialchars')),
			),

			array(
				'target'		=> $helper->get_table('user_group'),
				'primary'		=> 'users.uid',
				'query_first'	=> $helper->get_truncate_statement('user_group'),
				'execute_first'	=> '
					add_default_groups();
				',

				array('group_id',				'users.usergroup',					''),
				array('user_id',				'users.uid',						array($helper, 'get_user_id')),
				array('',						'users.additionalgroups',			array($helper, 'insert_additional_groups')),
				array('group_leader',			'groupleaders.lid',					array($helper, 'not_empty')),
				array('user_pending',			0,									''),

				'left_join'		=> 'users LEFT JOIN groupleaders ON (users.uid = groupleaders.uid AND users.usergroup = groupleaders.gid)',
			),

			array(
				'target'		=> $helper->get_table('users'),
				'primary'		=> 'users.uid',
				'autoincrement'	=> 'user_id',
				'query_first'	=> array(
					array('target', 'DELETE FROM ' . USERS_TABLE . ' WHERE user_id <> ' . ANONYMOUS),
					$helper->get_truncate_statement('bots'),
				),

				'execute_last'	=> '
					remove_invalid_users();
				',

				array('user_id',				'users.uid',						array($helper, 'get_user_id')),
				array('',						'users.uid AS poster_id',			array($helper, 'get_user_id')),
				array('user_type',				USER_NORMAL,						''),
				array('group_id',				get_group_id('registered'),			''),
				array('user_regdate',			'users.regdate',					''),
				array('username',				'users.username',					array($helper, 'htmlspecialchars')),
				array('username_clean',			'users.username',					array($helper, 'clean_string')),
				array('user_password',			'users.password',					array($helper, 'prefix_hash')),
				array('user_passwd_salt',		'users.salt',						''),
				array('user_posts',				'users.postnum',					'intval'),
				array('user_email',				'users.email',						'strtolower'),
				array('user_email_hash',		'users.email',						'gen_email_hash'),
				array('user_birthday',			'users.birthday',					''),
				array('user_lastvisit',			'users.lastvisit',					'intval'),
				array('user_lastmark',			'users.regdate',					'intval'),
				array('user_lang',				$config['default_lang'],			''),
				array('user_timezone',			'users.timezone',					array($helper, 'get_timezone')),
				array('',						'users.dst',						''),
				array('user_dateformat',		$config['default_dateformat'],		''),
				array('user_inactive_reason',	0,									''),
				array('user_inactive_time',		0,									''),

				array('user_jabber',			'',									''),
				array('user_rank',				0,									''),
				array('user_permissions',		'',									''),

				array('user_avatar',			'users.avatar',						array($helper, 'import_avatar')),
				array('user_avatar_type',		'users.avatartype',					array($helper, 'get_avatar_type')),
				array('user_avatar_width',		'users.avatardimensions',			array($helper, 'get_avatar_width')),
				array('user_avatar_height',		'users.avatardimensions',			array($helper, 'get_avatar_height')),

				array('user_new_privmsg',		'users.unreadpms',					''),
				array('user_unread_privmsg',	'users.unreadpms',					''),
				array('user_last_privmsg',		0,									'intval'),
				array('user_emailtime',			'users.regdate',					'null_to_zero'),
				array('user_notify',			'users.subscriptionmethod',			array($helper, 'user_notify')),
				array('user_notify_pm',			'users.pmnotify',					'intval'),
				array('user_notify_type',		NOTIFY_EMAIL,						''),
				array('user_allow_pm',			'users.receivepms',					'intval'),
				array('user_allow_viewonline',	'users.invisible',					'is_empty'),
				array('user_allow_viewemail',	'users.hideemail',					'is_empty'),
				array('user_actkey',			'',									''),
				array('user_newpasswd',			'',									''), // Users need to re-request their password...
				array('user_style',				$config['default_style'],			''),

				array('user_options',			'',									'set_user_options'),
				array('',						'users.pmnotice AS popuppm',		''),

				array('user_sig_bbcode_uid',		'users.regdate',				'make_uid'),
				array('user_sig',					'users.signature',				array($helper, 'prepare_message')),
				array('user_sig_bbcode_bitfield',	'',								array($helper, 'get_bbcode_bitfield')),
				array('',							'users.regdate AS dateline',	''),
			),

			array(
				'target'		=> $helper->get_table('profile_fields_data'),
				'primary'		=> 'users.uid',
				'query_first'	=> $helper->get_truncate_statement('profile_fields_data'),

				array('user_id',				'users.uid',						array($helper, 'get_user_id')),
				array('pf_phpbb_website',		'users.website',					'validate_website'),
				array('pf_phpbb_yahoo',			'users.yahoo',						array($helper, 'set_encoding')),
				array('pf_phpbb_aol',			'users.aim',						array($helper, 'set_encoding')),
				array('pf_phpbb_icq',			'users.icq',						array($helper, 'set_encoding')),
				array('pf_phpbb_skype',			'users.skype',						array($helper, 'set_encoding')),
				array('pf_phpbb_interests',		'',									''),
				array('pf_phpbb_occupation',	'',									''),
				array('pf_phpbb_location',		(USER_FROM_EXISTS) ? 'userfields.fid1' : '', array($helper, 'htmlspecialchars')),

				'left_join'		=> 'users LEFT JOIN userfields ON (users.uid = userfields.ufid)',
			),

			array(
				'target'		=> $helper->get_table('zebra'),
				'primary'		=> 'users.uid',
				'query_first'	=> $helper->get_truncate_statement('zebra'),

				array('',						'users.uid',						''),
				array('',						'users.buddylist',					''),
				array('',						'users.ignorelist',					array($helper, 'insert_zebra')),

				'where'		=> 'users.buddylist <> "" OR users.ignorelist <> ""',
			),
		),
	);
}
