<?php
/**
 * MyReactions 0.0.4

 * Copyright 2017 Matthew Rogowski

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at

 ** http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.

 * Idea inspired by https://facepunch.com/ and more recently Slack

 * Twitter Emoji licenced under CC-BY 4.0
 * http://twitter.github.io/twemoji/
 * https://github.com/twitter/twemoji
**/

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function myreactions_do_info()
{
	return array(
		"name" => "MyReactions",
		"description" => "Add emoji reactions to posts",
		"website" => "https://github.com/MattRogowski/MyReactions",
		"author" => "Matt Rogowski",
		"authorsite" => "https://matt.rogow.ski",
		"version" => MYREACTIONS_VERSION,
		"compatibility" => "18*",
		"codename" => "myreactions"
	);
}

function myreactions_do_install()
{
	global $db;

	myreactions_do_uninstall();

	myreactions_do_db_changes();

	change_admin_permission("forum", "myreactions", 1);
}

function myreactions_do_is_installed()
{
	global $db;

	return $db->table_exists('myreactions') && $db->table_exists('post_reactions');
}

function myreactions_do_uninstall()
{
	global $db;

	if($db->table_exists('myreactions'))
	{
		$db->drop_table('myreactions');
	}
	if($db->table_exists('post_reactions'))
	{
		$db->drop_table('post_reactions');
	}
	if($db->field_exists("reactions_received", "users"))
	{
		$db->drop_column("users", "reactions_received");
	}
	if($db->field_exists("reactions_given", "users"))
	{
		$db->drop_column("users", "reactions_given");
	}

	$db->delete_query('datacache', 'title = \'myreactions\'');

	if($db->table_exists('alerts') && $db->table_exists('alert_types'))
	{
		$myalerts_type_id = myreactions_myalerts_type_id();
		if($myalerts_type_id)
		{
			$db->delete_query('alerts', 'alert_type_id = '.$myalerts_type_id);
			$db->delete_query('alert_types', 'id = '.$myalerts_type_id);
		}
	}
}

