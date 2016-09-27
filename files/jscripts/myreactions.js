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
		$.post('misc.php?action=myreactions_react&ajax=1&rid='+rid+'&pid='+pid, function(resp) {
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
		$.post('misc.php?action=myreactions_remove&ajax=1&rid='+rid+'&pid='+pid, function(resp) {
			$('#post_'+pid).find('.myreactions-container').remove();
			$('#post_'+pid).find('.post_controls').before(resp);
			$.modal.close();
		});
	}
};

MyReactions.init();