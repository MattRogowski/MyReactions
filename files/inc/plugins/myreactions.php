<?php
/**
 * MyReactions 0.1

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

$plugins->add_hook('postbit', 'myreactions_postbit');
$plugins->add_hook('misc_start', 'myreactions_react');

function myreactions_info()
{
	return array(
		"name" => "MyReactions",
		"description" => "Add emoji reactions to posts",
		"website" => "https://github.com/MattRogowski/MyReactions",
		"author" => "Matt Rogowski",
		"authorsite" => "https://matt.rogow.ski",
		"version" => "0.1",
		"compatibility" => "18*",
		"guid" => ""
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
		  `reaction_path` varchar(255) NOT NULL,
		  PRIMARY KEY (`reaction_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;');

		$reactions = array("angry","anguished","balloon","broken_heart","clap","confounded","confused","crossed_fingers","disappointed","disappointed_relieved","dizzy_face","expressionless","eyes","face_with_rolling_eyes","facepalm","fearful","fire","flushed","grimacing","grin","grinning","hear_no_evil","heart","heart_eyes","ill","information_desk_person","innocent","joy","laughing","mask","nerd_face","neutral_face","ok_hand","open_mouth","pensive","persevere","poop","pray","rage","raised_hands","rofl","scream","see_no_evil","shrug","sleeping","slightly_frowning_face","slightly_smiling_face","smile","smiling_imp","smirk","sob","speak_no_evil","star","stuck_out_tongue","stuck_out_tongue_closed_eyes","stuck_out_tongue_winking_eye","sunglasses","sweat","sweat_smile","tada","thinking_face","thumbsdown","thumbsup","tired_face","triumph","unamused","upside_down_face","v","white_frowning_face","wink","worried","zipper_mouth_face");

		foreach($reactions as $reaction)
		{
			$insert = array(
				'reaction_name' => ucwords(str_replace('_', ' ', $reaction)),
				'reaction_path' => 'images/reactions/'.$reaction.'.png'
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
	myreactions_cache();
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
	
	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

	find_replace_templatesets("postbit", "#".preg_quote('<div class="post_controls">')."#i", '{$post[\'myreactions\']}<div class="post_controls">');
	find_replace_templatesets("postbit_classic", "#".preg_quote('<div class="post_controls">')."#i", '{$post[\'myreactions\']}<div class="post_controls">');
	
	$templates = array();
	$templates[] = array(
		"title" => "myreactions_container",
		"template" => "<div style=\"clear:both\"></div>
<div class=\"myreactions-container reactions-{\$size}\">
  {\$post_reactions}
  <div class=\"myreactions-reacted\">{\$reacted_with}</div>
</div>"
	);
	$templates[] = array(
		"title" => "myreactions_reactions",
		"template" => "<div class=\"myreactions-reactions\">
  {\$reactions}<span>{\$lang->myreactions_add}</span>
  <div style=\"clear:both\"></div>
</div>"
	);
	$templates[] = array(
		"title" => "myreactions_reaction",
		"template" => "<div class=\"myreactions-reaction{\$class}\"{\$onclick}>
  {\$reaction_image} <span>{\$count}</span>
</div>"
	);
	$templates[] = array(
		"title" => "myreactions_reaction_image",
		"template" => "<img src=\"/{\$reaction['reaction_path']}\"{\$class} />{\$remove}{\$onclick}"
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
			{\$recent}
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
		"title" => "myreactions_react_recent",
		"template" => "<tr>
	<td class=\"tcat\">{\$lang->myreactions_recent}</td>
</tr>
<tr>
	<td class=\"trow1\" align=\"left\">
		{\$recent_reactions}
	</td>
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
}

function myreactions_deactivate()
{
	global $mybb, $db;
	
	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

	find_replace_templatesets("postbit", "#".preg_quote('{$post[\'myreactions\']}')."#i", '', 0);
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'myreactions\']}')."#i", '', 0);
	
	$db->delete_query("templates", "title IN ('myreactions_container','myreactions_reactions','myreactions_reaction','myreactions_reaction_image')");
}

function myreactions_cache()
{
	global $db, $cache;
	
	$query = $db->simple_select('myreactions');
	$myreactions = array();
	while($myreaction = $db->fetch_array($query))
	{
		$myreactions[] = $myreaction;
	}
	$cache->update('myreactions', $myreactions);
}

function myreactions_postbit(&$post)
{
	global $lang, $cache, $templates;

	$all_reactions = $cache->read('myreactions');
	$lang->load('myreactions');

	shuffle($all_reactions);
	$number = rand(0, 5);
	$type = rand(0, 1);
	$sizes = array(16,20,24,28,32);
	$size = $sizes[0];

	switch($type)
	{
		case 0:
			$reactions = '';
			for($i = 1; $i <= $number; $i++)
			{
				$k = $i - 1;
				$reaction = $all_reactions[$k];
				eval("\$reactions .= \"".$templates->get('myreactions_reaction_image')."\";");
			}

			$reaction = array('reaction_path' => 'images/reactions/plus.png');
			$class = ' class="reaction-add'.(!$number?' reaction-add-force':'').'"';
			$onclick = ' onclick="MyReactions.react('.$post['pid'].');"';
			eval("\$reactions .= \"".$templates->get('myreactions_reaction_image')."\";");

			eval("\$post_reactions = \"".$templates->get('myreactions_reactions')."\";");
			break;
		case 1:
			$post_reactions = '';
			for($i = 1; $i <= $number; $i++)
			{
				$k = $i - 1;
				$reaction = $all_reactions[$k];
				eval("\$reaction_image = \"".$templates->get('myreactions_reaction_image')."\";");
				$count = rand(1, 100);
				eval("\$post_reactions .= \"".$templates->get('myreactions_reaction')."\";");
			}

			$reaction = array('reaction_path' => 'images/reactions/plus.png');
			$count = $lang->myreactions_add;
			eval("\$reaction_image = \"".$templates->get('myreactions_reaction_image')."\";");
			$class = ' reaction-add';
			if(!$number)
			{
				$class .= ' reaction-add-force';
			}
			$onclick = ' onclick="MyReactions.react('.$post['pid'].');"';
			eval("\$post_reactions .= \"".$templates->get('myreactions_reaction')."\";");
			break;
	}

	$reacted_with = $lang->myreactions_you_reacted_with;
	$reaction = $all_reactions[$k];
	$class = $onclick = '';
	$remove = ' ('.$lang->myreactions_remove.')';
	eval("\$reacted_with .= \"".$templates->get('myreactions_reaction_image')."\";");

	eval("\$post['myreactions'] = \"".$templates->get('myreactions_container')."\";");
}

function myreactions_react()
{
	global $mybb, $lang, $cache, $templates, $theme;

	if($mybb->input['action'] == 'myreactions')
	{
		$all_reactions = $cache->read('myreactions');
		$lang->load('myreactions');

		$post = get_post($mybb->input['pid']);
		$post_preview = $post['message'];
		if(my_strlen($post['message']) > 100)
		{
			$post_preview = my_substr($post['message'], 0, 140).'...';
		}

		$reactions = $recent_reactions = '';
		foreach($all_reactions as $reaction)
		{
			eval("\$reactions .= \"".$templates->get('myreactions_reaction_image', 1, 0)."\";");
		}
		
		shuffle($all_reactions);
		$number = rand(1, 10);
		for($i = 1; $i <= $number; $i++)
		{
			$k = $i - 1;
			$reaction = $all_reactions[$k];
			eval("\$recent_reactions .= \"".$templates->get('myreactions_reaction_image', 1, 0)."\";");
		}
		eval("\$recent = \"".$templates->get('myreactions_react_recent', 1, 0)."\";");

		eval("\$myreactions = \"".$templates->get('myreactions_react', 1, 0)."\";");
		echo $myreactions;
		exit;
	}
}

/*
.myreactions-container {
  padding: 10px;
  border-top: 1px solid #ccc;
}
.myreactions-reaction {
  display: inline-block;
  margin: 2px;
  padding: 5px;
}
.myreactions-reactions, .myreactions-reaction {
  background: #f5f5f5;
  border: 1px solid #ccc;
  display: inline-block;
  border-radius: 6px;
}
.myreactions-reaction span {
  float: right;
  margin-left: 5px;
}
.myreactions-reactions .reaction-add, .myreactions-reaction.reaction-add {
  display: none;
}
.myreactions-container:hover .reaction-add, .reaction-add.reaction-add-force {
  display: inline-block;
}
.myreactions-reaction.reaction-add span, .myreactions-reactions .reaction-add + span {
  display: none;
}
.myreactions-reactions .reaction-add + span {
  margin-right: 5px;
}
.myreactions-reaction.reaction-add.reaction-add-force span, .myreactions-reactions .reaction-add.reaction-add-force + span {
  display: inline;
}
.myreactions-reactions img {
  margin: 5px;
  float: left;
  display: inline-block;
}
.myreactions-container .myreactions-reacted img {
  position: relative;
}
.myreactions-container.reactions-16 img {
  width: 16px;
  height: 16px;
}
.myreactions-container.reactions-16 .myreactions-reaction span, .myreactions-container.reactions-16 .myreactions-reacted {
  font-size: 12px;
  line-height: 16px;
}
.myreactions-container.reactions-16 .myreactions-reactions .reaction-add + span {
  font-size: 12px;
  line-height: 26px;
}
.myreactions-container.reactions-16 .myreactions-reacted img {
  top: 4px;
}
.myreactions-container.reactions-20 img {
  width: 20px;
  height: 20px;
}
.myreactions-container.reactions-20 .myreactions-reaction span, .myreactions-container.reactions-20 .myreactions-reacted {
  font-size: 13px;
  line-height: 20px;
}
.myreactions-container.reactions-20 .myreactions-reactions .reaction-add + span {
  font-size: 13px;
  line-height: 30px;
}
.myreactions-container.reactions-20 .myreactions-reacted img {
  top: 6px;
}
.myreactions-container.reactions-24 img {
  width: 24px;
  height: 24px;
}
.myreactions-container.reactions-24 .myreactions-reaction span, .myreactions-container.reactions-24 .myreactions-reacted {
  font-size: 14px;
  line-height: 24px;
}
.myreactions-container.reactions-24 .myreactions-reactions .reaction-add + span {
  font-size: 14px;
  line-height: 34px;
}
.myreactions-container.reactions-24 .myreactions-reacted img {
  top: 7px;
}
.myreactions-container.reactions-28 img {
  width: 28px;
  height: 28px;
}
.myreactions-container.reactions-28 .myreactions-reaction span, .myreactions-container.reactions-28 .myreactions-reacted {
  font-size: 15px;
  line-height: 28px;
}
.myreactions-container.reactions-28 .myreactions-reactions .reaction-add + span {
  font-size: 15px;
  line-height: 38px;
}
.myreactions-container.reactions-28 .myreactions-reacted img {
  top: 7px;
}
.myreactions-container.reactions-32 img {
  width: 32px;
  height: 32px;
}
.myreactions-container.reactions-32 .myreactions-reaction span, .myreactions-container.reactions-32 .myreactions-reacted {
  font-size: 16px;
  line-height: 32px;
}
.myreactions-container.reactions-32 .myreactions-reactions .reaction-add + span {
  font-size: 16px;
  line-height: 42px;
}
.myreactions-container.reactions-32 .myreactions-reacted img {
  top: 8px;
}
.myreactions-react img {
	width: 24px;
	height: 24px;
	padding: 5px;
}
*/