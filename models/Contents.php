<?php
namespace Model;

class Contents extends \Springboard\Model\Multilingual {
  public $multistringfields = array( 'title', 'body' );
  
  public function getContent( $content, $language ) {
    
    $content = $this->db->getRow("
      SELECT c.*, sbody.value as body, stitle.value as title
      FROM 
        contents c,
        strings as sbody,
        strings as stitle
      WHERE 
        c.shortname = " . $this->db->qstr( $content ) . " AND
        sbody.language = '" . $language . "' AND
        stitle.language = '" . $language . "' AND
        c.title_stringid = stitle.translationof AND
        c.body_stringid  = sbody.translationof
    ");
    
    return $content;
    
  }
  
}
