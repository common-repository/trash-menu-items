<?php
/*
Plugin Name: Trash Menu Items
Plugin URI: https://wordpress.org/plugins/trash-menu-items/
Description: This plugin will let you enable trash for menu items, so that you can restore your custom links
Version: 1.0
Author: Narayan - Gyrix TechnoLabs LLP
Author URI: https://gyrix.co
License: GPLv2 or later
Text Domain: trash-menu-items
*/

if ( !defined('ABSPATH') ) {
    exit; // Exit if accessed directly
}

class TMI_Trash_Menu_Items {

	public static function tmi_init() {
	    if ( is_admin() ) {
	    	require_once( plugin_dir_path(__FILE__) . 'tmi-walker-nav-menu-edit.php' );
			add_filter( 'wp_edit_nav_menu_walker', 'TMI_Trash_Menu_Items::tmi_custom_walker' );

			add_action( 'admin_init',               'TMI_Trash_Menu_Items::tmi_add_meta_box' );
			add_action( 'admin_enqueue_scripts',    'TMI_Trash_Menu_Items::tmi_include_js' );
			add_action( 'admin_enqueue_scripts',    'TMI_Trash_Menu_Items::tmi_include_css' );
			add_action( 'wp_ajax_trash_menu_items', 'TMI_Trash_Menu_Items::tmi_trash_menu_item' );
			add_action( 'wp_ajax_delete_menu_items','TMI_Trash_Menu_Items::tmi_delete_menu_items' );
		}
	}

	public static function tmi_custom_walker() { // return custom walker class
       	return 'TMI_Walker_Nav_Menu_Edit';
  	}

  	public static function tmi_add_meta_box() { // Add metabox on nav-menus.php
    	add_meta_box( 'post_types_meta_box', __( 'Trash' ), 'TMI_Trash_Menu_Items::tmi_render_meta_box', 'nav-menus', 'side', 'low' );
	}

	public static function tmi_render_meta_box() { // Show all nav menu items whose status is trash
    	$trash_posts = get_posts(array(
    		'post_type' => 'nav_menu_item',
    		'post_status' => 'trash'
    	) );

	    ?>
		    <div id="posttype-nav_menu_item_trash" class="posttypediv" >
		        <ul class="posttype-tabs add-menu-item-tabs" >
		            <li class="tabs"><?php _e( 'Items' ); ?></li>
		        </ul>
		        <div class="tabs-panel tabs-panel-active">
		            <ul class="categorychecklist form-no-clear">
		            <?php $i = 0; foreach ($trash_posts  as $trash_post ) : $i++; ?>
	            		<?php 

							$menu_item_db_id = $trash_post->ID;
							$menu_item_object_id = get_post_meta( $menu_item_db_id, '_menu_item_object_id', true);
							$menu_item_object = get_post_meta( $menu_item_db_id,'_menu_item_object',true);
							$menu_item_type = get_post_meta( $menu_item_db_id, '_menu_item_type', true);
							$menu_item_title = $trash_post->post_title;
							if( $menu_item_title === '' ) {
					        	$object = get_post( $menu_item_object_id );
					    		$menu_item_title = $object->post_title;

					    		// for taxonomy, title of the menu is different
								if( $menu_item_type === 'taxonomy' ) {	
									$term = get_term( $menu_item_object_id, 'category' );
									$menu_item_title = $term->name;
								}
							}
							$menu_item_url = get_post_meta( $menu_item_db_id, '_menu_item_url', true);
							if ( $menu_item_url === '' ) {
	            				$menu_item_url = get_permalink( $menu_item_db_id );
	            			}
	            		?>
		                <li>
		                    <label class="menu-item-title">
		                    	<input type="checkbox" class="menu-item-checkbox" name="menu-item[-<?php echo $i; ?>][menu-item-object-id]" value="<?php echo $menu_item_object_id; ?>"> <?php echo $menu_item_title; ?>
		                    </label>
		                    <input type="hidden" class="menu-item-db-id" name="menu-item[-<?php echo $i; ?>][menu-item-db-id]" value="<?php echo $menu_item_db_id; ?>">
		                    <input type="hidden" class="menu-item-object" name="menu-item[-<?php echo $i; ?>][menu-item-object]" value="<?php echo $menu_item_object; ?>">
		                    <input type="hidden" class="menu-item-title" name="menu-item[-<?php echo $i; ?>][menu-item-title]" value="<?php echo $menu_item_title; ?>">
		                    <input type="hidden" class="menu-item-url" name="menu-item[-<?php echo $i; ?>][menu-item-url]" value="<?php echo $menu_item_url; ?>">
		                    <input type="hidden" value="<?php echo $menu_item_type; ?>" name="menu-item[-<?php echo $i; ?>][menu-item-type]">
		                    <span class="rb-delete-permanent" > delete </span>
		                    <input type="hidden" id="menu-item-security-<?php echo $menu_item_db_id; ?>" value="<?php echo wp_create_nonce( $menu_item_db_id ); ?>">
		                </li>
		            <?php endforeach; ?>
		            </ul>
		        </div>
		        <p class="button-controls" >
		            <span class="list-controls" >
		                <a href="/wp-admin/nav-menus.php?page-tab=all&amp;selectall=1#posttype-nav_menu_item_trash" class="select-all">Select All</a>
		            </span>

		            <span class="add-to-menu">
		                <input type="submit" class="button-secondary submit-add-to-menu right" value="Add to Menu" name="add-post-type-menu-item" id="submit-posttype-nav_menu_item_trash">
		                <span class="spinner"></span>
		            </span>
		        </p>
		    </div>
	    <?php
	}

