<?php
/**
 * MyReactions 0.0.3

 * Copyright 2016 Matthew Rogowski

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

define("MYREACTIONS_VERSION", "0.0.3");

$plugins->add_hook('showthread_start', 'myreactions_showthread');
$plugins->add_hook('postbit', 'myreactions_postbit');
$plugins->add_hook('forumdisplay_thread', 'myreactions_forumdisplay');
$plugins->add_hook('member_profile_end', 'myreactions_profile');
$plugins->add_hook('misc_start', 'myreactions_misc');
$plugins->add_hook("admin_forum_menu", "myreactions_admin_forum_menu");
$plugins->add_hook("admin_forum_action_handler", "myreactions_admin_forum_action_handler");
$plugins->add_hook("admin_forum_permissions", "myreactions_admin_forum_permissions");
$plugins->add_hook("admin_page_output_footer", "myreactions_settings_footer");

global $templatelist;
$templatelist .= ',myreactions_container,myreactions_reactions,myreactions_reaction,myreactions_reaction_image,myreactions_add,myreactions_react,myreactions_react_favourites,myreactions_profile,myreactions_reacted_button,myreactions_reacted,myreactions_reacted_row_grouped,myreactions_reacted_row_linear,myreactions_reacted_row_user';

function myreactions_info()
{
	require_once MYBB_ROOT.'inc/plugins/myreactions/myreactions.php';

	return myreactions_do_info();
}

function myreactions_install()
{
	require_once MYBB_ROOT.'inc/plugins/myreactions/myreactions.php';

	return myreactions_do_install();
}

function myreactions_is_installed()
{
	require_once MYBB_ROOT.'inc/plugins/myreactions/myreactions.php';

	return myreactions_do_is_installed();
}

function myreactions_uninstall()
{
	require_once MYBB_ROOT.'inc/plugins/myreactions/myreactions.php';

	return myreactions_do_uninstall();
}

function myreactions_db_changes()
{
	require_once MYBB_ROOT.'inc/plugins/myreactions/myreactions.php';

	return myreactions_do_db_changes();
}

function myreactions_activate()
{
	require_once MYBB_ROOT.'inc/plugins/myreactions/myreactions.php';

	return myreactions_do_activate();
}

function myreactions_deactivate()
{
	require_once MYBB_ROOT.'inc/plugins/myreactions/myreactions.php';

	return myreactions_do_deactivate();
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

	if(!$mybb->user['uid'] || $mybb->usergroup['isbannedgroup'])
	{
		return;
	}

	if(in_array($mybb->input['action'], array('myreactions_react','myreactions_remove')))
	{
		myreactions_showthread($post);
	}

	$all_reactions = $cache->read('myreactions');
	$lang->load('myreactions');

	$received_reactions = $reacted_ids = $reacted_reactions = array();
	if(array_key_exists($post['pid'], $thread_reactions))
	{
		$received_reactions = $thread_reactions[$post['pid']];
		foreach($received_reactions as $reaction)
		{
			if($reaction['post_reaction_uid'] == $mybb->user['uid'])
			{
				$reacted_ids[] = $reaction['post_reaction_id'];
				$reacted_reactions[] = $reaction['post_reaction_rid'];
			}
		}
	}

	$size = $mybb->settings['myreactions_size'];
	$post_reactions = '';
	if($mybb->input['myreactions_debug'])
	{
		$mybb->settings['myreactions_type'] = $mybb->input['myreactions_type'];
	}
	$reacted_count = count($received_reactions);
	switch($mybb->settings['myreactions_type'])
	{
		case 'linear':
			krsort($received_reactions);
			$reactions = '';
			foreach($received_reactions as $received_reaction)
			{
				$class = $onclick = '';
				$reaction = $all_reactions[$received_reaction['post_reaction_rid']];
				$title = ' title="'.$received_reaction['reaction_name'];
				if(in_array($received_reaction['post_reaction_id'], $reacted_ids))
				{
					$class = ' class="myreactions-reacted"';
					$onclick = ' onclick="MyReactions.remove('.$received_reaction['post_reaction_rid'].','.$post['pid'].');"';
					$title .= ' - '.$lang->myreactions_remove;
				}
				$title .= '"';
				eval("\$reactions .= \"".$templates->get('myreactions_reaction_image')."\";");
			}
			if($reactions)
			{
				eval("\$post_reactions = \"".$templates->get('myreactions_reactions')."\";");
			}

			if($mybb->user['uid'] > 0 && $post['uid'] != $mybb->user['uid'] && !($reacted_ids && !$mybb->settings['myreactions_multiple']))
			{
				$force = (!$reactions?' reaction-add-force':'');
				eval("\$post_reactions .= \"".$templates->get('myreactions_add')."\";");
			}
			if($reactions)
			{
				eval("\$post_reactions .= \"".$templates->get('myreactions_reacted_button')."\";");
			}
			break;
		case 'grouped':
			$reacted_count = 0;
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
				$class = $onclick = $title = '';
				eval("\$reaction_image = \"".$templates->get('myreactions_reaction_image')."\";");
				$title = ' title="'.$info['name'];
				if(in_array($rid, $reacted_reactions))
				{
					$class = ' myreactions-reacted';
					$onclick = ' onclick="MyReactions.remove('.$rid.','.$post['pid'].');"';
					$title .= ' - '.$lang->myreactions_remove;
				}
				$title .= '"';
				eval("\$post_reactions .= \"".$templates->get('myreactions_reaction')."\";");
			}

			if($mybb->user['uid'] > 0 && $post['uid'] != $mybb->user['uid'] && !($reacted_ids && !$mybb->settings['myreactions_multiple']))
			{
				$force = (!$grouped_reactions?' reaction-add-force':'');
				eval("\$post_reactions .= \"".$templates->get('myreactions_add')."\";");
			}
			if($grouped_reactions)
			{
				$reacted_count = count($received_reactions);
				eval("\$post_reactions .= \"".$templates->get('myreactions_reacted_button')."\";");
			}
			break;
	}

	if($post_reactions)
	{
		eval("\$post['myreactions'] = \"".$templates->get('myreactions_container')."\";");
	}

	$post['user_details'] = str_replace('{myreactions}', '<br />'.$lang->sprintf($lang->myreactions_received_postbit, '<a href="javascript:void(0)" onclick="MyReactions.reactedUser('.$post['uid'].', \'received\');"><strong>'.$post['reactions_received'].'</strong></a>').'<br />'.$lang->sprintf($lang->myreactions_given_postbit, '<a href="javascript:void(0)" onclick="MyReactions.reactedUser('.$post['uid'].', \'given\');"><strong>'.$post['reactions_given'].'</strong></a>'), $post['user_details']);
}

function myreactions_misc()
{
	global $mybb, $db, $lang, $cache, $templates, $theme;

	if(!$mybb->user['uid'] || $mybb->usergroup['isbannedgroup'])
	{
		return;
	}

	if($mybb->input['action'] == 'myreactions')
	{
		$all_reactions = $cache->read('myreactions');
		$lang->load('myreactions');

		$post = get_post($mybb->input['pid']);

		$given_reactions = myreactions_by_post_and_user($post['pid'], $mybb->user['uid']);

		require_once MYBB_ROOT.'inc/class_parser.php';
		$parser = new postParser;
		$post_preview = $parser->text_parse_message($post['message'], array('filter_badwords' => true));
		if(my_strlen($post_preview) > 140)
		{
			$post_preview = my_substr($post_preview, 0, 140).'...';
		}

		$reactions = $favourite_reactions = $given_to_post_reactions = '';

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
			$title = ' title="'.$reaction['reaction_name'].'"';
			eval("\$favourite_reactions .= \"".$templates->get('myreactions_reaction_image', 1, 0)."\";");

		}
		if($has_favourites)
		{
			$title = $lang->myreactions_favourites;
			$filtered_reactions = $favourite_reactions;
			eval("\$favourites = \"".$templates->get('myreactions_react_filtered', 1, 0)."\";");
		}

		$has_given_to_post = false;
		$query = $db->simple_select('post_reactions', 'post_reaction_rid, count(post_reaction_id) as count', 'post_reaction_pid = \''.$post['pid'].'\'', array('group_by' => 'post_reaction_rid', 'order_by' => 'count', 'order_dir' => 'desc', 'limit' => 10));
		while($favourite_reaction = $db->fetch_array($query))
		{
			$has_given_to_post = true;
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
			$title = ' title="'.$reaction['reaction_name'].'"';
			eval("\$given_to_post_reactions .= \"".$templates->get('myreactions_reaction_image', 1, 0)."\";");

		}
		if($has_given_to_post)
		{
			$title = $lang->myreactions_given_to_post;
			$filtered_reactions = $given_to_post_reactions;
			eval("\$given_to_post = \"".$templates->get('myreactions_react_filtered', 1, 0)."\";");
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
			$title = ' title="'.$reaction['reaction_name'].'"';
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

		myreactions_recount_received($post['uid']);
		myreactions_recount_given($mybb->user['uid']);

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

			myreactions_recount_received($post['uid']);
			myreactions_recount_given($mybb->user['uid']);

			myreactions_postbit($post);

			echo $post['myreactions'];
			exit;
		}
	}
	elseif($mybb->input['action'] == 'myreactions_reacted')
	{
		$lang->load('myreactions');

		$reactions_grouped = $reactions_linear = $reactions_user = array();
		$users_join = 'JOIN '.TABLE_PREFIX.'users u ON (pr.post_reaction_uid = u.uid)';
		if($mybb->input['pid'])
		{
			$where = 'pr.post_reaction_pid = \''.$mybb->input['pid'].'\'';
			$reacted_heading = $lang->myreactions_who_reacted_heading_post;
		}
		elseif($mybb->input['uid'])
		{
			$user = get_user($mybb->input['uid']);
			switch($mybb->input['type'])
			{
				case 'received':
					$reacted_heading = $lang->sprintf($lang->myreactions_who_reacted_heading_user_received, $user['username']);
					$where = 'p.uid = \''.$mybb->input['uid'].'\'';
					break;
				case 'given':
					$reacted_heading = $lang->sprintf($lang->myreactions_who_reacted_heading_user_given, $user['username']);
					$users_join = 'JOIN '.TABLE_PREFIX.'users u ON (p.uid = u.uid)';
					$where = 'pr.post_reaction_uid = \''.$mybb->input['uid'].'\'';
					break;
			}
		}
		$post_reactions = $db->write_query('
			SELECT pr.*, r.*, u.username AS username, u.uid AS uid, u.usergroup as usergroup, u.displaygroup as displaygroup
			FROM '.TABLE_PREFIX.'post_reactions pr
			JOIN '.TABLE_PREFIX.'myreactions r ON (pr.post_reaction_rid = r.reaction_id)
			JOIN '.TABLE_PREFIX.'posts p ON (pr.post_reaction_pid = p.pid)
			'.$users_join.'
			WHERE '.$where.'
			ORDER BY post_reaction_date DESC
		');
		while($post_reaction = $db->fetch_array($post_reactions))
		{
			if(!array_key_exists($post_reaction['post_reaction_rid'], $reactions_grouped))
			{
				$reactions_grouped[$post_reaction['post_reaction_rid']] = array('count' => 0, 'reacted' => array());
			}
			$reactions_grouped[$post_reaction['post_reaction_rid']]['count']++;
			$reactions_grouped[$post_reaction['post_reaction_rid']]['reacted'][] = $post_reaction;
			$reactions_linear[] = $post_reaction;
			if(!array_key_exists($post_reaction['username'], $reactions_user))
			{
				$reactions_user[$post_reaction['username']] = array();
			}
			$reactions_user[$post_reaction['username']][] = $post_reaction;
		}
		usort($reactions_grouped, function($a, $b) {
			return $a['count'] < $b['count'];
		});
		krsort($reactions_linear);
		ksort($reactions_user);
		$reactions_grouped = array_values($reactions_grouped);
		$reactions_user = array_values($reactions_user);
		$reacted_grouped = $reacted_linear = $reacted_user = '';
		foreach($reactions_grouped as $i => $r)
		{
			$trow = alt_trow($i == 0);
			$reaction = $r['reacted'][0];
			$title = ' title="'.$r['reacted'][0]['reaction_name'].'"';
			eval("\$image = \"".$templates->get('myreactions_reaction_image', 1, 0)."\";");
			$count = 0;
			$users = array();
			foreach($r['reacted'] as $u)
			{
				$count++;
				if(!array_key_exists($u['uid'], $users))
				{
					$users[$u['uid']] = array('count' => 0, 'user' => $u);
				}
				$users[$u['uid']]['count']++;
			}
			foreach($users as $uid => $info)
			{
				$formatted_name = format_name($info['user']['username'], $info['user']['usergroup'], $info['user']['displaygroup']);
				if($info['count'] > 1)
				{
					$formatted_name .= ' (x'.$info['count'].')';
				}
				$users[$uid]['link'] = build_profile_link($formatted_name, $info['user']['uid'], '_blank');
			}
			usort($users, function($a, $b) {
				return $a['count'] < $b['count'];
			});
			$built_users = array();
			foreach($users as $user)
			{
				$built_users[] = $user['link'];
			}
			$users = implode(', ', $built_users);
			eval("\$reacted_grouped .= \"".$templates->get('myreactions_reacted_row_grouped', 1, 0)."\";");
		}
		foreach($reactions_linear as $i => $r)
		{
			$trow = alt_trow($i == 0);
			$reaction = $r;
			$title = ' title="'.$r['reaction_name'].'"';
			eval("\$image = \"".$templates->get('myreactions_reaction_image', 1, 0)."\";");
			$user = build_profile_link(format_name($r['username'], $r['usergroup'], $r['displaygroup']), $r['uid'], '_blank');
			$date = my_date($mybb->settings['dateformat'].' @ '.$mybb->settings['timeformat'], $r['post_reaction_date']);
			eval("\$reacted_linear .= \"".$templates->get('myreactions_reacted_row_linear', 1, 0)."\";");

			$title = ' title="'.$r['reaction_name'].' - '.$r['username'].' - '.$date.'"';
			eval("\$image = \"".$templates->get('myreactions_reaction_image', 1, 0)."\";");
			$reacted_all .= $image;
		}
		foreach($reactions_user as $i => $r)
		{
			$trow = alt_trow($i == 0);
			$user = build_profile_link(format_name($r[0]['username'], $r[0]['usergroup'], $r[0]['displaygroup']), $r[0]['uid'], '_blank');
			$images = '';
			krsort($r);
			foreach($r as $reaction)
			{
				$title = ' title="'.$reaction['reaction_name'].'"';
				eval("\$images .= \"".$templates->get('myreactions_reaction_image', 1, 0)."\";");
			}
			eval("\$reacted_user .= \"".$templates->get('myreactions_reacted_row_user', 1, 0)."\";");
		}
		eval("\$reacted = \"".$templates->get('myreactions_reacted', 1, 0)."\";");
		echo $reacted;
		exit;
	}
}

function myreactions_forumdisplay()
{
	global $mybb, $db, $templates, $thread, $threadcache, $all_thread_reactions;

	if(!$mybb->settings['myreactions_forumdisplay'])
	{
		return;
	}

	if(!$all_thread_reactions)
	{
		$limit = $mybb->settings['myreactions_forumdisplay_count'];
		if(!$limit || !is_numeric($limit) || $limit <= 0)
		{
			$limit = 10;
		}
		$where = '';
		if($mybb->settings['myreactions_forumdisplay_type'] == 'post')
		{
			$where = ' AND t.firstpost = p.pid';
		}

		$all_thread_reactions = $tids = array();
		foreach($threadcache as $t)
		{
			$tids[] = $t['tid'];
		}
		$query = $db->write_query('
			SELECT r.*, t.tid, count(post_reaction_id) AS count
			FROM '.TABLE_PREFIX.'myreactions r
			JOIN '.TABLE_PREFIX.'post_reactions pr ON pr.post_reaction_rid = r.reaction_id
			JOIN '.TABLE_PREFIX.'posts p on p.pid = pr.post_reaction_pid
			JOIN '.TABLE_PREFIX.'threads t on t.tid = p.tid
			WHERE t.tid IN('.implode(',', $tids).')
			'.$where.'
			GROUP BY t.tid, reaction_id ORDER BY t.tid ASC, count DESC, pr.post_reaction_date ASC
		');
		while($reaction = $db->fetch_array($query))
		{
			if(!array_key_exists($reaction['tid'], $all_thread_reactions))
			{
				$all_thread_reactions[$reaction['tid']] = array();
			}
			if(count($all_thread_reactions[$reaction['tid']]) == $limit)
			{
				continue;
			}
			$all_thread_reactions[$reaction['tid']][] = $reaction;
		}
	}

	$thread['reactions'] = '';
	if(array_key_exists($thread['tid'], $all_thread_reactions))
	{
		$thread_reaction_images = '';
		foreach($all_thread_reactions[$thread['tid']] as $reaction)
		{
			$thread_reaction_images .= '<img src="'.$reaction['reaction_image'].'" width="16" height="16" />';
		}
		eval("\$thread['reactions'] = \"".$templates->get('myreactions_forumdisplay_thread')."\";");
	}
}

function myreactions_profile()
{
	global $mybb, $db, $lang, $templates, $theme, $memprofile, $myreactions;

	if(!$mybb->user['uid'] || $mybb->usergroup['isbannedgroup'])
	{
		return;
	}

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
		$title = '';
		eval("\$reaction_image = \"".$templates->get('myreactions_reaction_image')."\";");
		$title = ' title="'.$reaction['reaction_name'].'"';
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
		JOIN '.TABLE_PREFIX.'posts on post_reaction_pid = pid
		GROUP BY reaction_id
		ORDER BY count DESC
		LIMIT 10
	');
	while($reaction = $db->fetch_array($given_query))
	{
		$count = $reaction['count'];
		$title = '';
		eval("\$reaction_image = \"".$templates->get('myreactions_reaction_image')."\";");
		$title = ' title="'.$reaction['reaction_name'].'"';
		eval("\$reactions_given .= \"".$templates->get('myreactions_reaction')."\";");
	}
	if(!$reactions_given)
	{
		$reactions_given = $lang->myreactions_profile_none;
	}

	$lang->myreactions_received = $lang->sprintf($lang->myreactions_received_profile, $memprofile['reactions_received']);
	$lang->myreactions_given = $lang->sprintf($lang->myreactions_given_profile, $memprofile['reactions_given']);

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

function myreactions_recount_received($uid)
{
	global $db;

	$query = $db->write_query('
		SELECT count(post_reaction_id) as count
		FROM '.TABLE_PREFIX.'post_reactions pr
		JOIN '.TABLE_PREFIX.'posts p
		ON (pr.post_reaction_pid = p.pid)
		WHERE p.uid = \''.intval($uid).'\'
	');
	$count = $db->fetch_field($query, 'count');
	$db->update_query('users', array('reactions_received' => $count), 'uid = \''.intval($uid).'\'');
}

function myreactions_recount_given($uid)
{
	global $db;

	$query = $db->write_query('
		SELECT count(post_reaction_id) as count
		FROM '.TABLE_PREFIX.'post_reactions pr
		JOIN '.TABLE_PREFIX.'posts p
		ON (pr.post_reaction_pid = p.pid)
		WHERE pr.post_reaction_uid = \''.intval($uid).'\'
	');
	$count = $db->fetch_field($query, 'count');
	$db->update_query('users', array('reactions_given' => $count), 'uid = \''.intval($uid).'\'');
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

function myreactions_settings_footer()
{
	global $mybb, $db;
	// we're viewing the form to change settings but not submitting it
	if($mybb->input["action"] == "change" && $mybb->request_method != "post")
	{
		$query = $db->simple_select('settinggroups', 'gid', 'name = \'myreactions\'');
		$gid = $db->fetch_field($query, 'gid');
		// if the settings group we're editing is the same as the gid for the MySupport group, or there's no gid (viewing all settings), echo the peekers
		if($mybb->input["gid"] == $gid || !$mybb->input['gid'])
		{
			echo '<script type="text/javascript">
			jQuery(document).ready(function() {
				new Peeker($(".setting_myreactions_forumdisplay"), $("#row_setting_myreactions_forumdisplay_type, #row_setting_myreactions_forumdisplay_count"), 1, true)
			});
			</script>';
		}
	}
}
