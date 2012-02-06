<?php
namespace Admin;
class Menu {
  
  public static function get() {
    /*
      regi:
      Array(
        'link'   => Array('smsqueue?target=clearsession'),
        'icon'   => 'images/sekkyumu/run.png',
        'text'   => 'Küldési sor',
        'access' => 'true', - config fileba permissions tomb valtja ki
      ),
    */
    $menu = array(
       array(
        'link'   => 'users',
        'icon'   => 'images/sekkyumu/run.png',
        'text'   => 'Felhasználók',
      ),
      
      array(
        'link'   => 'recordings',
        'icon'   => 'images/sekkyumu/write_document.png',
        'text'   => 'felvételek',
      ),
      
      array(
        'link'   => 'contents',
        'icon'   => 'images/sekkyumu/write_document.png',
        'text'   => 'tartalmak',
      ),
      
      array(
        'link'   => 'help_contents',
        'icon'   => 'images/sekkyumu/write_document.png',
        'text'   => 'súgó tartalmak',
      ),
      
      array(
        'link'   => 'languages',
        'icon'   => 'images/sekkyumu/write_document.png',
        'text'   => 'nyelvek',
      ),
      
      array(
        'link'   => 'genres',
        'icon'   => 'images/sekkyumu/write_document.png',
        'text'   => 'műfajok',
      ),
      
      array(
        'link'   => 'roles',
        'icon'   => 'images/sekkyumu/write_document.png',
        'text'   => 'szerepek',
      ),
      
      array(
        'link'   => 'organizations',
        'icon'   => 'images/sekkyumu/write_document.png',
        'text'   => 'Intézmények',
      ),
      
    );
    
    return $menu;
    
  }
  
}
