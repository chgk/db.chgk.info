<?php

class DbQuestionFactory extends DbFactory {
  protected $prefix = 'DbQuestion';
  private $field;
  private $value;
  private $noAnswers;
  static $questions = array(); 
  private $map = array(
    'Я' => 'Jeopardy'
  );

  public function __construct($noAnswers = FALSE) {
    $this->noAnswers = $noAnswers;
  }
  
  public function getQuestion($row, $parent=false) {
    if ( is_array( $row ) ) $row = (object) $row;
    if ( is_object( $row ) && $row->TextId && self::$questions[$row->TextId] )
      return self::$questions[$row->TextId];
    elseif( !is_object ($row) && $row && self::$questions[$row->TextId]) {
      return self::$questions[$row->TextId];
    }
    
    if ( is_object( $row ) || !$row ) {
      $result = $this->getQuestionFromRow( $row, $parent );
    } else {
      $result = $this->getQuestionFromTextId( $row );
    }
    self::$questions[ $result->getTextId() ] = $result;
    return $result;
  }
  
  public function getUnsortedQuestion( $node, $tour, $question ) {
    module_load_include('class.php', 'chgk_db', 'classes/DbUnsorted'); 
    $unsorted = new DbUnsorted( $node );
    return $unsorted->getQuestion( $tour, $question );
  }
  
  public function getQuestionFromTextId( $textId ) {
    if ( preg_match('/^unsorted(\d+)(?:[.](\d+))*-(\d+)/', $textId, $matches ) ) {
      return $this->getUnsortedQuestion( $matches[1], $matches[2], $matches[3] );
    }
    $textId = str_replace('/', '-', $textId);
    $arr = explode( '-', $textId );
    $number = array_pop( $arr );
    $tour = implode( '-', $arr );
    $tournament = DbPackage::newFromDb( $tour );
    if ( $tournament instanceof DbPackageError) return FALSE;    
    $question = $tournament->getQuestion($number);

    $isQuestion = $question->exists();
    if (!$question->exists()) {
      return FALSE;
    } else {
    return $question;
    } 
  }
  
  public function getQuestionFromRow( $row, $parent = null ) {
    if ( is_array($row) ) {
	$row = (object) $row;
    }

    $this->row = $row;
    if ($this->classExists()) {
      $ref = new ReflectionClass($this->getClassName());
      $result = $ref->newInstance($row,$parent, $this->noAnswers);
    } else {
      $result = new DbQuestion($row, $parent, $this->noAnswers);
    }
    return $result;

  }
  
  protected function getClassName() {

    if (!(isset($this->map[$this->row->Type]))) return 'DbQuestion';

    return 'DbQuestion'.$this->map[$this->row->Type];
  }
}
