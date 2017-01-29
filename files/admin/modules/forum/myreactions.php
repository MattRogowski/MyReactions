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

	$page->output_nav_tabs($sub_tabs, 'manage_reactions');

	$pagenum = $mybb->get_input('page', MyBB::INPUT_INT);
	if($pagenum)
	{
		$start = ($pagenum-1) * 20;
	}
	else
	{
		$start = 0;
		$pagenum = 1;
	}


	$table = new Table;
	$table->construct_header($lang->image, array("class" => "align_center", "width" => 1));
	$table->construct_header($lang->name, array("width" => "35%"));
	$table->construct_header($lang->controls, array("class" => "align_center", "colspan" => 2));

	$query = $db->simple_select("myreactions", "*", "", array('limit_start' => $start, 'limit' => 20, 'order_by' => 'reaction_name'));
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

		$images = array();
		foreach(array(16,20,24,28,32) as $size)
		{
			$images[] = "<img src=\"{$image}\" alt=\"\" class=\"reaction reaction_{$reaction['reaction_id']}\" width=\"".$size."\" height=\"".$size."\" />";
		}

		$table->construct_cell(implode(' ', $images), array("class" => "align_center", "width" => "20%"));
		$table->construct_cell(htmlspecialchars_uni($reaction['reaction_name']), array("width" => "60%"));

		$table->construct_cell("<a href=\"index.php?module=forum-myreactions&amp;action=edit&amp;reaction_id={$reaction['reaction_id']}\">{$lang->edit}</a>", array("class" => "align_center", "width" => "10%"));
		$table->construct_cell("<a href=\"index.php?module=forum-myreactions&amp;action=delete&amp;reaction_id={$reaction['reaction_id']}&amp;my_post_key={$mybb->post_code}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->confirm_reaction_deletion}')\">{$lang->delete}</a>", array("class" => "align_center", "width" => "10%"));
		$table->construct_row();
	}

	if($table->num_rows() == 0)
	{
		$table->construct_cell($lang->no_reactions, array('colspan' => 5));
		$table->construct_row();
	}

	$table->output($lang->manage_reactions);

	$query = $db->simple_select("myreactions", "COUNT(reaction_id) as myreactions");
	$total_rows = $db->fetch_field($query, "myreactions");

	echo "<br />".draw_admin_pagination($pagenum, "20", $total_rows, "index.php?module=forum-myreactions&amp;page={page}");

	$page->output_footer();
}
