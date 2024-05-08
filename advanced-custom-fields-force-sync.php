<?php
 /*
 Plugin Name: Advanced Custom Fields Force Sync
 Plugin URI:  
 Description: Force Advanced Custom Fields Field Groups to sync from their json files.
 Version: 1.0.1
 Author:      Chris Bellew
 License:     GPL2
 License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class ACF_Force_Sync {
	public function __construct() {
		$this->initialize();
	}

	public function initialize() {
		add_filter('page_row_actions', array($this, 'custom_actions'), 10, 2);
		add_action('admin_action_acf_force_sync', array($this, 'do_force_sync'));
		add_action('admin_notices', array( $this, 'maybe_display_notice' ));
		add_action('admin_enqueue_scripts', [$this, 'init_scripts']);
		add_action('wp_ajax_acf_force_sync', [$this, 'do_force_sync']);
	}

	public function custom_actions( $actions, $post ) {
		if('acf-field-group' === $post->post_type) {
			$url = $this->create_link($post); //admin_url( 'admin.php?page=acf-force-sync&post=' . $post->ID );
			$actions['force-sync'] = sprintf('<a data-post="%d" href="%s">%s</a>', $post->ID, $url, __('Force Sync', 'ACF_Force_Sync'));
		}
		return $actions;
	}

	public function init_scripts() {
		wp_enqueue_script( 'acf-force-sync-js', plugins_url( 'advanced-custom-fields-force-sync.js', __FILE__ ));
	}
	
	public function create_link($post) {
	  return wp_nonce_url(admin_url("admin.php?action=acf_force_sync&post=".$post->ID), 'revisionize-create-'.$post->ID);
	}
	
	public function do_force_sync() {
		$id = intval($_REQUEST['post']);
		
		$result = new stdClass();

		$all_field_groups = acf_get_field_groups();
		$key = false;
		foreach($all_field_groups as $fg) {
			if($id === $fg['ID']) {
				$key = $fg['key'];
				break;
			}
		}
		if(false === $key) {
			$result->success = false;
			$result->error = 'Unable to find the specified field group.';
		} else {
			$files = acf_get_local_json_files();
			$local_field_group = json_decode( file_get_contents( $files[ $key ] ), true );
			$local_field_group['ID'] = $id;
			$r = acf_import_field_group( $local_field_group );
			$result->success = true;
		}
		echo json_encode($result);
		exit;
	}
	
	public function set_notice($notice) {
    update_option( 'acf_force_sync_notice', $notice, 'no' );
	}
	
	public function maybe_display_notice(){
	  $notice = get_option( 'acf_force_sync_notice', false );
	  if( $notice ){
	    delete_option( 'acf_force_sync_notice' );
	    $this->display_notice( $notice );
	  }
	}
	
	public function display_notice($notice) {
		echo '<div class="error"><p>' . $notice . '</p></div>';
	}
}

$acf_force_sync = new ACF_Force_Sync();
