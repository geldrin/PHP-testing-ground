<?php
namespace AuthDirectories;

abstract class Base {
  protected $bootstrap;
  protected $organization;
  protected $directory;
  protected $directoryuser = array(
    'user'   => array(),
    'groups' => array(),
  );

  public function __construct( $bootstrap, $organization, $directory ) {
    $this->bootstrap    = $bootstrap;
    $this->organization = $organization;
    $this->directory    = $directory;
  }

  public function syncWithUser( $user ) {
    if ( !$this->directoryuser['user'] )
      return;

    $userModel = $this->bootstrap->getModel('users');
    $userModel->id = $user['id'];
    $userModel->updateRow( $this->directoryuser['user'] );
    $userModel->registerForSession();
  }

  public function syncGroupsForUser( $user ) {
    $groups     = $this->directoryuser['groups'];
    $groupModel = $this->bootstrap->getModel('groups');
    $userModel  = $this->bootstrap->getModel('users');
    $userModel->id = $user['id'];

    $existinggroups  = $userModel->getAssocDirectoryGroupIDs( $this->organization['id'] );
    $directorygroups = $groupModel->getDirectoryGroups( $this->organization['id'] );
    $neededgroups    = array();
    $lookuptable     = array();
    $groupids        = array(); // megjegyezzuk a csoportokat a torleshez
    $needupdate      = false; // kell e updatelni a felhasznalo csoportjait
    $needcleargroups = (bool) count( $existinggroups ); // ha nincs, akkor nem torlunk feleslegesen

    foreach( $groups as $group )
      $lookuptable[ $group ] = true;

    unset( $groups );

    foreach( $directorygroups as $group ) {
      $groupids[] = $group['id'];

      if ( isset( $lookuptable[ $group['dn'] ] ) )
        $neededgroups[] = $group['id'];
    }

    // megallaptjuk hogy kell e a db-hez nyulni, ha elter a csoportok szama
    // akkor biztos hogy kell
    if ( count( $neededgroups ) != count( $existinggroups ) )
      $needupdate = true;

    if ( !$needupdate ) {

      foreach( $neededgroups as $id ) {

        // valamilyen csoportnak nem tagja a felhasznalo
        if ( !isset( $existinggroups[ $id ] ) ) {
          $needupdate = true;
          break;
        } else
          // toroljuk a tombbol azokat a csoportokat amiknek a tagja
          // kesobb ellenorizzuk hogy a tombben maradt e valami mert az azt
          // jelenti hogy olyan csoportnak tagja aminek nem kellene hogy tagja
          // legyen
          unset( $existinggroups[ $id ] );

      }

      if ( !$needupdate and count( $existinggroups ) != 0 )
        $needupdate = true;

    }

    if ( !$needupdate )
      return;

    if ( $needcleargroups ) // vannak csoportjai, toroljuk oket
      $userModel->clearFromGroups( $groupids );

    $userModel->addGroups( $neededgroups );

  }

  abstract public function handle($directory);

}
