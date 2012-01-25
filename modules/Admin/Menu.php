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
    );
    
    return $menu;
    
  }
  
}
