<?php
/**
 * MyReactions 0.0.4 - Admin File

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
**/

if(!defined("IN_MYBB"))
{
	header("HTTP/1.0 404 Not Found");
	exit;
}

$page->add_breadcrumb_item($lang->myreactions, "index.php?module=forum-myreactions");

if($mybb->input['action'] == "add")
{
	if($mybb->request_method == "post")
	{
		if(!trim($mybb->input['reaction_name']))
		{
			$errors[] = $lang->error_missing_name;
		}

		if(!trim($mybb->input['reaction_image']))
		{
			$errors[] = $lang->error_missing_path;
		}

		if(!$errors)
		{
			$new_reaction = array(
				"reaction_name" => $db->escape_string($mybb->input['reaction_name']),
				"reaction_image" => $db->escape_string($mybb->input['reaction_image'])
			);

			$reaction_id = $db->insert_query("myreactions", $new_reaction);

			myreactions_cache();

			// Log admin action
			log_admin_action($reaction_id, htmlspecialchars_uni($mybb->input['reaction_name']));

			flash_message($lang->success_reaction_added, 'success');
			admin_redirect("index.php?module=forum-myreactions");
		}
	}

	$page->add_breadcrumb_item($lang->add_reaction);
	$page->output_header($lang->myreactions." - ".$lang->add_reaction);

	$sub_tabs['manage_reactions'] = array(
		'title' => $lang->manage_reactions,
		'link' => "index.php?module=forum-myreactions",
	);
	$sub_tabs['add_reaction'] = array(
		'title' => $lang->add_reaction,
		'link' => "index.php?module=forum-myreactions&amp;action=add",
		'description' => $lang->add_reaction_desc
	);
	$sub_tabs['add_multiple_reactions'] = array(
		'title' => $lang->add_multiple_reactions,
		'link' => "index.php?module=forum-myreactions&amp;action=add_multiple",
	);
	$sub_tabs['mass_edit'] = array(
		'title' => $lang->mass_edit,
		'link' => "index.php?module=forum-myreactions&amp;action=mass_edit"
	);
	$sub_tabs['import'] = array(
		'title' => $lang->import,
		'link' => "index.php?module=forum-myreactions&amp;action=import"
	);

	$page->output_nav_tabs($sub_tabs, 'add_reaction');
	$form = new Form("index.php?module=forum-myreactions&amp;action=add", "post", "add");

	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$mybb->input['reaction_image'] = 'images/reactions/';
	}

	$form_container = new FormContainer($lang->add_reaction);
	$form_container->output_row($lang->name." <em>*</em>", "", $form->generate_text_box('reaction_name', $mybb->input['reaction_name'], array('id' => 'reaction_name')), 'reaction_name');
	$form_container->output_row($lang->image_path." <em>*</em>", $lang->image_path_desc, $form->generate_text_box('reaction_image', $mybb->input['reaction_image'], array('id' => 'reaction_image')), 'reaction_image');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_reaction);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "edit")
{
	$query = $db->simple_select("myreactions", "*", "reaction_id='".$mybb->get_input('reaction_id', MyBB::INPUT_INT)."'");
	$reaction = $db->fetch_array($query);

	// Does the reaction not exist?
	if(!$reaction['reaction_id'])
	{
		flash_message($lang->error_invalid_reaction, 'error');
		admin_redirect("index.php?module=forum-myreactions");
	}

	if($mybb->request_method == "post")
	{
		if(!trim($mybb->input['reaction_name']))
		{
			$errors[] = $lang->error_missing_name;
		}

		if(!trim($mybb->input['reaction_image']))
		{
			$errors[] = $lang->error_missing_path;
		}

		if(!$errors)
		{
			$updated_reaction = array(
				"reaction_name" => $db->escape_string($mybb->input['reaction_name']),
				"reaction_image" => $db->escape_string($mybb->input['reaction_image'])
			);

			$db->update_query("myreactions", $updated_reaction, "reaction_id = '{$reaction['reaction_id']}'");

			myreactions_cache();

			// Log admin action
			log_admin_action($reaction['reaction_id'], htmlspecialchars_uni($mybb->input['reaction_name']));

			flash_message($lang->success_reaction_updated, 'success');
			admin_redirect("index.php?module=forum-myreactions");
		}
	}

	$page->add_breadcrumb_item($lang->edit_reaction);
	$page->output_header($lang->myreactions." - ".$lang->edit_reaction);

	$sub_tabs['edit_reaction'] = array(
		'title' => $lang->edit_reaction,
		'link' => "index.php?module=forum-myreactions&amp;action=edit",
		'description' => $lang->edit_reaction_desc
	);
	$sub_tabs['mass_edit'] = array(
		'title' => $lang->mass_edit,
		'link' => "index.php?module=forum-myreactions&amp;action=mass_edit",
	);

	$page->output_nav_tabs($sub_tabs, 'edit_reaction');
	$form = new Form("index.php?module=forum-myreactions&amp;action=edit", "post", "edit");

	echo $form->generate_hidden_field("reaction_id", $reaction['reaction_id']);

	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$mybb->input = array_merge($mybb->input, $reaction);
	}

	$form_container = new FormContainer($lang->edit_reaction);
	$form_container->output_row($lang->name." <em>*</em>", "", $form->generate_text_box('reaction_name', $mybb->input['reaction_name'], array('id' => 'reaction_name')), 'reaction_name');
	$form_container->output_row($lang->image_path." <em>*</em>", $lang->image_path_desc, $form->generate_text_box('reaction_image', $mybb->input['reaction_image'], array('id' => 'reaction_image')), 'reaction_image');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_reaction);
	$buttons[] = $form->generate_reset_button($lang->reset);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "delete")
{
	$query = $db->simple_select("myreactions", "*", "reaction_id='".$mybb->get_input('reaction_id', MyBB::INPUT_INT)."'");
	$reaction = $db->fetch_array($query);

	// Does the reaction not exist?
	if(!$reaction['reaction_id'])
	{
		flash_message($lang->error_invalid_reaction, 'error');
		admin_redirect("index.php?module=forum-myreactions");
	}

	// User clicked no
	if($mybb->input['no'])
	{
		admin_redirect("index.php?module=forum-myreactions");
	}

	if($mybb->request_method == "post")
	{
		// Delete the reaction
		$db->delete_query("myreactions", "reaction_id='{$reaction['reaction_id']}'");

		myreactions_cache();

		// Log admin action
		log_admin_action($reaction['reaction_id'], htmlspecialchars_uni($reaction['reaction_name']));

		flash_message($lang->success_reaction_updated, 'success');
		admin_redirect("index.php?module=forum-myreactions");
	}
	else
	{
		$page->output_confirm_action("index.php?module=forum-myreactions&amp;action=delete&amp;reaction_id={$reaction['reaction_id']}", $lang->confirm_reaction_deletion);
	}}

