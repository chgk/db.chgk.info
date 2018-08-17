<?php 


class DbUnsorted {

  protected $questions;
  private $publishedPackage = NULL;

  const TEXT_ID_POSTFIX = '_u';

  public function __construct( $node ) {
    if ( is_object( $node ) ) {
      $this->node = $node;
    } else {
      $this->node = node_load( $node );
    }
  }

  public static function newFromTextId($textId) {
    $db = new DbDatabase();
    $nid = $db->getUnsortedNodeId($textId);
    if (!$nid) {
      return null;
    }
    $node = node_load(['nid' => $nid]);
    return new self($node);
  }

  private function parse() {
    if (!$this->parsed) {
      $this->parser = new DbParser( $this->getSourceText() );
      $this->parsed = $this->parser->getArray();
    }
    return $this->parsed;
  }    
  
  private function getPackage( $tour_values = array() ) {
    if ( $this->package ) {
      return $this->package;
    }
    
    $parsed = $this->parse();
    
    $tour_values[ 'FileName' ] = 'unsorted'.$this->node->nid;
    foreach ( $tour_values as $k => $v ) {
      $parsed['tournament'][ $k ] = $v;
    }
    
    $this->package = DbPackage::newFromRow( $parsed['tournament'] );

    $this->package->setForSearch();
    $this->questions = array();
    $t = array();
    
    foreach ( $parsed['tours'] as $index => $tour ) {
      $t[$index] = $this->package->addTour( $tour[ 'tour' ] );
    };

    foreach ( $t as $i=>$v ) {
      foreach ( $parsed['tours'][$i]['questions'] as $question ) {
        $q = $v->addQuestion( $question );

        $q -> setEditable();
        $q -> setNoContact();
        $q -> setNode( $this->node );
      }
    }
    return $this->package;

  }
  
  public function isPublished() {
    $db = new DbDatabase();
    $t = $db->getTournamentByTextId( $this->getPublishedTextId() );
    return (bool)$t;
  }

  private function getPublishedTextId() {
    return 'pub'.$this->node->nid;
  }

  private function getPublishedPackage() {
    if ( NULL === $this->publishedPackage ) {
      $db = new DbDatabase();
      $t = $db->getTournamentByTextId( $this->getPublishedTextId() );
      if ( $t ) {
        $this->publishedPackage = DbPackage::newFromRow( $t );
      } else {
        $this->publishedPackage = FALSE;
      }
    }

    return $this->publishedPackage;
  }

  public function getCheckedHTML() {
      $text = $this->getSourceText();
      if (!preg_match('/^(Чемпионат|Пакет):/u', $text)) {
          $text = "Чемпионат:\n".$this->node->title."\n\n".$this->getSourceText();
      }
      $api = variable_get('chgk_api', 'http://api.baza-voprosov.ru/');
      $r = drupal_http_request($api.'questions/validate', ['Content-type' => 'application/json'], 'POST',
          json_encode(['text'=>$text, 'outputFormat' => 'html']));
      $data = json_decode($r->data);
      $result = '';
      if ($r->code == 200) {
          $result .= $data->result;
          $this->node->checked = true;
          node_save($this->node);
      } elseif($r->code == 400) {
          drupal_set_message($data->error, 'error');

          $lines = preg_split('/\r\n|\r|\n/', $text);
          $lines[$data->line] = '<p style="background-color:#ffaaaa">'.$lines[$data->line]."</p>";
          $t = implode("\n", $lines);
          $result.="<pre>$t</pre>";
          $this->node->checked = false;
          node_save($this->node);
      } else {
          drupal_set_message('Api communication error. Please try later', 'error');
      }
      return $result;
  }

  private function getPreText() {
    $t = '';
    if ( $this->isPublished() ) {
      $t = '<p>Пакет '.l('опубликован', $this->getPublishedUrl() ) . '. ';
      if ( unsorted_access( 'check', $this->node ) ) {
        $t.='<p>'.$this->getCancelPublishLink().'</p>';
      }
    } else {
      if ( unsorted_access( 'check', $this->node ) ) {
        $t = "<p>".$this->getPublishLink()."</p>";
      }
    }
    return $t;
  }

  private function getPublishedUrl() {
    return 'tour/pub'.$this->node->nid;
  }

  public function getCancelPublishLink() {
    return l( 'Отменить публикацию', 'node/'.$this->node->nid.'/unpublish');
  }

  public function getPublishLink() {
    return l('Опубликовать', 'node/'.$this->node->nid.'/publish' );
  }

  protected function getSourceText() {
    return str_replace("\r\n", "\n", $this->node->body);
  } 
  
