var MyReactions = {
	init: function()
	{
		$(document).ready(function(){
		});
	},

	reactions: function(pid)
	{
		MyBB.popupWindow('/misc.php?action=myreactions&pid='+pid);
	},
	react: function(rid, pid)
	{
		$.post('misc.php?action=myreactions_react&ajax=1&rid='+rid+'&pid='+pid+'&my_post_key='+my_post_key, function(resp) {
			if(resp.errors)
			{
				alert(resp.errors[0]);
				return false;
			}
			$('#post_'+pid).find('.myreactions-container').remove();
			$('#post_'+pid).find('.post_controls').before(resp);
			$.modal.close();
		});
	},
	remove: function(rid, pid) {
		$.post('misc.php?action=myreactions_remove&ajax=1&rid='+rid+'&pid='+pid+'&my_post_key='+my_post_key, function(resp) {
			if(resp.errors)
			{
				alert(resp.errors[0]);
				return false;
			}
			$('#post_'+pid).find('.myreactions-container').remove();
			$('#post_'+pid).find('.post_controls').before(resp);
			$.modal.close();
		});
	},
	reactedPost: function(pid)
	{
		MyBB.popupWindow('/misc.php?action=myreactions_reacted&pid='+pid);
	},
	reactedUser: function(uid)
	{
		MyBB.popupWindow('/misc.php?action=myreactions_reacted&uid='+uid);
	},
	reactedView: function()
	{
		$('.myreactions_reacted_row').addClass('myreactions_reacted_row_hidden');
		$('.myreactions_reacted_row.myreactions_reacted_row_'+$('input[name="myreactions_reacted_display"]:checked').val()).removeClass('myreactions_reacted_row_hidden');
	}
};

MyReactions.init();