<?php
/**
 * Plugin Name: Extend Block Example
 * Description: Example how to extend an existing Gutenberg block.
 * Author: Team Jazz, Liip AG
 * Author URI: https://liip.ch
 * Version: 1.0.0
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: extend-block-example
 * Domain Path: /languages/
 *
 * @package extend-block-example
 */


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

include_once 'api.php';
include_once 'menus.php';
include_once 'dictionary.php';

add_action('rest_api_init', function(){ 
  new Audio_API(); 
  new Dictionary_API();
});

(new Menus_API())->init(); // registrata all'interno

add_action( 'enqueue_block_editor_assets', 'extend_block_example_enqueue_block_editor_assets' );

function extend_block_example_enqueue_block_editor_assets() {
    // Enqueue our script
    wp_enqueue_script(
        'extend-block-example-js',
        esc_url( plugins_url( '/dist/extend-block-example.js', __FILE__ ) ),
        array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
        '1.0.0',
        true // Enqueue the script in the footer.
    );
}

add_filter( 'wp_insert_post_data' , 'filter_post_data' , '99', 2 );

function filter_post_data( $data , $postarr ) {
    // Change post title
    $data['post_content'] = str_replace('&nbsp;', ' ', $data['post_content']);
    return $data;
}


//add_filter( 'render_block', 'split_paragraphs', 10, 2);
/*
global $counter;
$counter = 0;

function split_paragraphs($content, $block) {

  if($block['blockName'] !== 'core/paragraph') {
    return $content;
  }
  $paragraph = $content;

  global $counter;

  $parts = [];
  $parts = explode(' ', $paragraph);

  $new_paragraph = '<span id="'.$counter.'" class="piece">&nbsp;</span>';
  foreach($parts as $part) {
    $counter++;

    $new_paragraph .= $part . '<span id="'.$counter.'" class="piece">&nbsp;</span>';
  }
  return $new_paragraph;  
}*/