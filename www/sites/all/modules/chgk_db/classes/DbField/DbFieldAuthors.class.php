<?php

class DbFieldAuthors extends DbField {
  public function getName() {
    if (preg_match('/, /', $this->getValue())) {
      return 'Авторы';
    } else {
      return 'Автор';      
    }
  }
  
  public function formatHtml() {
    parent::formatHtml();
    if ($this->parent->noAuthorLinks() ) return;

    $authors = $this->parent->getAuthors();
    $t = new DbPersonInHTML();
    $this->html = $t->makeLinks( $authors, $this->html );
    return;
    foreach ($authors as $author) {
      $link = $author->getLink();
      $name =  $author->getShortName();
      list($n, $s) = explode(' ', $name);
      $n = preg_replace('/(..)$/u', '.*?\b', $n);
      $s = preg_replace('/(..)$/u', '.*?\b', $s);
      $r = "/($n\\s+$s)/u";
      if ( strpos( $this->html, $name ) !== false ) {
        $this->html = str_replace($name, $author->getLink(), $this->html);
      } elseif ( preg_match( $r, $this->html, $matches ) ) {
        $this->html = preg_replace($r,  $author->getLink( $matches[1]) , $this->html );
      }  else {
        $links[] = $author->getLink();
      }
    }
    
    
    if ($links) {
      if ( sizeof($authors) == 1 ) {
        $r = preg_replace('/ \(.*$/', '', $this->html);
        $this->html = preg_replace("/^$r/u", $authors[0]->getLink($r), $this->html );
      } else {
        $this->html.='<br/>Страницы авторов: '.implode(',<br/>',$links );
      }
    } 
  }
  
  public function highlight() {
    return;
  }

}
