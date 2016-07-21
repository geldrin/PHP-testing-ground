<?php
namespace Model;

class Uploads extends \Springboard\Model {

  public function isUploadingAllowed() {
    $server   = $this->db->qstr( $this->bootstrap->config['node_sourceip'] );

    // !!float lesz mert nagyobb mint egy int 32biten
    $minbytes =
      $this->bootstrap->config['upload_minimum_free_gigabytes'] * 1000000000
    ;

    return (bool)$this->db->getOne("
      SELECT COUNT(*)
      FROM infrastructure_nodes
      WHERE
        storageworkfree > '$minbytes' AND
        storagefree     > '$minbytes' AND
        server          = $server AND
        type            = 'frontend'
    ");
  }

  public function getFileResumeInfo( $info ) {

    $this->clearFilter();
    $this->addFilter('userid',    $info['userid'] );
    $this->addFilter('filename',  $info['filename'], false, false );
    $this->addFilter('size',      $info['filesize'] );
    $this->addFilter('iscontent', $info['iscontent'] );

    /*
      ha handlechunk akkor meg nem nyulhatunk hozza kulonosebben
      de a controller varni fog ra egy ideig hogy atmenjen handlechunk-ba
      ha handlechunk akkor epp masolodik/appendelodik a feltoltott chunk a
      "main" chunkhoz, az uploading allapot a "normalis" allapot
    */
    $this->addTextFilter("status IN('uploading', 'handlechunk')");
    return $this->getRow( false, 'id DESC');

  }

  public function getChunkPath() {

    $this->ensureObjectLoaded();
    return
      $this->bootstrap->config['chunkpath'] .
      $this->id . '.' . \Springboard\Filesystem::getExtension( $this->row['filename'] )
    ;

  }

  public function handleChunk( $chunkpath ) {

    $this->ensureObjectLoaded();

    $dest = $this->getChunkPath();
    $this->updateRow( array(
        'status' => 'handlechunk',
      )
    );

    if ( !file_exists( $dest ) ) {

      if ( !rename( $chunkpath, $dest ) ) {
        $this->updateRow( array(
            'status' => 'error_handlechunk_rename',
          )
        );
        return false;
      }

      $this->setPermissions( $dest );

    } else {

      if ( !$this->append( $chunkpath, $dest ) ) {
        $this->updateRow( array(
            'status' => 'error_handlechunk_append',
          )
        );
        return false;
      }

    }

    return true;

  }

  protected function setPermissions( $file ) {

    $oldumask = umask(0);
    chmod( $file, 0664 );
    umask( $oldumask );

  }

  protected function append( $what, $where ) {

    $whathandle  = fopen( $what,  'rb' ); // read only, from beginning
    $wherehandle = fopen( $where, 'ab' ); // write only, from end

    if ( !$whathandle or !$wherehandle )
      return false;

    flock( $whathandle, LOCK_SH ); // dont unlink untill done
    flock( $wherehandle, LOCK_EX ); // nobody else write untill done

    while ( !feof( $whathandle ) ) {

      $data    = fread( $whathandle, 524288 ); // 0.5 mbyte
      $written = 0;
      $len     = strlen( $data );
      $loops   = 0;

      while ( $written != $len and $loops <= 10 ) {

        $loops++;
        $written += fwrite( $wherehandle, $data );
        if ( $written != $len )
          $data   = substr( $data, $written );

      }

      if ( $loops > 10 )
        throw new \Exception('It took more than 10 loops to append the file!');

    }

    fclose( $whathandle );
    fclose( $wherehandle );

    return true;

  }

  public function getUploads( $user, $iscontent = false ) {

    $this->addFilter('iscontent', $iscontent? 1: 0 );
    $this->addFilter('userid', $user['id'] );
    $this->addTextFilter("status IN('uploading', 'handlechunk')");
    return $this->getArray( false, false, false, 'id DESC');

  }

  public function filesizeMatches() {
    $this->ensureObjectLoaded();
    $path = $this->getChunkPath();
    $path = escapeshellarg( $path );
    $command = "stat --printf=\"%s\" $path";

    $size = exec( $command, $output, $exitcode );
    if ( $exitcode != 0 )
      throw new \Exception(
        "size command returned non-0 exit code: $exitcode, " .
        "command: " . $command . " " .
        "output: " . implode("\n", $output )
      );

    return $this->row['size'] == $size;
  }

}