if($mybb->input['action'] == "add_multiple")
{
	if($mybb->request_method == "post")
	{
		if($mybb->input['step'] == 1)
		{
			if(!trim($mybb->input['pathfolder']))
			{
				$errors[] = $lang->error_missing_path_multiple;
			}

			$path = $mybb->input['pathfolder'];
			$dir = @opendir(MYBB_ROOT.$path);

			if(!$dir)
			{
				$errors[] = $lang->error_invalid_path;
			}

			if($path && !is_array($errors))
			{
				if(substr($path, -1, 1) !== "/")
				{
					$path .= "/";
				}

				$query = $db->simple_select("myreactions");

				$amyreactions = array();
				while($reaction = $db->fetch_array($query))
				{
					$amyreactions[$reaction['reaction_image']] = 1;
				}

				$myreactions = array();
				while($file = readdir($dir))
				{
					if($file != ".." && $file != ".")
					{
						$ext = get_extension($file);
						if($ext == "gif" || $ext == "jpg" || $ext == "jpeg" || $ext == "png" || $ext == "bmp")
						{
							if(!$amyreactions[$path.$file])
							{
								$myreactions[] = $file;
							}
						}
					}
				}
				closedir($dir);

				if(count($myreactions) == 0)
				{
					$errors[] = $lang->error_no_reactions;
				}
			}

			if(!$errors)
			{
				$page->add_breadcrumb_item($lang->add_multiple_reactions);
				$page->output_header($lang->myreactions." - ".$lang->add_multiple_reactions);

				$sub_tabs['manage_reactions'] = array(
					'title' => $lang->manage_reactions,
					'link' => "index.php?module=forum-myreactions",
				);
				$sub_tabs['add_reaction'] = array(
					'title' => $lang->add_reaction,
					'link' => "index.php?module=forum-myreactions&amp;action=add"
				);
				$sub_tabs['add_multiple_reactions'] = array(
					'title' => $lang->add_multiple_reactions,
					'link' => "index.php?module=forum-myreactions&amp;action=add_multiple",
					'description' => $lang->add_multiple_reactions_desc
				);
				$sub_tabs['mass_edit'] = array(
					'title' => $lang->mass_edit,
					'link' => "index.php?module=forum-myreactions&amp;action=mass_edit"
				);
				$sub_tabs['import'] = array(
					'title' => $lang->import,
					'link' => "index.php?module=forum-myreactions&amp;action=import"
				);

				$page->output_nav_tabs($sub_tabs, 'add_multiple_reactions');
				$form = new Form("index.php?module=forum-myreactions&amp;action=add_multiple", "post", "add_multiple");
				echo $form->generate_hidden_field("step", "2");
				echo $form->generate_hidden_field("pathfolder", $path);

				$form_container = new FormContainer($lang->add_multiple_reactions);
				$form_container->output_row_header($lang->image, array("class" => "align_center", 'width' => '10%'));
				$form_container->output_row_header($lang->name);
				$form_container->output_row_header($lang->include, array("class" => "align_center", 'width' => '5%'));

				foreach($myreactions as $key => $file)
				{
					$ext = get_extension($file);
					$name = ucwords(str_replace(array('-','_'), ' ', pathinfo($file, PATHINFO_FILENAME)));

					$form_container->output_cell("<img src=\"../".$path.$file."\" alt=\"\" width=\"32\" height=\"32\" /><br /><small>{$file}</small>", array("class" => "align_center", "width" => 1));
					$form_container->output_cell($form->generate_text_box("reaction_name[{$file}]", $name, array('id' => 'reaction_name', 'style' => 'width: 98%')));
					$form_container->output_cell($form->generate_check_box("include[{$file}]", 1, "", array('checked' => 1)), array("class" => "align_center"));
					$form_container->construct_row();
				}

				if($form_container->num_rows() == 0)
				{
					flash_message($lang->error_no_images, 'error');
					admin_redirect("index.php?module=forum-myreactions&action=add_multiple");
				}

				$form_container->end();

				$buttons[] = $form->generate_submit_button($lang->save_reactions);

				$form->output_submit_wrapper($buttons);
				$form->end();

				$page->output_footer();
				exit;
			}
		}
		else
		{
			$path = $mybb->input['pathfolder'];
			reset($mybb->input['include']);
			$name = $mybb->input['reaction_name'];

			if(empty($mybb->input['include']))
			{
				flash_message($lang->error_none_included, 'error');
				admin_redirect("index.php?module=forum-myreactions&action=add_multiple");
			}

			foreach($mybb->input['include'] as $image => $insert)
			{
				if($insert)
				{
					$new_reaction = array(
						"reaction_name" => $db->escape_string($name[$image]),
						"reaction_image" => $db->escape_string($path.$image)
					);

					$db->insert_query("myreactions", $new_reaction);
				}
			}

			myreactions_cache();

			// Log admin action
			log_admin_action();

			flash_message($lang->success_multiple_reactions_added, 'success');
			admin_redirect("index.php?module=forum-myreactions");
		}
	}

	$page->add_breadcrumb_item($lang->add_multiple_reactions);
	$page->output_header($lang->myreactions." - ".$lang->add_multiple_reactions);

	$sub_tabs['manage_reactions'] = array(
		'title' => $lang->manage_reactions,
		'link' => "index.php?module=forum-myreactions",
	);
	$sub_tabs['add_reaction'] = array(
		'title' => $lang->add_reaction,
		'link' => "index.php?module=forum-myreactions&amp;action=add"
	);
	$sub_tabs['add_multiple_reactions'] = array(
		'title' => $lang->add_multiple_reactions,
		'link' => "index.php?module=forum-myreactions&amp;action=add_multiple",
		'description' => $lang->add_multiple_reactions_desc
	);
	$sub_tabs['mass_edit'] = array(
		'title' => $lang->mass_edit,
		'link' => "index.php?module=forum-myreactions&amp;action=mass_edit"
	);
	$sub_tabs['import'] = array(
		'title' => $lang->import,
		'link' => "index.php?module=forum-myreactions&amp;action=import"
	);

	$page->output_nav_tabs($sub_tabs, 'add_multiple_reactions');
	$form = new Form("index.php?module=forum-myreactions&amp;action=add_multiple", "post", "add_multiple");
	echo $form->generate_hidden_field("step", "1");

	if($errors)
	{
		$page->output_inline_error($errors);
	}

	$form_container = new FormContainer($lang->add_multiple_reactions);
	$form_container->output_row($lang->path_to_images, $lang->path_to_images_desc, $form->generate_text_box('pathfolder', $mybb->input['pathfolder'], array('id' => 'pathfolder')), 'pathfolder');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->show_reactions);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "mass_edit")
{
	if($mybb->request_method == "post")
	{
		foreach($mybb->input['reaction_name'] as $reaction_id => $name)
		{
			$reaction_id = (int)$reaction_id;
			if($mybb->input['delete'][$reaction_id] == 1)
			{
				$db->delete_query("myreactions", "reaction_id = '{$reaction_id}'", 1);
			}
			else
			{
				$reaction = array(
					"reaction_name" => $db->escape_string($mybb->input['reaction_name'][$reaction_id])
				);

				$db->update_query("myreactions", $reaction, "reaction_id = '{$reaction_id}'");
			}
		}

		myreactions_cache();

		// Log admin action
		log_admin_action();

		flash_message($lang->success_multiple_reactions_updated, 'success');
		admin_redirect("index.php?module=forum-myreactions");
	}

	$page->add_breadcrumb_item($lang->mass_edit);
	$page->output_header($lang->myreactions." - ".$lang->mass_edit);

	$sub_tabs['manage_reactions'] = array(
		'title' => $lang->manage_reactions,
		'link' => "index.php?module=forum-myreactions",
	);
	$sub_tabs['add_reaction'] = array(
		'title' => $lang->add_reaction,
		'link' => "index.php?module=forum-myreactions&amp;action=add",
	);
	$sub_tabs['add_multiple_reactions'] = array(
		'title' => $lang->add_multiple_reactions,
		'link' => "index.php?module=forum-myreactions&amp;action=add_multiple",
	);
	$sub_tabs['mass_edit'] = array(
		'title' => $lang->mass_edit,
		'link' => "index.php?module=forum-myreactions&amp;action=mass_edit",
		'description' => $lang->mass_edit_desc
	);
	$sub_tabs['import'] = array(
		'title' => $lang->import,
		'link' => "index.php?module=forum-myreactions&amp;action=import"
	);

	$page->output_nav_tabs($sub_tabs, 'mass_edit');

	$form = new Form("index.php?module=forum-myreactions&amp;action=mass_edit", "post", "mass_edit");

	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$mybb->input['path'] = 'images/reactions/';
	}

	$form_container = new FormContainer($lang->manage_reactions);
	$form_container->output_row_header($lang->image, array("class" => "align_center", 'width' => '1'));
	$form_container->output_row_header($lang->name);
	$form_container->output_row_header($lang->reaction_delete, array("class" => "align_center", 'width' => '5%'));

	$query = $db->simple_select("myreactions", "*", "", array('order_by' => 'reaction_name'));
	while($reaction = $db->fetch_array($query))
	{
		if(my_strpos($reaction['reaction_image'], "p://") || substr($reaction['reaction_image'], 0, 1) == "/")
		{
			$image = $reaction['reaction_image'];
		}
		else
		{
			$image = "../".$reaction['reaction_image'];
		}

		$form_container->output_cell("<img src=\"{$image}\" alt=\"\" width=\"32\" height=\"32\" />", array("class" => "align_center", "width" => 1));
		$form_container->output_cell($form->generate_text_box("reaction_name[{$reaction['reaction_id']}]", $reaction['reaction_name'], array('id' => 'reaction_name', 'style' => 'width: 98%')));
		$form_container->output_cell($form->generate_check_box("delete[{$reaction['reaction_id']}]", 1, $mybb->input['delete']), array("class" => "align_center"));
		$form_container->construct_row();
	}

	if($form_container->num_rows() == 0)
	{
		$form_container->output_cell($lang->no_reactions, array('colspan' => 6));
		$form_container->construct_row();
	}

	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_reactions);
	$buttons[] = $form->generate_reset_button($lang->reset);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if(!$mybb->input['action'])
{
	$page->output_header($lang->manage_reactions);

	$sub_tabs['manage_reactions'] = array(
		'title' => $lang->manage_reactions,
		'link' => "index.php?module=forum-myreactions",
		'description' => $lang->manage_reactions_desc
	);
	$sub_tabs['add_reaction'] = array(
		'title' => $lang->add_reaction,
		'link' => "index.php?module=forum-myreactions&amp;action=add",
	);
	$sub_tabs['add_multiple_reactions'] = array(
		'title' => $lang->add_multiple_reactions,
		'link' => "index.php?module=forum-myreactions&amp;action=add_multiple",
	);
	$sub_tabs['mass_edit'] = array(
		'title' => $lang->mass_edit,
		'link' => "index.php?module=forum-myreactions&amp;action=mass_edit",
	);
	$sub_tabs['import'] = array(
		'title' => $lang->import,
		'link' => "index.php?module=forum-myreactions&amp;action=import"
	);

	$page->output_nav_tabs($sub_tabs, 'manage_reactions');

	$query = $db->simple_select("myreactions", "*", "", array('order_by' => 'reaction_name'));
	$reactions = '';
	$facebook_reactions = array('like','love','haha','wow','sad','angry','none');
	$facebook_reaction_emojis = array();
	foreach($facebook_reactions as $fbr)
	{
		$facebook_reaction_emojis[$fbr] = array('primary' => '', 'other' => array());
	}
	while($reaction = $db->fetch_array($query))
	{
	    $item = '<fieldset class="float_left reaction_item reaction_facebook_'.($reaction['reaction_facebook']?$reaction['reaction_facebook']:'none').'" style="width: 112px;margin: 5px"><strong style="display:inline-block;min-height:30px">'.htmlspecialchars_uni($reaction['reaction_name']).'</strong><div>';

	    if(my_strpos($reaction['reaction_image'], "p://") || substr($reaction['reaction_image'], 0, 1) == "/")
	    {
	        $image = $reaction['reaction_image'];
	    }
	    else
	    {
	        $image = "../".$reaction['reaction_image'];
	    }
	    $item .= "<img src=\"{$image}\" alt=\"\" class=\"reaction reaction_{$reaction['reaction_id']}\" style=\"padding: 2px;\" width=\"32\" height=\"32\" />";

		$item .= '</div>';

		if($reaction['reaction_facebook'])
		{
			if($reaction['reaction_facebook_primary'])
			{
				$facebook_reaction_emojis[$reaction['reaction_facebook']]['primary'] = $reaction;
			}
		}

	    $item .= '<div style="border-top:1px solid #ccc;margin-top:10px;padding-top:10px">';

		if($reaction['reaction_facebook'])
		{
			$item .= '<img src="../images/reactions/facebook_reactions/'.$reaction['reaction_facebook'].'.jpg" style="border: 2px solid #fff;border-radius: 20px" width="20" height="20" />';
		}
		else
		{
			$item .= '<span style="display:inline-block;width:24px;height:24px"></span>';
		}

		$popup = new PopupMenu("reaction_{$reaction['reaction_id']}", $lang->options);
	    $popup->add_item($lang->edit, "index.php?module=forum-myreactions&amp;action=edit&amp;reaction_id={$reaction['reaction_id']}");
	    $popup->add_item($lang->delete, "index.php?module=forum-myreactions&amp;action=delete&amp;reaction_id={$reaction['reaction_id']}&amp;my_post_key={$mybb->post_code}", "return AdminCP.deleteConfirmation(this, '{$lang->confirm_reaction_deletion}')");
	    $item .= '<div class="float_right" style="padding: 4px;">'.$popup->fetch().'</div>';

		$item .= '</div></fieldset>';

	    $reactions .= $item;
	}

	if($reactions)
	{
		$table = new Table;

		foreach($facebook_reactions as $fbr)
		{
			$table->construct_header(ucwords($fbr), array("class" => "align_center", "width" => ($fbr == 'none'?'10%':'15%'), "colspan" => ($fbr == 'none'?1:2)));
		}

		foreach($facebook_reactions as $fbr)
		{
			if($fbr == 'none')
			{
				$table->construct_cell('-', array("class" => "align_center"));
			}
			else
			{
				$table->construct_cell('<img src="../images/reactions/facebook_reactions/'.$fbr.'.jpg" style="border: 2px solid #fff;border-radius: 32px" width="32" height="32" />', array("style" => "text-align:right"));
				$table->construct_cell('<img src="../'.$facebook_reaction_emojis[$fbr]['primary']['reaction_image'].'" style="padding: 2px" width="32" height="32" />');
			}
		}
		$table->construct_row();
		foreach($facebook_reactions as $fbr)
		{
			$table->construct_cell('<a href="javascript:void(0)" data-reaction="'.$fbr.'">'.$lang->sprintf($lang->facebook_filter, ucwords($fbr)).'</a>', array("class" => "align_center", "colspan" => ($fbr == 'none'?1:2)));
		}
		$table->construct_row();
		$table->output($lang->manage_reactions);

		$table = new Table;
		$table->construct_cell($reactions);
		$table->construct_row();
		$table->output($lang->manage_reactions);

		echo "<script>
		$(function() {
			$('[data-reaction]').click(function() {
				if($(this).hasClass('active'))
				{
					$(this).removeClass('active').attr('style', '');
					$('.reaction_item').show();
				}
				else
				{
					$('[data-reaction]').removeClass('active').attr('style', '');
					reaction = $(this).data('reaction');
					$('.reaction_item').hide();
					$('.reaction_item.reaction_facebook_'+reaction).show();
					$(this).addClass('active').attr('style', 'font-weight:bold');
				}
			});
		});
		</script>";
	}
	else
	{
		$table = new Table;
		$table->construct_cell($lang->no_reactions);
		$table->construct_row();
		$table->output($lang->manage_reactions);
	}

	$page->output_footer();
}



