<?php
namespace Model;

class Uploads extends \Springboard\Model {
  
  public function getFileResumeInfo( $filename, $filesize, $userid ) {
    
    $this->clearFilter();
    $this->addFilter('userid',   $userid );
    $this->addFilter('filename', $filename, false, false );
    $this->addFilter('size',     $filesize );
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
      
      if ( !rename( $chunkpath, $dest ) )
        return false;
      
      $this->setPermissions( $dest );
      
    } else {
      
      if ( !$this->append( $chunkpath, $dest ) )
        return false;
      
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
  
}
