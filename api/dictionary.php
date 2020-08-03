<?php
//require_once 'node_modules/hquery.php/hquery.php';
require_once TREASUREISLAND_PLUGIN_PATH.'vendor/autoload.php';

use duzun\hQuery;
// use Http\Client\HttpAsyncClient;
// use Http\Discovery\HttpAsyncClientDiscovery;


class Dictionary_API {

  public $prefix = 'dictionary';

  function __construct(){
    $this->register_routes();
  }

  function register_routes(){

    register_rest_route($this->prefix, '/meaning', [
      'method' => 'GET',
      'callback' => [$this, 'meaning']
    ]);

    register_rest_route($this->prefix, '/traduction', [
      'method' => 'GET',
      'callback' => [$this, 'eng_to_ita']
    ]);
  }

  function meaning(\WP_REST_Request $request){
    $word = $request['word'];

    $results = $this->_meaning($word);
    $response = new \WP_REST_Response($results);
    $response->set_status(200);
    return $response;
  }

  function eng_to_ita(\WP_REST_Request $request){
    $word = $request['word'];

    $results = $this->_eng_to_ita($word);
    $response = new \WP_REST_Response($results);
    $response->set_status(200);
    return $response;
  }


  private function _get_page($url){
    return hQuery::fromUrl($url, ['Accept' => 'text/html,application/xhtml+xml;q=0.9,*/*;q=0.8']);
  }

  /*
   * Scrape a meaning of a word with a speech
   */
  private function _meaning($word){

    $url = 'https://dictionary.cambridge.org/dictionary/english/'.$word; 
    $doc = $this->_get_page($url);

    $uk_list = $doc->find('.uk.dpron-i amp-audio source');
    $us_list = $doc->find('.us.dpron-i amp-audio source');

    $def = $doc->find_text('.ddef_d');

    $uk_mp3 = $this->_get_mp3($uk_list);
    $us_mp3 = $this->_get_mp3($us_list);

    return array(
      'uk'  => $uk_mp3,
      'us'  => $us_mp3,
      'def' => $def
    );
  }

  /**
    * Get a mp3 url from a list
    */
  private function _get_mp3($list) {
    if( sizeof($list) ) {
      foreach($list as $pos => $element) {
        $src = $element->attr('src');
        if( strpos($src, '.mp3') ){
          return $src;
        }
      }
    }
    return '';
  }

  /*
   * Return a translation of a word
   */
  function _eng_to_ita($word){
    $url = 'https://dictionary.cambridge.org/dictionary/english-italian/'.$word;
    $doc = $this->_get_page($url);

    $translation = [];
    $results = $doc->find('.dtrans');
    if($results) {
      foreach( $results as $result ) {
        array_push($translation, trim($result->text()));
      }
    }
    return $translation;
  }
}
