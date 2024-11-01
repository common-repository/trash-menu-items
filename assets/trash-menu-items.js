jQuery(function($) {

	//Remove menu from database
	$("#menu-to-edit").on('click', '.trash', function() {
    	link = $(this);
		el = link.parent().parent();	
		var menu_item_id =  el.find('.menu-item-data-db-id').val();
		var menu_item_title = jQuery("#edit-menu-item-title-" + menu_item_id).val();
		var security = jQuery("#menu-item-s-" + menu_item_id).val();
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
		        action : 'trash_menu_items',
		        menu_item_id : menu_item_id,
		        menu_item_title  : menu_item_title,
		        security : security
	    	},
			
			success: function(data){
				// Reload trash menus in the metabox
				jQuery('#posttype-nav_menu_item_trash .categorychecklist').html(data);
				// Remove menu item from menu
				menu_item = el.parent().remove();
			}
		});	
	});

	// Remove menu permanently
	$(".rb-delete-permanent").live('click',function(e){
    	e.preventDefault();

    	link = $(this);
		el = link.parent();
		var menu_item_id =  el.find('.menu-item-db-id').val();
		var security = jQuery("#menu-item-security-" + menu_item_id).val();
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
		        action : 'delete_menu_items',
		        menu_item_id : menu_item_id,
		        security : security
	    	},
			
			success: function(data){
				// Remove menu item from link
				menu_item = el.remove();
			}
		});

	});

});

