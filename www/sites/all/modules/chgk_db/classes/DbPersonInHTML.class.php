<?php 
class DbPersonInHTML {

  public function makeLinks( $authors, $html ) {
    $r = '/,\s*(\w+)\s+И\s+(\w+)\s+(\w+)\s*([,\(])/u';
    $regs = array(
      '/(,)\s*(\w+)\s+И\s+(\w+)\s+(\w+)\s*([,\(]|$)/iu',
      '/(^)\s*(\w+)\s+И\s+(\w+)\s+(\w+)\s*([,\(]|$)/iu',
    );
    foreach ($regs as $r) {    
      if ( preg_match( $r, $html ) ) {
        $html = preg_replace($r, '$1 $2 ###$4@@@ и $3 $4 $5', $html);
      }
    }
    foreach ($authors as $author) {

      $link = $author->getLink();
      $name =  $author->getShortName();

      list($n, $s) = explode(' ', $name);
      $n = str_replace("?", "\\?", $n);
      $b = '(?!\pL)';// It is utf-8 friendly replacement for \b
      $n = preg_replace('/(..)$/u', '\S*?'.$b, $n);
      $s = preg_replace('/(..)$/u', '\S*?'.$b, $s);

      $r = "/($n\\s+(###)?$s(@@@)?)/iu";
      $r = preg_replace('/[её]/ui', '[её]', $r);
      if ( strpos( $html, $name ) !== false ) {
        $html = str_replace($name, $author->getLink( $name ), $html);
      } elseif ( preg_match( $r, $html, $matches ) ) {
        $html = preg_replace($r,  $author->getLink( $matches[1]) , $html );
      }  else {
        $links[] = $author->getLink();
      }
    }
    $html = preg_replace('/###.*?@@@/','',$html); 
    
    if ($links) {
      if ( sizeof($authors) == 1 ) {
        $r = preg_replace('/ \(.*$/', '', $html);
        $r_escaped = preg_quote($r);
        $html = preg_replace("/^$r_escaped/u", $authors[0]->getLink($r), $html );
      } else {
        $html.='<br/>Ссылки: '.implode(',<br/>',$links );
      }
    } 
    return $html;
 }

}