  protected function getErrorHTML( $e ) {
    $message = $e->getMessage();
    $numbers = $this->parser->getErrorLines();
    if (!empty($numbers[0])) { 
      $message .= "&nbsp;&nbsp;&nbsp;<a href=\"#error\">V</a>";
    }
    drupal_set_message( $message, 'error' );
    $result = "<pre class=\"parser-error\">";
    $lines = explode("\n", $this->getSourceText());

    $number = 0;
    foreach ( $lines as $line ) {
      $number++;
      if ( $number == $numbers[0] ) {
        $result .= "<a name=\"error\"></a><div class=\"error\">";
      }
      $result.=htmlspecialchars($line)."\n";
      if ( $number == $numbers[1] ) {
        $result .= "</div>";
      }
    }
    $result.= "</pre>";
    return $result;
  }
  public function getQuestion( $t, $q ) {
    $package = $this->getPackage( );
    $question = $package->getQuestion( $q, $t );
    $question -> setNode( $this->node );
    return $question;
  }
  
  private function getTourName( $number ) {
    reset ( $this->parsed[ 'tours' ] );
    for ($i = 1; $i<$number; $i++ ) next($this->parsed[ 'tours' ]);
    $a =current($this->parsed[ 'tours' ]);
    return $a['tour']['Title'];
  }
  
  public function editQuestion( $id, $questionText) {
    $this->parse();
    $regexp = '/^unsorted'.$this->node->nid.'(\d+)-(\d+)$/';
    if (preg_match('/^unsorted'.$this->node->nid.'.(\d+)-(\d+)$/', $id, $matches ) ) {
	$t = $this->getTourName($matches[ 1 ]);
	$q = $matches [ 2 ];
    } elseif( preg_match('/^unsorted'.$this->node->nid.'-(\d+)$/', $id, $matches ) ) {
    	$t = $this->getTourName( '1' );
	$q = $matches [ 1 ];
    }
    $ln = $this->parser->getQuestionLine( $t, $q );

    if (!$ln) return false;
    list ($begin, $end) = $ln;
    $text = $this->getSourceText();
    $text.="\n";
    $regexp = '/^((?:[^\n]*\n){'.($begin-1).'})([^\n]*?\n){'.($end-$begin+1).'}(.*)$/s';
    $t = str_replace('$','\\$', $questionText);
    $t = str_replace('\\1','\\\\1', $t);
    $newText = preg_replace($regexp, '$1'.$t.'$3', $text);
    $newText = trim($newText);
    $this->node->body = $newText;
    node_save($this->node);
  }
  
  public function publish() {
      $textId = $this->node->text_id;
      if (!$textId) {
          drupal_set_message('Не указан текстовый идентификатор', 'error');
          drupal_goto('node/'.$this->node->nid.'/edit');
      } else {
        $db = new DbDatabase();
        $t = $db->getTournamentByTextId($textId);
        if (!$this->isPublishedByUser() && $t) {
          drupal_set_message("Текстовый идентификатор {$textId} уже используется", 'error');
          drupal_goto('node/'.$this->node->nid);
        }
          $api = variable_get('chgk_api', 'http://api.baza-voprosov.ru/');
          $sourceText = $this->getSourceText();
          $data = ['text' => $sourceText, 'outputFormat' => 'json', 'textId' => $textId, 'processedBy' => (int)$this->node->uid];
          $r = drupal_http_request(
              $api.'questions/validate',
              ['Content-type' => 'application/json'],
              'POST',
              json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
          );
          $data = json_decode($r->data);

          if ($this->isPublishedByUser()) {
            $r = drupal_http_request(
              $api . 'packages/'.$textId,
              ['Content-type' => 'application/json'],
              'PUT',
              $data->result
            );
          } else {
            $r = drupal_http_request(
              $api . 'packages',
              ['Content-type' => 'application/ld+json'],
              'POST',
              $data->result
            );

          }

          if ($r->code>=400) {
            drupal_set_message('Не удалось опубликовать: '.print_r($r->data,1), 'error');
            drupal_goto('node/'.$this->node->nid);
          }
          if ($r->code < 300 ) {
            $this->node->published = true;
            node_save($this->node);
            drupal_set_message('Пакет успешно опубликован');

            drupal_goto('tour/'.$this->node->text_id);
          }
      }
  }

  public function unpublish() {
    $api = variable_get('chgk_api', 'http://api.baza-voprosov.ru/');

    $r = drupal_http_request(
      $api.'packages/'.$this->node->text_id,
      ['Content-type' => 'application/json'],
      'DELETE');
    if ($r->code == 204) {
      $this->node->published = false;
      node_save($this->node);
      drupal_set_message('Пакет "'. $this->node->title .'" удалён из основной базы');
    } else {
      drupal_set_message('Не удалось удалить пакет "'. $this->node->title );
    }
    drupal_goto('node/'.$this->node->nid);
  }

  public function isPublishedByUser() {
    return (bool)$this->node->published;
  }
}
