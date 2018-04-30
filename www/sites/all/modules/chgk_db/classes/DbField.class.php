<?php

require_once(dirname(__FILE__)."/DbField/DbFieldQuestion.class.php");

class DbField {
  private $field;
  protected $value; 
  protected $number;
  protected $html;
  protected $paragraphs;
  protected $fb2;
  protected $poems;
  protected $isForPrint;
  protected $codes;
  protected $searchString;
  protected $parent;
  protected $imageBaseUrl='/images/db';
  const SOUNDS_BASE_URL='/sounds/db';
#  const MP3_PLAYER_TEMPLATE= '<embed type="application/x-shockwave-flash" src="http://www.google.com/reader/ui/3523697345-audio-player.swf?audioUrl=%s"  allowscriptaccess="never" width="400" height="27" quality="best" bgcolor="#ff0000" wmode="window" flashvars="playerMode=embedded" />';
//  const MP3_PLAYER_TEMPLATE= '<embed type="application/x-shockwave-flash" src="/sounds/3523697345-audio-player.swf?audioUrl=%s"  allowscriptaccess="never" width="400" height="27" quality="best" bgcolor="#ff0000" wmode="window" flashvars="playerMode=embedded" />';
  const MP3_PLAYER_TEMPLATE = '<p><audio src="%s" controls></audio></p>';

  public function __construct($field, $value, $number = false, $parent = null) {
    $this->field = $field;
    $this->value = $value;
    $this->number = $number;
    $this->parent = $parent;
    $imageDomain=variable_get('image_domain', $_SERVER['HTTP_HOST']);
    $this->imageBaseUrl='http://'. $imageDomain .'/images/db';
  }


  public function getCssClass() {
    return $this->field;
  }  
  public function getHtml() {
    
    if ($this->html) {
      return $this->html;
    }

    $this->html = $this->value;
    $this->formatHtml();
    return $this->html;
  }
  
  public function getText() {
    return $this->value;
  }

  protected function updateFirstParagraph() {
    $this->paragraphs[0] =
            '<strong>'.$this->getName().
            ':</strong> '. $this->paragraphs[0];
  }
  
  protected function updateFirstParagraphFb2() {
    $this->paragraphs[0] =
            '<strong>'.$this->getFb2Name().
            ':</strong> '. $this->paragraphs[0];
  }
  
                                                              
  public function getXML() {
     return "<".$this->field."><![CDATA[".$this->getHtml()."]]></".$this->field.">";
  }
  
  public function getFb2() {
    if ($this->fb2) {
      return $this->fb2;
    }    
    $this->fb2 = $this->value;
    $this->fb2 = html_entity_decode($this->fb2, ENT_COMPAT, 'UTF-8');
    $this->fb2 = htmlspecialchars($this->fb2, ENT_NOQUOTES, 'UTF-8');
    $this->split();
    $this->updateFirstParagraph();
    $poemStarts = $poemEnds = array();
    $codeStarts = $codeEnds = array();

    foreach ($this->poems as $p) {
        list($b, $e) = $p;
        $poemStarts[] = $b;
        $poemEnds[] = $e;
    }

    foreach ($this->codes as $p) {
        list($b, $e) = $p;
        $codeStarts[] = $b;
        $codeEnds[] = $e;
    }

    $inpoem = FALSE;
    $incode = FALSE;
    $result = '';
    foreach ($this->paragraphs as $k=>$p) {
        if (in_array($k, $poemStarts)) {
            $inpoem = TRUE;
            $result .= "<poem><stanza>\n";
        }
        if (in_array($k, $codeStarts)) {
            $incode = TRUE;
            $result .= "<poem><stanza>\n";
        }

        if ($incode) {
            $result .= "<v>$p</v>\n";
        }    elseif ($inpoem) {
            if ($p) $result.="<v>$p</v>\n";
        } else {
            $result.="<p>$p</p>\n";
        }
        if (in_array($k, $poemEnds)) {
            $result .= "</stanza></poem>";

            $inpoem = FALSE;
        }

        if (in_array($k, $codeEnds)) {
            $incode = FALSE;
            $result .= "</stanza></poem>\n";
        }

    }
    $this->fb2 = $result;
//    $this->fb2 = preg_replace('/ -+(\s+)/','&nbsp;&mdash;$1', $this->fb2);
    $this->fb2 = preg_replace('/\(pic: ([^\)]*)\)/','<image l:href="#_$1" />', $this->fb2);
    $this->fb2 = preg_replace('/\s+/',' ', $this->fb2);

    return $this->fb2;
  }