$all_reactions = $cache->read('myreactions');
$other_plugins = array(
	'',
	'mylikes' => 'MyLikes (mylikes.php)',
	'simplelikes' => 'Like System/SimpleLikes (simplelikes.php)',
	'thankyoulike' => 'Thank You/Like System (thankyoulike.php)',
	'thx' => 'Thanks, Thanks system (thx.php)',
);
if($mybb->input['action'] == 'do_import')
{
	if($mybb->request_method == 'post')
	{
		$reaction_uids = $post_uids = array();

		switch($mybb->input['plugin'])
		{
			case 'mylikes':
				$mylikes = $cache->read('mylikes');
				foreach($mylikes as $pid => $uids)
				{
					foreach($uids as $uid)
					{
						$reaction_uids[$uid] = $uid;
						$post_info = get_post($pid);
						$post_uids[$post_info['uid']] = $post_info['uid'];

						$data = array(
							'post_reaction_pid' => $pid,
							'post_reaction_rid' => $mybb->input['reaction'],
							'post_reaction_uid' => $uid,
						);
						myreactions_process_import_insert($data);
					}
				}
				break;
			case 'simplelikes':
				$likes = $db->simple_select('post_likes');
				foreach($likes as $like)
				{
					$reaction_uids[$like['user_id']] = $like['user_id'];
					$post_info = get_post($like['post_id']);
					$post_uids[$post_info['uid']] = $post_info['uid'];

					$data = array(
						'post_reaction_pid' => $like['post_id'],
						'post_reaction_rid' => $mybb->input['reaction'],
						'post_reaction_uid' => $like['user_id'],
						'post_reaction_date' => strtotime($like['created_at']),
					);
					myreactions_process_import_insert($data);
				}
				break;
			case 'thankyoulike':
				$thankyoulikes = $db->simple_select('g33k_thankyoulike_thankyoulike');
				foreach($thankyoulikes as $thankyoulike)
				{
					$reaction_uids[$thankyoulike['uid']] = $thankyoulike['uid'];
					$post_uids[$thankyoulike['puid']] = $thankyoulike['puid'];

					$data = array(
						'post_reaction_pid' => $thankyoulike['pid'],
						'post_reaction_rid' => $mybb->input['reaction'],
						'post_reaction_uid' => $thankyoulike['uid'],
						'post_reaction_date' => $thankyoulike['dateline'],
					);
					myreactions_process_import_insert($data);
				}
				break;
			case 'thx':
				$thxs = $db->simple_select('thx');
				foreach($thxs as $thx)
				{
					$reaction_uids[$thx['adduid']] = $thx['adduid'];
					$post_uids[$thx['uid']] = $thx['uid'];

					$data = array(
						'post_reaction_pid' => $thx['pid'],
						'post_reaction_rid' => $mybb->input['reaction'],
						'post_reaction_uid' => $thx['adduid'],
						'post_reaction_date' => $thx['time'],
					);
					myreactions_process_import_insert($data);
				}
				break;
		}

		$uids = array_unique(array_merge($reaction_uids,$post_uids));
		foreach($uids as $uid)
		{
			myreactions_recount_received($uid);
			myreactions_recount_given($uid);
		}

		flash_message($lang->import_success, 'success');
		admin_redirect("index.php?module=forum-myreactions");
	}

	$page->add_breadcrumb_item($lang->import);
	$page->output_header($lang->myreactions." - ".$lang->import);

	$sub_tabs['manage_reactions'] = array(
		'title' => $lang->manage_reactions,
		'link' => "index.php?module=forum-myreactions",
		'description' => $lang->manage_reactions_desc
	);
	$sub_tabs['import'] = array(
		'title' => $lang->import,
		'link' => "index.php?module=forum-myreactions&amp;action=import",
		'description' => $lang->import_desc
	);

	$page->output_nav_tabs($sub_tabs, 'import');

	$form = new Form("index.php?module=forum-myreactions&amp;action=do_import", "post", "import");

	if($errors)
	{
		$page->output_inline_error($errors);
	}

	$form_container = new FormContainer($lang->sprintf($lang->import_from, preg_replace('/\s\([a-z\.]+\)$/', '', $other_plugins[$mybb->input['plugin']])));

	if($mybb->input['plugin'] == 'mylikes')
	{
		$form_container->output_row('', '', $lang->import_intro_mylikes);
	}

	$done_posts = $done_users = array();
	$reaction_count = 0;
	switch($mybb->input['plugin'])
	{
		case 'mylikes':
			$mylikes = $cache->read('mylikes');
			foreach($mylikes as $pid => $uids)
			{
				$done_posts[$pid] = $pid;
				foreach($uids as $uid)
				{
					$done_users[$uid] = $uid;
					$reaction_count++;
				}
			}
			break;
		case 'simplelikes':
			$likes = $db->simple_select('post_likes');
			foreach($likes as $like)
			{
				$done_posts[$like['post_id']] = $like['post_id'];
				$done_users[$like['user_id']] = $like['user_id'];
				$reaction_count++;
			}
			break;
		case 'thankyoulike':
			$thankyoulikes = $db->simple_select('g33k_thankyoulike_thankyoulike');
			foreach($thankyoulikes as $thankyoulike)
			{
				$done_posts[$thankyoulike['pid']] = $thankyoulike['pid'];
				$done_users[$thankyoulike['uid']] = $thankyoulike['uid'];
				$reaction_count++;
			}
			break;
		case 'thx':
			$thxs = $db->simple_select('thx');
			foreach($thxs as $thx)
			{
				$done_posts[$thx['pid']] = $thx['pid'];
				$done_users[$thx['adduid']] = $thx['adduid'];
				$reaction_count++;
			}
			break;
	}

	$form_container->output_row($lang->import_overview, '', $lang->sprintf($lang->import_overview_details, $reaction_count, count($done_users), count($done_posts)));

	$form_container->end();

	echo $form->generate_hidden_field('plugin', $mybb->input['plugin']);
	echo $form->generate_hidden_field('reaction', $mybb->input['reaction']);
	$buttons[] = $form->generate_submit_button($lang->import_start);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}
