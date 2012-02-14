<?php
namespace Visitor\Combine;

class Controller extends \Visitor\Controller {
  public $sendheaders = false;
  public $permissions = array(
    'js'          => 'public',
    'css'         => 'public',
  );
  
  public function init() {
    header("Expires: " . gmdate("M d Y H:i:s", strtotime( "+1 day" ) ) . ' GMT' );
    header("Last-Modified: " . gmdate("M d Y H:i:s") . ' GMT' );
  }
  
  public function indexAction() {
    $this->redirect('index');
  }
  
  public function jsAction() {
    $this->serveCache('js');
  }
  
  public function cssAction() {
    $this->serveCache('css');
  }
  
  protected function serveCache( $type ) {
    
    $urls = $this->application->getParameter('url', array() );
    if ( empty( $urls ) )
      $this->redirect('index');
    
    $urlmd5          = md5( implode('&', $urls ) );
    $plaincache      = $this->bootstrap->getCache( $type . 'combine_plain_' . $urlmd5 );
    $compressedcache = $this->bootstrap->getCache( $type . 'combine_gz_' . $urlmd5 );
    
    if ( $plaincache->expired() ) {
      
      $content = '';
      $scheme  = SSL? 'https://': 'http://';
      
      foreach( $urls as $url ) {
        
        $filecontent = $this->getFile( $url );
        if ( $type == 'css' )
          $filecontent =
            str_replace(
              'url(',
              'url(' . $scheme . $this->application->config['staticuri'],
              $filecontent
            );
        
        $content .= "\n\n/* file: " . $url . " */\n\n" . $filecontent . "\n\n";
        
      }
      
      $data = array(
        'content'     => $content,
        'contenthash' =>
          md5(
            $this->application->config['version'] . $urlmd5 . $content
          )
        ,
      );
      
      $plaincache->put( $data );
      $data['content'] = gzencode( $content, 9 );
      $compressedcache->put( $data );
      
      unset( $data, $content, $filecontent );
      
    }
    
    $encodingheader = '';
    $matchheader    = '';
    
    if ( isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) )
      $encodingheader = $_SERVER['HTTP_ACCEPT_ENCODING'];
    
    if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) )
      $matchheader = $_SERVER['HTTP_IF_NONE_MATCH'];
    
    $encoding = strpos( $encodingheader, 'gzip' );
    
    if ( $encoding )
      $data = $compressedcache->get();
    else
      $data = $plaincache->get();
    
    if ( $matchheader == '"' . $data['contenthash'] . '"' )
      header('HTTP/1.1 304 Not Modified');
    else {
      
      if ( $type == 'js' )
        header("Content-Type: text/javascript");
      else
        header("Content-Type: text/css");
      
      header("ETag: \"" . $data['contenthash'] . "\"");
      
      if ( $encoding ) {
        
        // Vary: alert proxies that a cached response should be sent only 
        // to clients that send the appropriate Accept-Encoding request header
        header("Vary: Accept-Encoding");
        header("Content-Encoding: gzip");
        
      }
      
      echo $data['content'];
      
    }
    
  }
  
  protected function getFile( $filename ) {
    
    if ( preg_match_all(
           '/^http(s?)\:\/\/([^\/]+).*$/',
           $filename,
           $results
         ) == 1
       ) {
      
      if (
           in_array(
             @$results[2][0],
             $this->application->config['combine']['domains']
           )
         )
        $content = @file_get_contents( $filename );
      else
        $content = false;
      
    }
    else {
      
      $path      = BASE_PATH . 'httpdocs_static/' .
        preg_replace( '/\_v\d+/', '', $filename )
      ;
      $dir       = realpath( pathinfo( $path, PATHINFO_DIRNAME ) );
      $compareto = BASE_PATH . 'httpdocs_static/';
      
      if ( DIRECTORY_SEPARATOR == '\\' )
        // PHP_OS = Windows, WINNT, WIN32, ...
        $compareto = realpath( pathinfo( $compareto, PATHINFO_DIRNAME ) );
      
      if ( strpos( $dir, $compareto ) === 0 )
        $content = @file_get_contents( $path );
      else
        $content = false;
      
    }
    
    if ( $content === false ) {
      
      header('HTTP/1.1 404 Not Found');
      die();
      
    }
    else
      return $content;
    
  }
  
}
