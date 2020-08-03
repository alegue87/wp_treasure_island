<?php

/**
 *  Class that sets up the rest routes for the app
 */
class Audio_API
{

  public $prefix = 'audio';

  function __construct(){
    $this->register_routes();
  }

  /**
   * Registers all the plugins rest routes
   */
  public function register_routes() {

    register_rest_route( $this->prefix, '/list', [
      'methods' => 'GET',
      'callback' => [ $this, 'get_audio' ]
    ]);

    register_rest_route( $this->prefix, '/set', [ // da cambiare in /references
      'methods' => 'POST',
      'callback' => [ $this, 'set_references']
    ]);

    register_rest_route( $this->prefix, '/references', [
      'methods' => 'GET',
      'callback' => [ $this, 'get_references' ]
    ]);
  }

  public function get_references( \WP_REST_Request $request ) {
    $post_id = $request['post_id'];
    global $wpdb;
    $results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}termmeta WHERE meta_value LIKE '{$post_id}-%'", OBJECT );

    $data = json_decode(json_encode($results), true);
    $references = [];
    foreach($data as $item) {
      $post = get_post($item['term_id']);
      array_push($references, array(
        'reference' => $item['meta_value'],
        'source' => $post->guid,
        'name' => $this->get_audio_filename($post->guid)
      ));
        // TODO: andrebbero sortati per 'name' ed eliminati i doppi
    }
    return new \WP_REST_Response(array($post_id => $references));
  }

  /**
   * @param WP_REST_Request $request
   *
   * @return WP_Error|WP_REST_Response
   */
  public function set_references( \WP_REST_Request $request ) {
    // audio type attachment
    $post_id =  $request['post_id'];
    $reference = $request['reference'];
    $post_parent = $request['post_parent'];

    $old_reference = get_term_meta($post_id, 'reference', true);
    if( $old_reference === '' ) {
      add_term_meta($post_id, 'reference', $reference); // post_id-paragraph-piece
    }
    else {
      update_term_meta($post_id, 'reference', $reference);
    }

    wp_update_post(array( // assegna attachemnt a post ( eng )
      'ID' => $post_id,
      'post_parent' => $post_parent
    ));

    $reference = get_term_meta($post_id, 'reference', true);
    
    if( !empty($reference) ) {
      $response = new \WP_REST_Response(array('reference' => $reference));
      $response->set_status(200);
      return $response;
    }
    else {
      return new \WP_Error();
    }
  }

  /**
   * @param WP_REST_Request $request
   *
   * @return WP_Error|WP_REST_Response
   */
  public function get_audio( \WP_REST_Request $request ) {
    //$post_id    = url_to_postid( $request['url'] );
    //$post_type  = get_post_type( $post_id );
    //$controller = new WP_REST_Posts_Controller( 'attachment' );
    //request    = new WP_REST_Request( 'GET', "/wp/v2/{$post_type}s/$post_id" );
    //$request->set_url_params( array( 'id' => $post_id ) );

    // Salta i file audio già assegnati per l'inserimento
    // di altri non attribuiti ad un nuovo capitolo in editing
    wp_reset_postdata();

    if(isset($request['post_parent'])) {
      $post_parent = $request['post_parent'];
    }
    else {
      $post_parent = 0;
    }
    $args = array(
      'post_type'   => 'attachment',
      'post_status' => 'any',
      'post_mime_type' => 'audio/mpeg',
      'nopaging'    => true,
      'post_parent' => $post_parent
    );

    if( isset($request['showAlsoUnassigned']) ) {
      unset($args['post_parent']);
      $args['post_parent__in'] = array($post_parent, 0);
    }

    $query = new WP_Query($args);

    // se non trova assegnati prende gli altri
    if( !$query->have_posts() ) {
      $args['post_parent'] = 0;
      $query = new WP_Query($args);
    }

    $audio_list = array();
    while($query->have_posts()){
      $query->the_post();
      $post = $query->post;
      $reference = get_term_meta($post->ID, 'reference', true);

      array_push($audio_list, 
        array(
          'id' => $post->ID,
          'name' => $this->get_audio_filename($post->guid),
          'source' => $post->guid,
          'reference' => $reference,
          'post_parent' => $post->post_parent
        )
      );
    }

      $response = new WP_REST_Response($audio_list);
      $response->set_status(200);
      return $response;
    }

  /**
   * Get a audio file name
   * 
   * @param String $guid
   * @return String $filename
   * 
   */  
  public function get_audio_filename($guid){
    return 'a-' . explode('a-', $guid)[1];
  }

  /**
   * Exports the app manifest
   */
  public function export_manifest() {

    $site_name = get_bloginfo('name');

    // add basic settings to manifest
    $arr_manifest = [
      'name' => $site_name,
      'short_name' => $site_name,
      'start_url' => home_url(),
      'display' => 'standalone',
      'orientation' => 'any',
      'theme_color' => '#a333c8',
      'background_color' => '#a333c8',
    ];

    // add paths to all icon sizes if there is an icon uploaded
    $options = new Options();
    $icon = $options->get_setting( 'icon' );

    if ( $icon != '' ) {

      $base_path = $icon;
      $arr_manifest['icons'] = [];
      $uploads = new Uploads();

      foreach ( Uploads::$manifest_sizes as $manifest_size  ) {

        $icon_path = $uploads->get_file_url( $manifest_size . $base_path );

        if ( $icon_path != '' ) {

          $arr_manifest['icons'][] = [
            "src" => $icon_path,
            "sizes" => $manifest_size . 'x' . $manifest_size,
            "type" => "image/png"
          ];
        }
      }
    }

    echo wp_json_encode( $arr_manifest );
    exit();
  }

  /**
   * Returns a json response with 100 categories
   */
  public function view_categories( \WP_REST_Request $request ) {

    $woocommerce = $this->get_client();

    $filters = [];

    if ( isset( $request['page'] ) && is_numeric( $request['page'] ) )  {
      $filters['page'] = $request['page'];
    }

    if ( isset( $request['per_page'] ) && is_numeric( $request['per_page'] ) )  {
      $filters['per_page'] = $request['per_page'];
    }

    if ( isset( $request['hide_empty'] ) )  {
      $filters['hide_empty'] = true;
    }

    $woocommerce = $this->get_client();

    return $woocommerce->get( 'products/categories', $filters);
  }

  /**
   * Returns a json response with the info of the latest 100 products in a category if the request has a categId param
   * if the categoryId param is missing it returns a json with the info of the latest 100 featured products, if there are none
   * it returns the info of the last 10 products added to the site
   */
  public function view_products( \WP_REST_Request $request ) {
    $woocommerce = $this->get_client();

    $filters = [];

    if ( isset( $request['page'] ) && is_numeric( $request['page'] ) )  {
      $filters['page'] = $request['page'];
    }

    if ( isset( $request['per_page'] ) && is_numeric( $request['per_page'] ) )  {
      $filters['per_page'] = $request['per_page'];
    }

    if ( isset( $request['category'] ) && is_numeric( $request['category'] ) )  {
      $filters['category'] = $request['category'];
    }

    if ( isset( $request['search'] ) )  {
      $filters['search'] = filter_var( $request['search'], FILTER_SANITIZE_STRING );
    }

    if ( isset( $request['orderby'] ) && in_array( $request['orderby'], ['id', 'date', 'title', 'include', 'slug'] ) )  {
      $filters['orderby'] = $request['orderby'];
    }

    if ( isset( $request['order'] ) && in_array( $request['order'], ['asc', 'desc'] ) )  {
      $filters['order'] = $request['order'];
    }

    if ( isset( $request['featured'] ) )  {
      $featured_products = $woocommerce->get( 'products', array_merge([ 'featured' => true ], $filters) );

      if ( !empty( $featured_products ) ) {
        return $featured_products;
      }
    }

    return $woocommerce->get( 'products', $filters );
  }


  /**
   * Returns a json response with a product based on its id
   */
  public function view_product( \WP_REST_Request $request ){

    $woocommerce = $this->get_client();
    $id = $request->get_param( 'id' );

    return $woocommerce->get( "products/$id" );
  }

  /**
   * Returns a json response with the latest 100 reviews of product based on its id
   */
  public function view_reviews( \WP_REST_Request $request ) {

    $woocommerce = $this->get_client();
    $id = $request->get_param( 'id' );

    return $woocommerce->get( "products/$id/reviews", [ 'per_page' => 100, 'orderby' => 'date' ] );
  }

  /**
   * Returns a json response with the variations of product based on its id
   */
  public function view_product_variations( \WP_REST_Request $request ) {

    $woocommerce = $this->get_client();
    $id = $request->get_param( 'id' );

    $variations =  $woocommerce->get( "products/$id/variations", [ 'per_page' => 100 ] );
    
    $filters = array();
    if( isset($request['product']) && is_numeric($request['product']) ) {
      $filters['product'] = $request['product'];
    }

    $attributeIds = [];
    $attributeNames = [];
    $attributesTerms = [];
    // Hence the following line will convert your entire object into an array:
    $variations_arr = $this->to_associative_array($variations);
    foreach($variations_arr as $variation) {
      foreach( $variation['attributes'] as $attribute ) {
        if( !in_array($attribute['id'], $attributeIds) ) {
          array_push($attributeIds, $attribute['id']);
          $attributeNames[$attribute['id']] = $attribute['name'];
          $attributesTerms[$attribute['id']] = 
            $this->to_associative_array($woocommerce->get('products/attributes/'.$attribute['id'].'/terms', $filters)); 
        }
      }
    }
    
    foreach($variations_arr as &$variation) {
      if ( $id = attribute_not_exists($variation['attributes'], $attributeIds) ) {
        $variation['attributes'] = array_merge(
          $variation['attributes'], 
          make_attributes(
            $id,
            $attributeNames[$id], 
            $attributesTerms[$id]
          )
        );
      }
    }
  
    return $this->to_stdclass($variations_arr);    
  }
  
  /**
   * Convert a stdclass into array
   */
  function to_associative_array($stdclass, $associative=true){
    // Hence the following line will convert your entire object into an array:
    return json_decode(json_encode($stdclass), $associative);
  }

  /**
   * Convert an array into stdclass
   */
  function to_stdclass($array){
    return json_decode(json_encode($array));
  }

  /**
   * Return a json response with attributes terms
   */
  public function view_products_attributes_terms( \WP_REST_Request $request ) {

    $filters = [];
    
    if( isset($request['parent']) && is_numeric($request['parent']) ) {
      $filters['parent'] = $request['parent']; 
    }

    if( isset($request['product']) && is_numeric($request['product']) ) {
      $filters['product'] = $request['product'];
    }

    $woocommerce = $this->get_client();
    $id = $request->get_param( 'attribute_id' );

    return $woocommerce->get( "products/attributes/$id/terms", $filters);
  }

  /**
   * Adds the items that came with the request to the cart and redirects to the desktop checkout page
   */
  public function checkout_redirect ( \WP_REST_Request $request ) {

    $products = json_decode( $request['items'], true );

    // make sure the cart content is set as cookie
    do_action( 'woocommerce_set_cart_cookies', true );
    global $woocommerce;

    foreach( $products as $product ) {

      if  ( isset( $product['id'] ) && isset( $product['quantity'] ) && is_numeric( $product['id'] ) && is_numeric( $product['quantity'] ) )  {

        // if the product has a variation add it as a variable product
        if ( isset( $product['variationId'] ) && is_numeric( $product['variationId'] ) ) {

          $woocommerce->cart->add_to_cart( $product['id'], $product['quantity'], $product['variationId'] );

        } else {

          $woocommerce->cart->add_to_cart( $product['id'], $product['quantity'] );
        }

      }
    }

    wp_redirect( $woocommerce->cart->get_checkout_url() );
    exit();

  }
}


/**
 * Return a missed attribute id if not find or
 * return 0 if exists
 */
function attribute_not_exists($attributes = array(), $ids = array()){
  foreach($ids as $id) {
    if( array_search($id, array_column($attributes, 'id')) === false ) {
      return $id;
    }
  }
  return false;
}

/**
 * Make an array of attributes id, name, option
 */
function make_attributes($id = 0, $name = '', $terms = array()) {
  $array = [];
  foreach($terms as $term) {
    array_push($array, array(
      'id' => $id,
      'name' => $name,
      'option' => $term['name']
    ));
  }
  return $array;
}