function myreactions_do_db_changes()
{
	global $db;

	if(!$db->table_exists('myreactions'))
	{
		$db->write_query('CREATE TABLE `'.TABLE_PREFIX.'myreactions` (
		  `reaction_id` int(11) NOT NULL AUTO_INCREMENT,
		  `reaction_name` varchar(255) NOT NULL,
		  `reaction_image` varchar(255) NOT NULL,
		  PRIMARY KEY (`reaction_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;');

		$reactions = array("angry","anguished","awesome","balloon","broken_heart","clap","confounded","confused","crossed_fingers","disappointed","disappointed_relieved","disapproval","dizzy_face","expressionless","eyes","face_with_rolling_eyes","facepalm","fearful","fire","flushed","grimacing","grin","grinning","hear_no_evil","heart","heart_eyes","ill","information_desk_person","innocent","joy","laughing","mask","nerd_face","neutral_face","ok_hand","open_mouth","pensive","persevere","poop","pray","rage","raised_hands","rofl","scream","see_no_evil","shrug","sleeping","slightly_frowning_face","slightly_smiling_face","smile","smiling_imp","smirk","sob","speak_no_evil","star","stuck_out_tongue","stuck_out_tongue_closed_eyes","stuck_out_tongue_winking_eye","sunglasses","suspicious","sweat","sweat_smile","tada","thinking_face","thumbsdown","thumbsup","tired_face","triumph","unamused","upside_down_face","v","whatever","white_frowning_face","wink","worried","zipper_mouth_face");

		foreach($reactions as $reaction)
		{
			$insert = array(
				'reaction_name' => ucwords(str_replace('_', ' ', $reaction)),
				'reaction_image' => 'images/reactions/'.$reaction.'.png'
			);
			$db->insert_query('myreactions', $insert);
		}
	}
	if(!$db->table_exists('post_reactions'))
	{
		$db->write_query('CREATE TABLE `'.TABLE_PREFIX.'post_reactions` (
		  `post_reaction_id` int(11) NOT NULL AUTO_INCREMENT,
		  `post_reaction_pid` int(11) NOT NULL,
		  `post_reaction_rid` int(11) NOT NULL,
		  `post_reaction_uid` int(11) NOT NULL,
		  `post_reaction_date` int(11) NOT NULL,
		  PRIMARY KEY (`post_reaction_id`),
		  KEY `post_reaction_pid` (`post_reaction_pid`),
		  KEY `post_reaction_rid` (`post_reaction_rid`),
		  KEY `post_reaction_uid` (`post_reaction_uid`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;');
	}
	if(!$db->field_exists("reactions_received", "users"))
	{
		$db->add_column("users", "reactions_received", "int(11) NOT NULL DEFAULT 0");
	}
	if(!$db->field_exists("reactions_given", "users"))
	{
		$db->add_column("users", "reactions_given", "int(11) NOT NULL DEFAULT 0");
	}
}

function myreactions_do_activate()
{
	global $mybb, $db;

	myreactions_do_deactivate();

	myreactions_do_db_changes();

	$settings_group = array(
		"name" => "myreactions",
		"title" => "MyReactions Settings",
		"description" => "Settings for the MyReactions plugin.",
		"disporder" => "28",
		"isdefault" => 0
	);
	$db->insert_query("settinggroups", $settings_group);
	$gid = $db->insert_id();

	$settings = array();
	$settings[] = array(
		"name" => "myreactions_type",
		"title" => "Display Type",
		"description" => "<strong>Grouped</strong> - each reaction is only displayed once per post, in its own button with a count of the number of times it has been given to that post, ordered by number of times given<br /><strong>Linear</strong> - lists each individual reaction given in the order it was given",
		"optionscode" => "radio
grouped=Grouped
linear=Linear",
		"value" => "grouped"
	);
	$settings[] = array(
		"name" => "myreactions_size",
		"title" => "Display Size",
		"description" => "The size of the reaction emojis",
		"optionscode" => "radio
16=16px x 16px
20=20px x 20px
24=24px x 24px
28=28px x 28px
32=32px x 32px",
		"value" => "16"
	);
	$settings[] = array(
		"name" => "myreactions_multiple",
		"title" => "Allow multiple reactions",
		"description" => "Whether users can add more than one reaction to a post (regardless of setting the same reaction can never be given by the same user on the same post)",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "myreactions_forumdisplay",
		"title" => "Display on thread list",
		"description" => "Display the top reactions from a thread on the forum display thread list",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "myreactions_forumdisplay_type",
		"title" => "Thread list reaction source",
		"description" => "Which reactions should be shown?",
		"optionscode" => "radio
post=Reactions given to first post
thread=Reactions given in whole thread",
		"value" => "post"
	);
	$settings[] = array(
		"name" => "myreactions_forumdisplay_count",
		"title" => "Thread list reaction count",
		"description" => "How many top reactions should be shown?",
		"optionscode" => "text",
		"value" => "10"
	);
	$settings[] = array(
		"name" => "myreactions_profile",
		"title" => "Display on profiles",
		"description" => "Display the most given and most received reactions on user profiles",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "myreactions_enable_myalerts",
		"title" => "Enable MyAlerts integration",
		"description" => "If installed, you can enable sending alerts when users receive a reaction. If you enable this setting and MyAlerts is not running, the setting will automatically be changed back to 'No'",
		"optionscode" => "yesno",
		"value" => "0"
	);
	$i = 1;
	foreach($settings as $setting)
	{
		$insert = array(
			"name" => $db->escape_string($setting['name']),
			"title" => $db->escape_string($setting['title']),
			"description" => $db->escape_string($setting['description']),
			"optionscode" => $db->escape_string($setting['optionscode']),
			"value" => $db->escape_string($setting['value']),
			"disporder" => intval($i),
			"gid" => intval($gid),
		);
		$db->insert_query("settings", $insert);
		$i++;
	}

	rebuild_settings();

	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

	find_replace_templatesets("showthread", "#".preg_quote('</head>')."#i", '<script type="text/javascript" src="{$mybb->asset_url}/jscripts/myreactions.js?ver='.preg_replace('/[^0-9]/', '', MYREACTIONS_VERSION).'"></script>'."\n".'</head>');
	find_replace_templatesets("member_profile", "#".preg_quote('</head>')."#i", '<script type="text/javascript" src="{$mybb->asset_url}/jscripts/myreactions.js?ver='.preg_replace('/[^0-9]/', '', MYREACTIONS_VERSION).'"></script>'."\n".'</head>');
	find_replace_templatesets("postbit", "#".preg_quote('<div class="post_controls">')."#i", '{$post[\'myreactions\']}<div class="post_controls">');
	find_replace_templatesets("postbit_classic", "#".preg_quote('<div class="post_controls">')."#i", '{$post[\'myreactions\']}<div class="post_controls">');
	find_replace_templatesets("postbit_author_user", "#".preg_quote('{$post[\'replink\']}')."#i", '{$post[\'replink\']}{myreactions}');
	find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$thread[\'profilelink\']}</div>')."#i", '{$thread[\'profilelink\']}</div><div>{$thread[\'reactions\']}</div>');
	find_replace_templatesets("member_profile", "#".preg_quote('{$profilefields}')."#i", '{$profilefields}{$myreactions}');

	$templates = array();
	$templates[] = array(
		"title" => "myreactions_container",
		"template" => "<div style=\"clear:both\"></div>
<div class=\"myreactions-container reactions-{\$size} myreactions-post\">
  {\$post_reactions}
  <div style=\"clear:both\"></div>
</div>"
	);
	$templates[] = array(
		"title" => "myreactions_reactions",
		"template" => "<div class=\"myreactions-reactions\">
  {\$reactions}
  <div style=\"clear:both\"></div>
</div>"
	);
	$templates[] = array(
		"title" => "myreactions_reaction",
		"template" => "<div class=\"myreactions-reaction{\$class}\"{\$onclick}{\$title}>
  {\$reaction_image} <span>{\$count}</span>
</div>"
	);
	$templates[] = array(
		"title" => "myreactions_reaction_image",
		"template" => "<img src=\"{\$mybb->settings['bburl']}/{\$reaction['reaction_image']}\"{\$class}{\$onclick}{\$title} />{\$remove}"
	);
	$templates[] = array(
		"title" => "myreactions_add",
		"template" => "<div class=\"myreactions-reaction reaction-add{\$force} reaction-hover-show\" onclick=\"MyReactions.reactions('{\$post['pid']}');\">
  <img src=\"{\$mybb->settings['bburl']}/images/reactions/plus.png\" /> <span>{\$lang->myreactions_add}</span>
</div>"
	);
	$templates[] = array(
		"title" => "myreactions_react",
		"template" => "<div class=\"modal\">
	<div class=\"myreactions-react\">
		<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
			<tr>
				<td class=\"thead\"><strong>{\$lang->myreactions_add}</strong></td>
			</tr>
			<tr>
				<td class=\"trow1\">{\$post_preview}</td>
			</tr>
			{\$favourites}
			{\$given_to_post}
			<tr>
				<td class=\"tcat\">{\$lang->myreactions_all}</td>
			</tr>
			<tr>
				<td class=\"trow1\" align=\"left\">
					{\$reactions}
				</td>
			</tr>
		</table>
	</div>
</div>"
	);
	$templates[] = array(
		"title" => "myreactions_react_filtered",
		"template" => "<tr>
	<td class=\"tcat\">{\$title}</td>
</tr>
<tr>
	<td class=\"trow1\" align=\"left\">
		{\$filtered_reactions}
	</td>
</tr>"
	);
	$templates[] = array(
		"title" => "myreactions_forumdisplay_thread",
		"template" => "<div class=\"myreactions-forumdisplay-thread\">
	{\$thread_reaction_images}
</div>"
	);
	$templates[] = array(
		"title" => "myreactions_profile",
		"template" => "<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\"><strong>{\$lang->myreactions_profile_header}</strong></td>
	</tr>
	<tr>
		<td class=\"tcat\">{\$lang->myreactions_profile_top_received}<span class=\"float_right\">{\$lang->myreactions_received} <a href=\"javascript:void(0)\" onclick=\"MyReactions.reactedUser({\$memprofile['uid']}, 'received');\">{\$lang->myreactions_view_all}</a></span></td>
	</tr>
	<tr>
		<td class=\"trow1\" align=\"left\">
			<div class=\"myreactions-container myreactions-profile-container reactions-{\$size} myreactions-profile\">
				{\$reactions_received}
			</div>
		</td>
	</tr>
	<tr>
		<td class=\"tcat\">{\$lang->myreactions_profile_top_given}<span class=\"float_right\">{\$lang->myreactions_given} <a href=\"javascript:void(0)\" onclick=\"MyReactions.reactedUser({\$memprofile['uid']}, 'given');\">{\$lang->myreactions_view_all}</a></span></td>
	</tr>
	<tr>
		<td class=\"trow1\" align=\"left\">
			<div class=\"myreactions-container myreactions-profile-container reactions-{\$size} myreactions-profile\">
				{\$reactions_given}
			</div>
		</td>
	</tr>
	{\$top_reacted_post}
</table>
<br />"
	);
	$templates[] = array(
		"title" => "myreactions_profile_post",
		"template" => "<tr>
	<td class=\"tcat\">{\$lang->myreactions_profile_top_post}<span class=\"float_right\"><a href=\"{\$top_post_link}\">{\$lang->myreactions_profile_top_post_link} &raquo;</a></span></td>
</tr>
<tr>
	<td class=\"trow1\" align=\"left\">
		{\$top_post}
	</td>
</tr>
<tr>
	<td class=\"trow2\" align=\"left\">
		<div class=\"myreactions-container myreactions-profile-container reactions-{\$size} myreactions-profile\">
			{\$top_post_reactions}
		</div>
	</td>
</tr>"
	);
	$templates[] = array(
		"title" => "myreactions_reacted_button",
		"template" => "<div class=\"myreactions-reaction reaction-reacted reaction-hover-show\" onclick=\"MyReactions.reactedPost('{\$post['pid']}');\">
  <img src=\"{\$mybb->settings['bburl']}/images/reactions/thumbsup.png\" /> <span>{\$lang->myreactions_who_reacted_button} ({\$reacted_count})</span>
</div>"
	);
	$templates[] = array(
		"title" => "myreactions_reacted",
		"template" => "<div class=\"modal\">
	<div class=\"myreactions-post-reacted\">
		<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
			<tr>
				<td class=\"thead\" colspan=\"3\"><strong>{\$reacted_heading}</strong></td>
			</tr>
			<tr>
				<td class=\"trow1\" colspan=\"3\" align=\"center\">
					<input type=\"radio\" name=\"myreactions_reacted_display\" id=\"myreactions_reacted_display_grouped\" value=\"grouped\" onchange=\"MyReactions.reactedView()\" checked=\"checked\" /> <label for=\"myreactions_reacted_display_grouped\">{\$lang->myreactions_reacted_display_grouped}</label>
					<input type=\"radio\" name=\"myreactions_reacted_display\" id=\"myreactions_reacted_display_linear\" value=\"linear\" onchange=\"MyReactions.reactedView()\" /> <label for=\"myreactions_reacted_display_linear\">{\$lang->myreactions_reacted_display_linear}</label>
					<input type=\"radio\" name=\"myreactions_reacted_display\" id=\"myreactions_reacted_display_user\" value=\"user\" onchange=\"MyReactions.reactedView()\" /> <label for=\"myreactions_reacted_display_user\">{\$lang->myreactions_reacted_display_user}</label>
					<input type=\"radio\" name=\"myreactions_reacted_display\" id=\"myreactions_reacted_display_all\" value=\"all\" onchange=\"MyReactions.reactedView()\" /> <label for=\"myreactions_reacted_display_all\">{\$lang->myreactions_reacted_display_all}</label>
				</td>
			</tr>
			<tr class=\"myreactions_reacted_row myreactions_reacted_row_grouped\">
				<td class=\"tcat\" align=\"center\">{\$lang->myreactions_reacted_reaction}</td>
				<td class=\"tcat\" align=\"center\">{\$lang->myreactions_reacted_count}</td>
				<td class=\"tcat\">{\$lang->myreactions_reacted_users}</td>
			</tr>
			{\$reacted_grouped}
			<tr class=\"myreactions_reacted_row myreactions_reacted_row_linear myreactions_reacted_row_hidden\">
				<td class=\"tcat\" align=\"center\">{\$lang->myreactions_reacted_reaction}</td>
				<td class=\"tcat\" align=\"center\">{\$lang->myreactions_reacted_user}</td>
				<td class=\"tcat\">{\$lang->myreactions_reacted_date}</td>
			</tr>
			{\$reacted_linear}
			<tr class=\"myreactions_reacted_row myreactions_reacted_row_user myreactions_reacted_row_hidden\">
				<td class=\"tcat\" align=\"center\">{\$lang->myreactions_reacted_user}</td>
				<td class=\"tcat\">{\$lang->myreactions_reacted_reactions}</td>
			</tr>
			{\$reacted_user}
			<tr class=\"myreactions_reacted_row myreactions_reacted_row_all myreactions_reacted_row_hidden\">
				<td>{\$reacted_all}</td>
			</tr>
		</table>
	</div>
</div>"
	);
	$templates[] = array(
		"title" => "myreactions_reacted_row_grouped",
		"template" => "<tr class=\"myreactions_reacted_row myreactions_reacted_row_grouped\">
	<td class=\"{\$trow}\" width=\"10%\" align=\"center\">{\$image}</td>
	<td class=\"{\$trow}\">{\$count}</td>
	<td class=\"{\$trow}\">{\$users}</td>
</tr>"
	);
	$templates[] = array(
		"title" => "myreactions_reacted_row_linear",
		"template" => "<tr class=\"myreactions_reacted_row myreactions_reacted_row_linear myreactions_reacted_row_hidden\">
	<td class=\"{\$trow}\" width=\"10%\" align=\"center\">{\$image}</td>
	<td class=\"{\$trow}\" width=\"20%\" align=\"center\">{\$user}</td>
	<td class=\"{\$trow}\">{\$date}</td>
</tr>"
	);
	$templates[] = array(
		"title" => "myreactions_reacted_row_user",
		"template" => "<tr class=\"myreactions_reacted_row myreactions_reacted_row_user myreactions_reacted_row_hidden\">
	<td class=\"{\$trow}\" width=\"25%\" align=\"center\">{\$user}</td>
	<td class=\"{\$trow}\">{\$images}</td>
</tr>"
	);

	foreach($templates as $template)
	{
		$insert = array(
			"title" => $db->escape_string($template['title']),
			"template" => $db->escape_string($template['template']),
			"sid" => "-1",
			"version" => "1800",
			"status" => "",
			"dateline" => TIME_NOW
		);

		$db->insert_query("templates", $insert);
	}

	myreactions_cache();
}

function myreactions_do_deactivate()
{
	global $mybb, $db;

	$db->delete_query("settinggroups", "name = 'myreactions'");

	$db->delete_query("settings", "name LIKE 'myreactions_%'");

	rebuild_settings();

	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
	find_replace_templatesets("showthread", "#".preg_quote('<script type="text/javascript" src="{$mybb->asset_url}/jscripts/myreactions.js?ver=').'(\d+)'.preg_quote('"></script>'."\n".'</head>')."#i", '</head>', 0);
	find_replace_templatesets("showthread", "#".preg_quote('<script type="text/javascript" src="{$mybb->asset_url}/jscripts/myreactions.js?ver=').'(\d+)'.preg_quote('"></script>'."\r\n".'</head>')."#i", '</head>', 0);
	find_replace_templatesets("member_profile", "#".preg_quote('<script type="text/javascript" src="{$mybb->asset_url}/jscripts/myreactions.js?ver=').'(\d+)'.preg_quote('"></script>'."\n".'</head>')."#i", '</head>', 0);
	find_replace_templatesets("member_profile", "#".preg_quote('<script type="text/javascript" src="{$mybb->asset_url}/jscripts/myreactions.js?ver=').'(\d+)'.preg_quote('"></script>'."\r\n".'</head>')."#i", '</head>', 0);
	find_replace_templatesets("postbit", "#".preg_quote('{$post[\'myreactions\']}')."#i", '', 0);
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'myreactions\']}')."#i", '', 0);
	find_replace_templatesets("postbit_author_user", "#".preg_quote('{myreactions}')."#i", '', 0);
	find_replace_templatesets("forumdisplay_thread", "#".preg_quote('<div>{$thread[\'reactions\']}</div>')."#i", '', 0);
	find_replace_templatesets("member_profile", "#".preg_quote('{$myreactions}')."#i", '', 0);

	$db->delete_query("templates", "title LIKE 'myreactions_%'");
}

function myreactions_do_settings()
{
	global $mybb, $db, $cache, $lang;

	if($mybb->request_method != 'post')
	{
		return;
	}

	if(array_key_exists('myreactions_enable_myalerts', $mybb->input['upsetting']) && $mybb->input['upsetting']['myreactions_enable_myalerts'] == 1)
	{
		$plugins = $cache->read('plugins');
		if(!isset($plugins['active']['myalerts']))
		{
			$mybb->input['upsetting']['myreactions_enable_myalerts'] = 0;
		}
		else
		{
			if(!myreactions_myalerts_type_id())
			{
				$db->insert_query('alert_types', array('code' => 'myreactions_received_reaction', 'enabled' => 1, 'can_be_user_disabled' => 1));
			}
		}
	}
}
