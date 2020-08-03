<?php
/*
Plugin Name: Treasure Island
Plugin URI:  https://github.com/alegue/WordPress-Plugin-TreasureIsland
Description: AudioBook English-Italian with Dictionary
Version:     0.0.2
Author:      Alessio Guerriero
Author URI:  http://
*/

//namespace TreasureIsland; // vedi sotto a FIX


if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

define( 'WPPS_NAME'                 ,'TreasureIsland' );
define( 'TREASUREISLAND_DOMAIN'     ,'treasure-island' );
define( 'TREASUREISLAND_PLUGIN_PATH', WP_PLUGIN_DIR . '/' . TREASUREISLAND_DOMAIN . '/' );
define( 'API_PATH', TREASUREISLAND_PLUGIN_PATH.'api/');

include (TREASUREISLAND_PLUGIN_PATH.'/frontend/frontend-init.php'); 
                    // con namespace l'inclusione della classe Frontend_Init() non avviene: FIX


include_once API_PATH.'api.php';
include_once API_PATH.'menus.php';
include_once API_PATH.'dictionary.php';

function TreasureIsland_frontend_init() {
	new \TreasureIsland\Frontend\Frontend_Init();
}

if ( !is_admin() ) {
	add_action( 'plugins_loaded', 'TreasureIsland_frontend_init' );
}


add_action('rest_api_init', function(){ 
  new Audio_API(); 
  new Dictionary_API();

  // add_categories_extra_to_post();
});

(new Menus_API())->init(); // registrata all'interno


/*
 * add categories name and id in post response 
 */
function add_categories_extra_to_post(){
  register_rest_field('post', 'categories_extra', array(
    'get_callback' => function($post){
      $cats = get_the_category($post['id']);
      $data = array();
      if(!empty($cats)){
        foreach($cats as $cat){
          array_push($data, array(
            'name' => $cat->name,
            'id' => $cat->term_id,
            'category_parent' => $cat->category_parent,
            'description' => $cat->description,
            'slug' => $cat->slug
          ));
        }
      }
      return $data;
    }
  ));
}

add_filter( 'wp_insert_post_data' , 'filter_post_data' , '99', 2 );

function filter_post_data( $data , $postarr ) {
    // filter &nbsp;
    $data['post_content'] = str_replace('&nbsp;', ' ', $data['post_content']);
    $data['post_content'] = str_replace('—', ' — ', $data['post_content']); // for better separation of sounds
    return $data;
}








