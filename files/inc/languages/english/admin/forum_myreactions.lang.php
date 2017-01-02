<?php
/**
 * MyReactions 0.0.3 - Admin Language File

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
**/

$l['myreactions'] = 'MyReactions';
$l['can_manage_myreactions'] = 'Can manage MyReactions?';

$l['manage_reactions'] = "Manage MyReactions";
$l['manage_reactions_desc'] = "This section allows you to edit and delete your reactions.";
$l['add_reaction'] = "Add New Reaction";
$l['add_reaction_desc'] = "Here you can add a single new reaction.";
$l['add_multiple_reactions'] = "Add Multiple Reactions";
$l['add_multiple_reactions_desc'] = "Here you can add multiple new reactions at once.";
$l['edit_reaction'] = "Edit Reaction";
$l['edit_reaction_desc'] = "Here you can edit a single reaction.";
$l['mass_edit'] = "Mass Edit";
$l['mass_edit_desc'] = "Here you can easily edit all your reactions in one go.";

$l['no_reactions'] = "There are no reactions on your forum at this time.";

$l['image'] = "Image";
$l['name'] = "Name";
$l['image_path'] = "Image Path";
$l['image_path_desc'] = "This is the path to the reaction image.";
$l['include'] = "Add?";
$l['path_to_images'] = "Path to Images";
$l['path_to_images_desc'] = "This is the path to the folder that the images are in.";
$l['reaction_delete'] = "Delete?";
$l['save_reaction'] = "Save Reaction";
$l['save_reactions'] = "Save Reactions";
$l['show_reactions'] = "Show Reactions";
$l['reset'] = "Reset";

$l['error_missing_name'] = "You did not enter a name for this reaction.";
$l['error_missing_path'] = "You did not enter a path for this reaction.";
$l['error_missing_path_multiple'] = "You did not enter a path.";
$l['error_no_reactions'] = "There are no reactions in the specified directory, or all reactions in the directory have already been added.";
$l['error_no_images'] = "There are no images in the specified directory.";
$l['error_none_included'] = "You did not select any reactions to include.";
$l['error_invalid_path'] = "You did not enter a valid path.";
$l['error_invalid_reaction'] = "The specified reaction does not exist.";

$l['success_reaction_added'] = "The reaction has been added successfully.";
$l['success_multiple_reactions_added'] = "The selected reactions have been added successfully.";
$l['success_reaction_updated'] = "The reaction has been updated successfully.";
$l['success_multiple_reactions_updated'] = "The reactions have been updated successfully.";
$l['success_reaction_deleted'] = "The selected reaction has been deleted successfully.";
$l['success_mass_edit_updated'] = "The reactions have been updated successfully.";

$l['confirm_reaction_deletion'] = "Are you sure you wish to delete this reaction?";



$l['import'] = 'Import Data';
$l['import_from'] = 'Import Data from {1}';
$l['import_reactions_data'] = 'Import MyReactions Data';
$l['import_desc'] = 'Import data from other \'thank you\' or \'like\' plugins into MyReactions';
$l['import_plugin'] = 'Source Plugin';
$l['import_plugin_desc'] = 'Select the plugin you would like to import data from';
$l['import_reaction'] = 'Reaction';
$l['import_reaction_desc'] = 'Click a reaction to convert likes/thanks to. For every like/thanks a post has received, it will be replaced with this reaction';
$l['import_warning_desc'] = '<strong>Note:</strong> You can re-run the import multiple times for the same plugin, as long as you choose the same reaction to import to. If you re-run the import and select a different reaction, the original reaction you imported will <strong>not</strong> be deleted.<br /><br />If you have already had MyReactions running before running the import, any posts that have already been given the reaction that you are choosing to import to will <strong>not</strong> be given an additional reaction.<br /><br />Once the importer has run, there is no ability to undo the import. After running the import, make sure you are happy that reactions have been added to posts as expected before uninstalling the old plugin.';
$l['import_start'] = 'Start Import';
$l['import_error_no_plugin'] = 'Please select which plugin you are importing from';
$l['import_error_no_reaction'] = 'Please select a reaction to import to';
$l['import_error_plugin_missing_data'] = 'Data cannot be imported from this plugin. Reason:';
$l['import_error_plugin_missing_data_cache'] = 'The <strong>{1}</strong> cache object seems to be missing';
$l['import_error_plugin_missing_data_database'] = 'The table <strong>{1}{2}</strong> seems to be missing';
$l['import_intro_mylikes'] = '<strong>Note:</strong> When using the MyLikes plugin, a \'like\' on a post adds a reputation to the post author; in other words, it uses the default MyBB reputation system, and doesn\'t add a separate \'like\' to the post. If you still want to add a reaction to every post that has been liked, the reputation that has already been given will <strong>not</strong> be removed.';
$l['import_overview'] = 'Import Overview';
$l['import_overview_details'] = 'You are going to import <strong>{1}</strong> reactions from <strong>{2}</strong> users into <strong>{3}</strong> posts';
$l['import_success'] = 'The import has completed successfully.';
