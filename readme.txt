Name: MyReactions
Description: Add emoji reactions to posts
Website: https://github.com/MattRogowski/MyReactions
Author: Matt Rogowski
Authorsite: https://matt.rogow.ski
Version: 0.0.1
Compatibility: 1.8.x
Files: 5
Templates added: 8
Template changes: 4
Settings added: 4
Tables added: 2

Information:
This plugin allows you to add emoji reactions to user's posts.

Choose whether to gorup the reactions and display a count, or display them in a linear order of when they were received.

You can also view the most given and received reactions on user profiles.

The idea was originally inspired by https://facepunch.com/ (who I did ask permission from to build this several years ago) and more recently Slack

To Install:
Upload ./inc/plugins/myreactions.php to ./inc/plugins/
Upload ./inc/languages/english/myreactions.lang.php to ./inc/languages/english/
Upload ./admin/modules/forum/myreactions.php to ./admin/modules/forum/
Upload ./inc/languages/english/admin/forum_myreactions.lang.php to ./inc/languages/english/admin/
Upload ./jscripts/myreactions.js to ./jscripts/
Go to ACP > Templates & Style > **choose theme** > Add Stylesheet > enter 'myreactions.css' (without quotes) into 'File Name', select 'Write my own content', paste the content of ./myreactions.css into the editor, and save.
Go to ACP > Plugins > Activate
Go to ACP > Forums & Posts > MyReactions to manage

Change Log:
27/11/16 - v0.0.1 -> Initial 'beta' release.

Copyright 2016 Matthew Rogowski

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at

 ** http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.