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

$plugins->add_hook('showthread_start', 'myreactions_showthread');
$plugins->add_hook('postbit', 'myreactions_postbit');
$plugins->add_hook('misc_start', 'myreactions_react');
$plugins->add_hook('member_profile_end', 'myreactions_profile');
$plugins->add_hook("admin_forum_menu", "myreactions_admin_forum_menu");
$plugins->add_hook("admin_forum_action_handler", "myreactions_admin_forum_action_handler");
$plugins->add_hook("admin_forum_permissions", "myreactions_admin_forum_permissions");

function myreactions_info()
{
	return array(
		"name" => "MyReactions",
		"description" => "Add emoji reactions to posts",
		"website" => "https://github.com/MattRogowski/MyReactions",
		"author" => "Matt Rogowski",
		"authorsite" => "https://matt.rogow.ski",
		"version" => "0.0.4",
		"compatibility" => "18*",
		"codename" => "myreactions"
	);
}

function myreactions_install()
{
	global $db;

	myreactions_uninstall();

	if(!$db->table_exists('myreactions'))
	{
		$db->write_query('CREATE TABLE `'.TABLE_PREFIX.'myreactions` (
		  `reaction_id` int(11) NOT NULL AUTO_INCREMENT,
		  `reaction_name` varchar(255) NOT NULL,
		  `reaction_image` varchar(255) NOT NULL,
		  PRIMARY KEY (`reaction_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;');

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
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;');
	}

	change_admin_permission("forum", "myreactions", 1);
}

function myreactions_is_installed()
{
	global $db;

	return $db->table_exists('myreactions') && $db->table_exists('post_reactions');
}

function myreactions_uninstall()
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

	$db->delete_query('datacache', 'title = \'myreactions\'');
}

function myreactions_activate()
{
	global $mybb, $db;

	myreactions_deactivate();

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
		"name" => "myreactions_profile",
		"title" => "Display on profiles",
		"description" => "Display the most given and most received reactions on user profiles",
		"optionscode" => "yesno",
		"value" => "1"
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

	$myreactions_info = myreactions_info();

	find_replace_templatesets("showthread", "#".preg_quote('</head>')."#i", '<script type="text/javascript" src="{$mybb->asset_url}/jscripts/myreactions.js?ver='.preg_replace('/[^0-9]/', '', $myreactions_info['version']).'"></script>'."\n".'</head>');
	find_replace_templatesets("postbit", "#".preg_quote('<div class="post_controls">')."#i", '{$post[\'myreactions\']}<div class="post_controls">');
	find_replace_templatesets("postbit_classic", "#".preg_quote('<div class="post_controls">')."#i", '{$post[\'myreactions\']}<div class="post_controls">');
	find_replace_templatesets("member_profile", "#".preg_quote('{$profilefields}')."#i", '{$profilefields}{$myreactions}');

	$templates = array();
	$templates[] = array(
		"title" => "myreactions_container",
		"template" => "<div style=\"clear:both\"></div>
<div class=\"myreactions-container reactions-{\$size}\">
  {\$post_reactions}
  <div style=\"clear:both\"></div>
  <div class=\"myreactions-reacted\">{\$reacted_with}</div>
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
		"template" => "<div class=\"myreactions-reaction reaction-add{\$force}\" onclick=\"MyReactions.reactions('{\$post['pid']}');\">
  <img src=\"{\$mybb->settings['bburl']}/images/reactions/plus.png\" /> <span>{\$lang->myreactions_add}</span>
</div>
<div style=\"clear:both\"></div>"
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
		"title" => "myreactions_react_favourites",
		"template" => "<tr>
	<td class=\"tcat\">{\$lang->myreactions_favourites}</td>
</tr>
<tr>
	<td class=\"trow1\" align=\"left\">
		{\$favourite_reactions}
	</td>
</tr>"
	);
	$templates[] = array(
		"title" => "myreactions_profile",
		"template" => "<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\"><strong>{\$lang->myreactions_profile_header}</strong></td>
	</tr>
	<tr>
		<td class=\"tcat\">{\$lang->myreactions_profile_received}</td>
	</tr>
	<tr>
		<td class=\"trow1\" align=\"left\">
			<div class=\"myreactions-container myreactions-profile-container reactions-{\$size}\">
				{\$reactions_received}
			</div>
		</td>
	</tr>
	<tr>
		<td class=\"tcat\">{\$lang->myreactions_profile_given}</td>
	</tr>
	<tr>
		<td class=\"trow1\" align=\"left\">
			<div class=\"myreactions-container myreactions-profile-container reactions-{\$size}\">
				{\$reactions_given}
			</div>
		</td>
	</tr>
</table>
<br />"
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

function myreactions_deactivate()
{
	global $mybb, $db;

	$db->delete_query("settinggroups", "name = 'myreactions'");

	$settings = array(
		"myreactions_type",
		"myreactions_size",
		"myreactions_multiple",
		"myreactions_profile"
	);
	$settings = "'" . implode("','", $settings) . "'";
	$db->delete_query("settings", "name IN ({$settings})");

	rebuild_settings();

	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
	find_replace_templatesets("showthread", "#".preg_quote('<script type="text/javascript" src="{$mybb->asset_url}/jscripts/myreactions.js?ver=').'(\d+)'.preg_quote('"></script>'."\n".'</head>')."#i", '</head>', 0);
	find_replace_templatesets("showthread", "#".preg_quote('<script type="text/javascript" src="{$mybb->asset_url}/jscripts/myreactions.js?ver=').'(\d+)'.preg_quote('"></script>'."\r\n".'</head>')."#i", '</head>', 0);
	find_replace_templatesets("postbit", "#".preg_quote('{$post[\'myreactions\']}')."#i", '', 0);
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'myreactions\']}')."#i", '', 0);
	find_replace_templatesets("member_profile", "#".preg_quote('{$myreactions}')."#i", '', 0);

	$db->delete_query("templates", "title IN ('myreactions_container','myreactions_reactions','myreactions_reaction','myreactions_reaction_image','myreactions_add','myreactions_react','myreactions_react_favourites','myreactions_profile')");
}

function myreactions_cache()
{
	global $db, $cache;

	$query = $db->simple_select('myreactions');
	$myreactions = array();
	while($myreaction = $db->fetch_array($query))
	{
		$myreactions[$myreaction['reaction_id']] = $myreaction;
	}
	$cache->update('myreactions', $myreactions);
}

function myreactions_showthread($post = null)
{
	global $mybb, $db, $thread_reactions;

	if($mybb->input['pid'])
	{
		$post = get_post($mybb->input['pid']);
		$tid = $post['tid'];
	}
	elseif($mybb->input['tid'])
	{
		$tid = intval($mybb->input['tid']);
	}
	elseif($post)
	{
		$tid = $post['tid'];
	}

	$reactions = $db->query('
		SELECT pr.*, mr.reaction_name, u.username
		FROM '.TABLE_PREFIX.'post_reactions pr
		JOIN '.TABLE_PREFIX.'myreactions mr ON (reaction_id = post_reaction_rid)
		JOIN '.TABLE_PREFIX.'posts ON (pid = post_reaction_pid)
		JOIN '.TABLE_PREFIX.'users u on (u.uid = post_reaction_uid)
		WHERE tid = \''.$tid.'\'
		ORDER BY post_reaction_date DESC
	');
	$thread_reactions = array();
	while($reaction = $db->fetch_array($reactions))
	{
		$thread_reactions[$reaction['post_reaction_pid']][] = $reaction;
	}
}

function myreactions_postbit(&$post)
{
	global $mybb, $lang, $cache, $templates, $thread_reactions;

	if(in_array($mybb->input['action'], array('myreactions_react','myreactions_remove')))
	{
		myreactions_showthread($post);
	}

	$all_reactions = $cache->read('myreactions');
	$lang->load('myreactions');

	$received_reactions = $reacted = array();
	if(array_key_exists($post['pid'], $thread_reactions))
	{
		$received_reactions = $thread_reactions[$post['pid']];
		foreach($received_reactions as $reaction)
		{
			if($reaction['post_reaction_uid'] == $mybb->user['uid'])
			{
				$reacted[] = $reaction;
			}
		}
	}

	$size = $mybb->settings['myreactions_size'];
	$post_reactions = '';

	switch($mybb->settings['myreactions_type'])
	{
		case 'linear':
			krsort($received_reactions);
			$reactions = '';
			foreach($received_reactions as $received_reaction)
			{
				$reaction = $all_reactions[$received_reaction['post_reaction_rid']];
				$title = ' title="'.$received_reaction['reaction_name'].' - '.$received_reaction['username'].'"';
				eval("\$reactions .= \"".$templates->get('myreactions_reaction_image')."\";");
			}
			if($reactions)
			{
				eval("\$post_reactions = \"".$templates->get('myreactions_reactions')."\";");
			}

			if($mybb->user['uid'] > 0 && $post['uid'] != $mybb->user['uid'] && !($reacted && !$mybb->settings['myreactions_multiple']))
			{
				$force = (!$reactions?' reaction-add-force':'');
				eval("\$post_reactions .= \"".$templates->get('myreactions_add')."\";");
			}
			break;
		case 'grouped':
			$grouped_reactions = array();
			foreach($received_reactions as $received_reaction)
			{
				if(!array_key_exists($received_reaction['post_reaction_rid'], $grouped_reactions))
				{
					$grouped_reactions[$received_reaction['post_reaction_rid']] = array('name' => '', 'count' => 0, 'users' => array());
				}
				$grouped_reactions[$received_reaction['post_reaction_rid']]['name'] = $received_reaction['reaction_name'];
				$grouped_reactions[$received_reaction['post_reaction_rid']]['count']++;
				$grouped_reactions[$received_reaction['post_reaction_rid']]['users'][] = $received_reaction['username'];
			}
			uasort($grouped_reactions, function($a, $b) {
				return $a['count'] < $b['count'];
			});
			foreach($grouped_reactions as $rid => $info)
			{
				$reaction = $all_reactions[$rid];
				$count = $info['count'];
				$title = ' title="'.$info['name'].' - '.implode(', ', $info['users']).'"';
				eval("\$reaction_image = \"".$templates->get('myreactions_reaction_image')."\";");
				eval("\$post_reactions .= \"".$templates->get('myreactions_reaction')."\";");
			}

			if($mybb->user['uid'] > 0 && $post['uid'] != $mybb->user['uid'] && !($reacted && !$mybb->settings['myreactions_multiple']))
			{
				$force = (!$grouped_reactions?' reaction-add-force':'');
				eval("\$post_reactions .= \"".$templates->get('myreactions_add')."\";");
			}
			break;
	}

	if($reacted)
	{
		krsort($reacted);
		$reacted_with = $lang->myreactions_you_reacted_with;
		foreach($reacted as $r)
		{
			$reaction = $all_reactions[$r['post_reaction_rid']];
			$class = $onclick = '';
			$remove = ' <span onclick="MyReactions.remove('.$r['post_reaction_rid'].','.$r['post_reaction_pid'].');">('.$lang->myreactions_remove.')</span>';
			eval("\$reacted_with .= \"".$templates->get('myreactions_reaction_image')."\";");
		}
	}

	if($post_reactions)
	{
		eval("\$post['myreactions'] = \"".$templates->get('myreactions_container')."\";");
	}
}

function myreactions_react()
{
	global $mybb, $db, $lang, $cache, $templates, $theme;

	if($mybb->input['action'] == 'myreactions')
	{
		$all_reactions = $cache->read('myreactions');
		$lang->load('myreactions');

		$post = get_post($mybb->input['pid']);

		$given_reactions = myreactions_by_post_and_user($post['pid'], $mybb->user['uid']);

		$post_preview = htmlspecialchars_uni($post['message']);
		if(my_strlen($post['message']) > 100)
		{
			$post_preview = my_substr($post['message'], 0, 140).'...';
		}

		$reactions = $favourite_reactions = '';

		$has_favourites = false;
		$query = $db->simple_select('post_reactions', 'post_reaction_rid, count(post_reaction_id) as count', 'post_reaction_uid = \''.$mybb->user['uid'].'\'', array('group_by' => 'post_reaction_rid', 'order_by' => 'count', 'order_dir' => 'desc', 'limit' => 10));
		while($favourite_reaction = $db->fetch_array($query))
		{
			$has_favourites = true;
			$reaction = $all_reactions[$favourite_reaction['post_reaction_rid']];
			$class = $onclick = '';
			if(in_array($favourite_reaction['post_reaction_rid'], $given_reactions))
			{
				$class = ' class="disabled"';
			}
			else
			{
				$onclick = ' onclick="MyReactions.react('.$reaction['reaction_id'].','.$post['pid'].');"';
			}
			eval("\$favourite_reactions .= \"".$templates->get('myreactions_reaction_image', 1, 0)."\";");

		}
		if($has_favourites)
		{
			eval("\$favourites = \"".$templates->get('myreactions_react_favourites', 1, 0)."\";");
		}

		foreach($all_reactions as $reaction)
		{
			$class = $onclick = '';
			if(in_array($reaction['reaction_id'], $given_reactions))
			{
				$class = ' class="disabled"';
			}
			else
			{
				$onclick = ' onclick="MyReactions.react('.$reaction['reaction_id'].','.$post['pid'].');"';
			}
			eval("\$reactions .= \"".$templates->get('myreactions_reaction_image', 1, 0)."\";");
		}

		eval("\$myreactions = \"".$templates->get('myreactions_react', 1, 0)."\";");
		echo $myreactions;
		exit;
	}
	elseif($mybb->input['action'] == 'myreactions_react')
	{
		verify_post_check($mybb->input['my_post_key']);

		$lang->load('myreactions');

		$post = get_post($mybb->input['pid']);

		$given_reactions = myreactions_by_post_and_user($post['pid'], $mybb->user['uid']);

		if($post['uid'] == $mybb->user['uid'])
		{
			error($lang->myreactions_error_own_post);
		}
		if(!empty($given_reactions) && !$mybb->settings['myreactions_multiple'])
		{
			error($lang->myreactions_no_multiple);
		}
		if(in_array($mybb->input['rid'], $given_reactions))
		{
			error($lang->myreactions_already_reacted);
		}

		$db->insert_query('post_reactions', array('post_reaction_pid' => $post['pid'], 'post_reaction_rid' => $mybb->input['rid'], 'post_reaction_uid' => $mybb->user['uid'], 'post_reaction_date' => TIME_NOW));

		myreactions_postbit($post);
		echo $post['myreactions'];
		exit;
	}
	elseif($mybb->input['action'] == 'myreactions_remove')
	{
		verify_post_check($mybb->input['my_post_key']);

		$query = $db->simple_select('post_reactions', '*', 'post_reaction_pid = \''.$mybb->input['pid'].'\' and post_reaction_rid = \''.$mybb->input['rid'].'\' and post_reaction_uid = \''.$mybb->user['uid'].'\'');
		$post_reaction = $db->fetch_array($query);
		if($post_reaction)
		{
			$db->delete_query('post_reactions', 'post_reaction_id = \''.$post_reaction['post_reaction_id'].'\'');
			$post = get_post($post_reaction['post_reaction_pid']);
			myreactions_postbit($post);
			echo $post['myreactions'];
			exit;
		}
	}
}

function myreactions_profile()
{
	global $mybb, $db, $lang, $templates, $theme, $memprofile, $myreactions;

	if(!$mybb->settings['myreactions_profile'])
	{
		return;
	}

	$lang->load('myreactions');

	$reactions_received = $reactions_given = '';
	$size = $mybb->settings['myreactions_size'];

	$received_query = $db->query('
		SELECT '.TABLE_PREFIX.'myreactions.*, count(post_reaction_id) as count
		FROM '.TABLE_PREFIX.'myreactions
		JOIN '.TABLE_PREFIX.'post_reactions ON post_reaction_rid = reaction_id
		JOIN '.TABLE_PREFIX.'posts ON pid = post_reaction_pid AND '.TABLE_PREFIX.'posts.uid = \''.$memprofile['uid'].'\'
		GROUP BY reaction_id
		ORDER BY count DESC
		LIMIT 10
	');
	while($reaction = $db->fetch_array($received_query))
	{
		$count = $reaction['count'];
		eval("\$reaction_image = \"".$templates->get('myreactions_reaction_image')."\";");
		eval("\$reactions_received .= \"".$templates->get('myreactions_reaction')."\";");
	}
	if(!$reactions_received)
	{
		$reactions_received = $lang->myreactions_profile_none;
	}

	$given_query = $db->query('
		SELECT '.TABLE_PREFIX.'myreactions.*, count(post_reaction_id) as count
		FROM '.TABLE_PREFIX.'myreactions
		JOIN '.TABLE_PREFIX.'post_reactions ON post_reaction_rid = reaction_id AND post_reaction_uid = \''.$memprofile['uid'].'\'
		GROUP BY reaction_id
		ORDER BY count DESC
		LIMIT 10
	');
	while($reaction = $db->fetch_array($given_query))
	{
		$count = $reaction['count'];
		eval("\$reaction_image = \"".$templates->get('myreactions_reaction_image')."\";");
		eval("\$reactions_given .= \"".$templates->get('myreactions_reaction')."\";");
	}
	if(!$reactions_given)
	{
		$reactions_given = $lang->myreactions_profile_none;
	}

	eval("\$myreactions = \"".$templates->get('myreactions_profile', 1, 0)."\";");
}

function myreactions_by_post_and_user($pid, $uid)
{
	global $db;

	$given_reactions = array();
	$query = $db->simple_select('post_reactions', 'post_reaction_rid', 'post_reaction_pid = \''.$pid.'\' and post_reaction_uid = \''.$uid.'\'');
	while($rid = $db->fetch_field($query, 'post_reaction_rid'))
	{
		$given_reactions[] = $rid;
	}
	return $given_reactions;
}

function myreactions_admin_forum_menu($sub_menu)
{
	global $lang;

	$lang->load("forum_myreactions");

	$sub_menu[] = array("id" => "myreactions", "title" => $lang->myreactions, "link" => "index.php?module=forum-myreactions");

	return $sub_menu;
}

function myreactions_admin_forum_action_handler($actions)
{
	$actions['myreactions'] = array(
		"active" => "myreactions",
		"file" => "myreactions.php"
	);

	return $actions;
}

function myreactions_admin_forum_permissions($admin_permissions)
{
	global $lang;

	$lang->load("forum_myreactions");

	$admin_permissions['myreactions'] = $lang->can_manage_myreactions;

	return $admin_permissions;
}