	public static function tmi_include_js() {
 		wp_enqueue_script('trash-menu-items-js', plugins_url('/assets/trash-menu-items.js', __FILE__), array('jquery'));
	}

	public static function tmi_include_css() {
 		wp_enqueue_style('trash-menu-items-css', plugins_url('/assets/trash-menu-items.css', __FILE__));
	}

	public static function tmi_trash_menu_item() { // called when clicked on trash button in menu item
		// Set post status to trash
		if( isset( $_POST['menu_item_id'] ) &&  isset( $_POST['menu_item_title'] ) ) {

			global $wpdb;
			$table = $wpdb->prefix.'posts';
			$ID = $_POST['menu_item_id'];
			$title = $_POST['menu_item_title'];// if post is draft save this title in the post

			check_ajax_referer( (int) $ID, 'security' );
			if ( get_post_status ( $ID ) == 'draft' ) {
				$my_post = array(
					'ID'           => $ID,
					'post_title'   => $title
				);
				wp_update_post( $my_post );
		    }


			$query = 'UPDATE '.$table.' SET post_status = %s WHERE ID = %d';
			$wpdb->query( $wpdb->prepare( $query, 'trash', $ID ) );

			// get posts whose type is nav_menu_item and post status is trash
		    $trash_posts = get_posts(array(
		    	'post_type' => 'nav_menu_item',
		    	'post_status' => 'trash'
		    ) );

			$i = 0;
			foreach ($trash_posts  as $trash_post ) { 
				$i++;

				$menu_item_db_id = $trash_post->ID;
				$menu_item_object_id = get_post_meta( $menu_item_db_id, '_menu_item_object_id', true);
				$menu_item_object = get_post_meta( $menu_item_db_id,'_menu_item_object',true);
				$menu_item_type = get_post_meta( $menu_item_db_id, '_menu_item_type', true);
				$menu_item_title = $trash_post->post_title;
				if( $menu_item_title === '' ) {
		        	$object = get_post( $menu_item_object_id );
		    		$menu_item_title = $object->post_title;

		    		// for taxonomy, title of the menu is different
					if( $menu_item_type === 'taxonomy' ) {	
						$term = get_term( $menu_item_object_id, 'category' );
						$menu_item_title = $term->name;
					}
				}
				$menu_item_url = get_post_meta( $menu_item_db_id, '_menu_item_url', true);
				if ( $menu_item_url === '' ) {
    				$menu_item_url = get_permalink( $menu_item_db_id );
    			}
	    		
	    		echo '
	            <li>
	                <label class="menu-item-title"><input type="checkbox" class="menu-item-checkbox" name="menu-item[-'.$i.'][menu-item-object-id]" value="'.$menu_item_object_id.'">'.$menu_item_title.'</label>
	                <input type="hidden" class="menu-item-db-id" name="menu-item[-'.$i.'][menu-item-db-id]" value="'.$menu_item_db_id.'">
	                <input type="hidden" class="menu-item-object" name="menu-item[-'.$i.'][menu-item-object]" value="'.$menu_item_object.'">
	                <input type="hidden" class="menu-item-title" name="menu-item[-'.$i.'][menu-item-title]" value="'.$menu_item_title.'">
	                <input type="hidden" class="menu-item-url" name="menu-item[-'.$i.'][menu-item-url]" value="'.$menu_item_url.'">
	                <input type="hidden" value="'.$menu_item_type.'" name="menu-item[-'.$i.'][menu-item-type]">
	                <span class="rb-delete-permanent" > delete </span>
	                <input type="hidden" id="menu-item-security-'.$menu_item_db_id.'" value="'.wp_create_nonce( $menu_item_db_id ).'">
	            </li>';
	        }
		    wp_die();
		}
	}

	public static function tmi_delete_menu_items() { // called when clicked on delete button in trash menu meta box
		if ( isset( $_POST['menu_item_id'] ) ) {
			if( current_user_can('administrator') ) {
				$ID = $_POST['menu_item_id'];
				check_ajax_referer( (int) $ID, 'security' );
				wp_delete_post( $ID, true );
			    wp_die();
			}
		}
	}
}

/* ------------------------------------------------
	Run the plugin
------------------------------------------------ */
add_action('plugins_loaded', 'TMI_Trash_Menu_Items::tmi_init');