  protected function split() {
      $lines = split ("\n", $this->fb2);
      $this->paragraphs = array();
      $current = '';
      foreach ($lines as $l) {
          if (preg_match('/^[\s\|]/', $l)) {
              $this->paragraphs[] = $current;
              $current = $l ."\n";
          }  else {
             $current .= $l."\n" ;
          }
      }
     $this->paragraphs[] = $current;
     $sp = '';
     $begin = $end = 0;
     $incode = FALSE;
     $this->poems = array();
     $this->codes = array();
     foreach ($this->paragraphs as $k=>$p) {
         if (preg_match('/^\|/', $p )) {
            $this->paragraphs[$k] = preg_replace('/^\|/', '',
                    $this->paragraphs[$k]);
            if (!$incode) {
                $cbegin = $k;
                $incode = TRUE;
            }
         } else {
             if ($incode) {
                $this->codes[] = array($cbegin, $k);
             }
             $incode = FALSE;
         }
        $csp = preg_replace('/\S.*$/', '', $p);
        preg_match('/^\s+/', $p, $matches);
        $csp = $matches[0];
        if ($csp == $sp) {
            $end = $k;
        }
        else {
            if ($begin!=$end && $csp) {
                $this->poems[] = array($begin, $end);
            }
            $begin = $end = $k;
            $sp = $csp;
        }
     }
     if ($incode) {
          $this->codes[] = array($cbegin, $k);
     }

     if ($begin!=$end && $csp) {
        $this->poems[] = array($begin, $end);
     }
  }

  public function formatHtml() {
    $this->html = preg_replace('/(\s+)-+(\s+)/','\1&mdash;$2', $this->html);
    $this->html = preg_replace('/^((\|[^\n]*\n?)+)/m',"</p><pre>\n\$1</pre><p>\n", $this->html);
    $this->html = preg_replace('/\[Раздаточный материал:(.*?)\]\s*\n/sm',
        "<div class=\"razdatka\"><div class=\"razdatka_header\">Раздаточный материал</div> \\1</div>\n",
         $this->html  );
    $this->html = preg_replace('/^\s*<раздатка>(.*?)<\/раздатка>/sm',
        "<div class=\"razdatka\"><div class=\"razdatka_header\">Раздаточный материал</div> \\1</div>\n",
         $this->html  );

    $this->html = preg_replace('/^\s+/m', "<br />\n&nbsp;&nbsp;&nbsp;&nbsp;", $this->html);  

    if (!preg_match('/^\|/m',$this->html)) {
      $this->html = preg_replace('/\s+\&#0150/m','&nbsp;\&#0150', $this->html);
    }
    $this->html = preg_replace('/^\|/m','', $this->html);

#    $this->html = preg_replace('/\(pic: ([^\)]*)\)/','<br /><img src="'.$this->getImageBaseUrl().'/$1"><br />', $this->html);
    $iregexp = '/\(pic: ([^\)]*)\)/';
    $this->html = preg_replace('/\(pic: ([^\)]*)\)/e','"<br /><img src=\"".$this->getImageUrl(\'$1\')."\"><br />"', $this->html);
    $this->addSoundsToHtml();
    if ($this->getSearchString()) {
        $this->highLight();
    }    
  }
  
  protected function getImageUrl( $name  ) {
    if ( preg_match( '/\d{8}\./', $name ) ) {
      $result =  $this->getImageBaseUrl()."/".$name;
    } elseif ( $attachment = $this->parent->getAttachment( $name ) ) {
          $result = url( $attachment->filepath );
    }
    if ( !$result ) $result = $name;
    return $result;
  }
  
  
  protected function addSoundsToHtml() {
    $url = $this->getSoundsBaseUrl().'/$1';
    $playerTemplate = self::MP3_PLAYER_TEMPLATE;
    $playerString = sprintf($playerTemplate, $url);
    $this->html = preg_replace('/\(aud: ([^\)]*)\)/',$playerString, $this->html);

  }
  
  protected function highLight() {
      $sstr = $this->getSearchString();
      setlocale(LC_ALL, 'ru_RU.utf8');
      preg_match_all('/[\wа-я]{2,}\*?/iu', $sstr, $matchs);
      $terms= $matchs[0];
      foreach ($terms as $term) {
        if ( preg_match('/\*$/', $term) ) {
           $letters=preg_replace('/\*/', '', $term);
           $this->html = preg_replace("/\\b{$letters}[\wа-я]*/iu",
                   '@#@1@@@$0~#~1~~~', $this->html);
        } else {
           $this->html = preg_replace("/{$term}/iu",
                   '@#@1@@@$0~#~1~~~', $this->html);
        }
      }
      $this->html = str_replace("@#@1@@@", '<strong class="highlight">', $this->html);
      $this->html = str_replace("~#~1~~~", '</strong>', $this->html);
  }

  public function getName( $nohtml = false ) {
    return $this->field;
  }
  
  public function getFb2Name() {
    return $this->field;
  }

  public function getNumber() {
    return $this->number;
  }

  public function isEmpty() {
    return $this->value === NULL || $this->value==='';
  } 
  
  public function getValue() {
    return $this->value;
  }

  public function getImages() {
      $m = preg_match_all('/\(pic:\s*(.*?)\)/', $this->value,
              $matches, PREG_PATTERN_ORDER);
     $this->images =  $matches[1];
      return $this->images;
  }

  public function getSearchString() {
    if (!$this->parent) {
        return '';
    }
    return $this->parent->getSearchString();
  }
  
  protected function getImageBaseUrl() {
    return $this->imageBaseUrl;
  }

  public function setImageBaseUrl($url) {
    return $this->imageBaseUrl = $url;
  }

  protected function getSoundsBaseUrl() {
    return self::SOUNDS_BASE_URL;
  }

  public function setForPrint($flag= TRUE) {
    $this->isForPrint = $flag;
  }
  
  public function setValue( $value ) {
    $this->value = $value;
  }


}