elseif($mybb->input['action'] == 'import')
{
	$errors = array();

	if($mybb->request_method == 'post')
	{
		if(!$mybb->input['plugin'])
		{
			$errors[] = $lang->import_error_no_plugin;
		}
		if(!$mybb->input['reaction'])
		{
			$errors[] = $lang->import_error_no_reaction;
		}
		$missing_data = false;
		switch($mybb->input['plugin'])
		{
			case 'mylikes':
				if(!$cache->read('mylikes'))
				{
					$errors[] = $lang->import_error_plugin_missing_data.' '.$lang->sprintf($lang->import_error_plugin_missing_data_cache, 'mylikes');
				}
				break;
			case 'simplelikes':
				if(!$db->table_exists('post_likes'))
				{
					$errors[] = $lang->import_error_plugin_missing_data.' '.$lang->sprintf($lang->import_error_plugin_missing_data_database, TABLE_PREFIX, 'post_likes');
				}
				break;
			case 'thankyoulike':
				if(!$db->table_exists('g33k_thankyoulike_thankyoulike'))
				{
					$errors[] = $lang->import_error_plugin_missing_data.' '.$lang->sprintf($lang->import_error_plugin_missing_data_database, TABLE_PREFIX, 'g33k_thankyoulike_thankyoulike');
				}
				break;
			case 'thx':
				if(!$db->table_exists('thx'))
				{
					$errors[] = $lang->import_error_plugin_missing_data.' '.$lang->sprintf($lang->import_error_plugin_missing_data_database, TABLE_PREFIX, 'thx');
				}
				break;
		}
		if(!$errors)
		{
			admin_redirect("index.php?module=forum-myreactions&action=do_import&plugin=".$mybb->input['plugin']."&reaction=".$mybb->input['reaction']);
		}
	}

	$page->add_breadcrumb_item($lang->import);
	$page->output_header($lang->myreactions." - ".$lang->import);

	$sub_tabs['manage_reactions'] = array(
		'title' => $lang->manage_reactions,
		'link' => "index.php?module=forum-myreactions",
		'description' => $lang->manage_reactions_desc
	);
	$sub_tabs['add_reaction'] = array(
		'title' => $lang->add_reaction,
		'link' => "index.php?module=forum-myreactions&amp;action=add",
	);
	$sub_tabs['add_multiple_reactions'] = array(
		'title' => $lang->add_multiple_reactions,
		'link' => "index.php?module=forum-myreactions&amp;action=add_multiple",
	);
	$sub_tabs['mass_edit'] = array(
		'title' => $lang->mass_edit,
		'link' => "index.php?module=forum-myreactions&amp;action=mass_edit",
	);
	$sub_tabs['import'] = array(
		'title' => $lang->import,
		'link' => "index.php?module=forum-myreactions&amp;action=import",
		'description' => $lang->import_desc
	);

	$page->output_nav_tabs($sub_tabs, 'import');

	$form = new Form("index.php?module=forum-myreactions&amp;action=import", "post", "import");

	if($errors)
	{
		$page->output_inline_error($errors);
	}

	$form_container = new FormContainer($lang->import);

	$form_container->output_row($lang->import_plugin, $lang->import_plugin_desc, $form->generate_select_box('plugin', $other_plugins, $mybb->input['plugin']), 'plugin');

	$reaction_names = array('' => '');
	$reaction_images = array();
	foreach($all_reactions as $reaction)
	{
		$reaction_names[$reaction['reaction_id']] = $reaction['reaction_name'];
		$reaction_images[] = '<img src="../'.$reaction['reaction_image'].'" data-image="'.$reaction['reaction_id'].'" width="32" height="32"'.($reaction['reaction_id'] == $mybb->input['reaction']?' class="active"':'').' />';
	}
	$form_container->output_row($lang->import_reaction, $lang->import_reaction_desc, '<div id="reaction_images">'.implode('', $reaction_images).'</div>', 'reaction');

	$form_container->output_row('', '', $lang->import_warning_desc);

	$form_container->end();

	echo $form->generate_hidden_field('reaction', $mybb->input['reaction'], array('id' => 'reaction'));
	$buttons[] = $form->generate_submit_button($lang->next);

	$form->output_submit_wrapper($buttons);
	$form->end();

	echo "<style>
	#reaction_images img {
		margin: 5px;
		opacity: 0.1;
		cursor: pointer;
	}
	#reaction_images:hover img {
		opacity: 0.25;
	}
	#reaction_images img:hover, #reaction_images img.active {
		opacity: 1;
	}
	</style>
	<script>
	$(function() {
		$('#reaction_images img').click(function() {
			$('#reaction_images img').removeClass('active');
			$(this).addClass('active');
			$('#reaction').val($(this).data('image'));
		})
	});
	</script>";

	$page->output_footer();
}

function myreactions_process_import_insert($data)
{
	global $db;

	$delete_where = array();
	foreach($data as $field => $val)
	{
		if($field == 'post_reaction_date')
		{
			continue;
		}
		$delete_where[] = $field.' = '.$val;
	}
	$db->delete_query('post_reactions', implode(' AND ', $delete_where));
	if(!array_key_exists('post_reaction_date', $data))
	{
		$data['post_reaction_date'] = TIME_NOW;
	}
	$db->insert_query('post_reactions', $data);
}
