<?php
namespace Admin;
class Menu {

  public static function get( $bootstrap ) {
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
        'link'   => 'userroles',
        'icon'   => 'images/sekkyumu/write_document.png',
        'text'   => 'Szerepek',
      ),

      array(
        'link'   => 'privileges',
        'icon'   => 'images/sekkyumu/write_document.png',
        'text'   => 'Jogok',
      ),

      array(
        'link'   => 'organizations',
        'icon'   => 'images/sekkyumu/write_document.png',
        'text'   => 'Intézmények',
      ),

      array(
        'link'   => 'languages',
        'icon'   => 'images/sekkyumu/write_document.png',
        'text'   => 'nyelvek',
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
        'link'   => 'genres',
        'icon'   => 'images/sekkyumu/write_document.png',
        'text'   => 'műfajok',
      ),

      array(
        'link'   => 'categories',
        'icon'   => 'images/sekkyumu/write_document.png',
        'text'   => 'kategóriák',
      ),

      array(
        'link'   => 'channel_types',
        'icon'   => 'images/sekkyumu/write_document.png',
        'text'   => 'csatorna típusok',
      ),

      array(
        'link'   => 'localization',
        'icon'   => 'images/sekkyumu/write_document.png',
        'text'   => 'fordítás',
      ),

      /*
      array(
        'link'   => 'roles',
        'icon'   => 'images/sekkyumu/write_document.png',
        'text'   => 'szerepek',
      ),
      */

      array(
        'link'   => 'mailqueue',
        'icon'   => 'images/sekkyumu/write_document.png',
        'text'   => 'Küldési sor',
      ),

    );

    foreach( $menu as $key => $value )
      $menu[ $key ]['text'] = mb_convert_case( $value['text'], MB_CASE_TITLE );

    return $menu;

  }

}
