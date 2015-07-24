<?php
namespace Visitor\Streamingservers;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    // ures minden, csak api-ra hasznaljuk az egeszet
  );
  
  public $forms = array(
  );
  
  public $paging = array(
  );
  
  public $apisignature = array(
    'updatestatus' => array(
      'loginrequired' => false,
      ),
    'server' => array(
      'type'        => 'string',
      'required'    => true,
      'shouldemail' => false,
    ),
    'hash' => array(
      'type'        => 'string',
      'required'    => true,
      'shouldemail' => false,
    ),
    'reportsequencenum' => array(
      'type'        => 'id',
      'required'    => true,
      'shouldemail' => false,
    ),
  );
  
  public function updatestatusAction( $server, $hash, $reportsequencenum ) {
    $serverModel = $this->bootstrap->getModel('streamingservers');
    $d           = \Springboard\Debug::getInstance();

    if ( !$serverModel->getServerByHost( $server ) ) {
      throw new \Exception("server not found");
      return false;
    }

    if (
         $serverModel->row['reportsequencenum'] and
         !$serverModel->row['reportsequencenum'] <= $reportsequencenum
       ) {
      // ha meg nincs bealitva server oldalon akkor engedjuk, ha kisebb akkor
      // nem engedjuk
      throw new \Exception("invalid sequence number");
      return false;
    }

    $data     = file_get_contents('php://input');
    $datahash = hash_hmac(
      'sha256',
      $data,
      $serverModel->row['salt'] . $reportsequencenum
    );

    if ( $hash === $datahash ) {
      throw new \Exception("hash check failed");
      return false;
    }

    $info = json_decode( $data, true );
    $row  = array(
      'reportsequencenum' => $reportsequencenum,
    );

    $keysToFields = array(
      'features' => array(
        'live' => array(
           'rtmp'  => true,
           'rtmpt' => true,
           'rtmps' => true,
           'hls'   => true,
           'hlss'  => true,
           'hds'   => true,
           'hdss'  => true
        ),
        'ondemand' => array(
           'rtmp'  => true,
           'rtmpt' => true,
           'rtmps' => true,
           'hls'   => true,
           'hlss'  => true,
           'hds'   => true,
           'hdss'  => true,
        ),
      ),
      'network' => array(
        'traffic_in'  => true,
        'traffic_out' => true,
      ),
      'load' => array(
        'cpu' => array(
          'min5' => true,
        ),
        'clients' => array(
          'http'  => true,
          'https' => true,
          'rtmp'  => true,
        ),
      ),
    );

    $row = $this->fillRowFromInfoArray( $info, $keysToFields, $row );
    $serverModel->updateRow( $row );
    return true;
  }

  public function fillRowFromInfoArray( $info, $keysToFields, &$row, $field = '' ) {
    // vegig megyunk a $keysToFields tombon, ha stringet talalunk es van
    // az info tombben ilyen stringel ertek akkor at beirjuk a $row-ba
    // amugy egy al-tomb az ertek ami azt jelenti hogy megnezzuk hogy van e
    // ilyen kulccsal ertek az $info-ban, ha igen akkor ezt az erteket adjuk
    // at rekurzivan sajat magunknak igy haladunk befele
    foreach( $keysToFields as $key => $value ) {
      $newfield = $field . '_' . $key;

      if (
           $value === true and
           isset( $info[ $key ] )
         ) {
        $newfield = ltrim( $newfield, '_' );
        $row[ $newfield ] = $info[ $key ];
      } elseif ( is_array( $value ) and isset( $info[ $key ] ) ) {
        $this->fillRowFromInfoArray( $info[ $key ], $value, $row, $newfield );
      }
    }

    return $row;
  }
}
