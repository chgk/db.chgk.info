<?php
require_once(dirname(__FILE__)."/../DbQuestionFactory.class.php");

class DbPackageTour extends DbPackage {
  public $questions;
  protected $editors = null;


  public function getAll() {
    $this->loadQuestions();
    return $this;
  }

/*  public function getPrintVersion() {
     $this->loadQuestions();
     $content = theme('chgk_db_tour', $this);
     return theme('chgk_db_print', $this->getLongTitle(), $content);
  }
  */
  
  public function getHtmlContent() {
    $this->loadQuestions();
    return theme('chgk_db_tour', $this);
  }


  public function getQuestionsXml( $xw ) {
    $this->loadQuestions();
    foreach ( $this->questions as $question ) {
      $question->getQuestionXml( $xw );
    }
  }
  
  public function loadQuestions() {
    if (isset($this->questions)) return;
      
    $this->questions = array();

    $res = $this->db->getQuestionsRes($this->getDbId());
    $factory = new DbQuestionFactory($this->noAnswers);
    while ($row = $this->db->fetch_row($res)) {
      $this->addQuestion( $row );
/*      $parent = $this->parent && $this->parent->isSingleTour()?$this->parent:$this;
      $this->questions[$row->Number] = $factory->getQuestion($row, $parent);
      $this->questions[$row->Number]->setNoAnswers($this->noAnswers);
      $this->questions[$row->Number]->setForPrint($this->isForPrint);*/
    }
  }
 
  
  public function getLongTitle() {
    return $this-> getParent()->getTitle(). '  '. $this->getTitle();
  }
  
  public function getParentInfo() {
    return $this->getParent()->getInfo();
  }
  public function getParentEditor() {
    return $this->getParent()->getEditor();
  }
  
  public function getImages() {
      $this->images = array();
      foreach ($this->questions as $q) {
          $this->images = array_merge($this->images, $q->getImages());
      }
      return $this->images;
  }
  public function isSingleTour() {
      return true;
  }
  public function getFb2MainPart() {
      return theme('chgk_db_tour_fb2', $this);
  }

  public function hasOwnInfo() {
      return
         preg_replace('/\s/sm', '', $this->getParentInfo())
                 !=
         preg_replace('/\s/sm', '', $this->getInfo());
  }

   public function getFullTitle() {
     $result=  $this->getParent()->getTitle();
     if (!preg_match('/\.\s*$/', $result)) {
             $result .= ". ";
     }
     $result.=" ".$this->getTitle() ;
     return $result;
  }
  public function loadChildren() {
     $this->children = array();
  }
  
  public function hasPrintVersion() {
    return TRUE;
  }

  public function hasFb2() {
    return TRUE;
  }
  
  public function getPlayedAt() {
    $date = parent::getPlayedAt();
    if (!$date) $date = $this->getParent()->getPlayedAt();
    return $date;
  }

  protected function getSrcLinks() {
    $res = parent::getSrcLinks();
    if (!$res) $res = $this->getParent()->getSrcLinks();
    return $res;
  }

//  public function getTourInfoBlock() {
//    return $this->getParent()->getTourInfoBlock();
//  }


  protected function getRatingURL() {
    $url = parent::getRatingUrl();
    if (!$url) $url = $this->getParent()->getRatingUrl();
    return $url;
  }

  public function getTourList() {
    return $this->getParent()->getTourList();
    $links = array();
    foreach ($this->getTours() as $t) {
      $links[] = $t->getHtmlLinkForList(FALSE);
    }
    return theme('chgk_db_links', $links);
  }
  
  public function addQuestion( $row ) {
    if ( is_array($row) ) {
      $row = (object) $row;      
    }
    $factory = new DbQuestionFactory($this->noAnswers);
    if (!$this->questions) {
      $this->questions = array();
    }
    $parent = $this->getParent() && $this->getParent()->isSingleTour() ? $this->getParent() : $this;
    $this->questions[$row->Number] = $factory->getQuestion($row, $parent );
    $this->questions[$row->Number]->setNoAnswers($this->noAnswers);
    $this->questions[$row->Number]->setForPrint($this->isForPrint);
    $this->questions[$row->Number]->setForSearch($this->isForSearch);
    return $this->questions[$row->Number];
  }
  
  public function getQuestion( $number ) {
    if ( isset( $this->questions[ $number ] ) ) {
      return  $this->questions[ $number ];
    } else {
      $factory = new DbQuestionFactory();
      $row = $this->db->getQuestionByNumber($this->getDbId(), $number);
      return  $factory->getQuestion($row);
    }
  }
  
  public function getText() {
    
  }
  
  public function getNumber() {
    return $this->tour->Number;
  }
  
  public function save() {
    parent::save(); 
    foreach ( $this->questions as $q ) {
      $q->setFieldValue( 'ParentId', $this->tour->Id );
      $q->save();
    }
  }
  public function delete() {
    $this->loadQuestions();
    foreach ( $this->questions as $q ) {
      $q->delete();
    }
    parent::delete();
  }
  
  public function getEditors() {
    if ( $this->editors === NULL ) {
      $this->editors = parent::getEditors();
      
      if (!$this->editors && $this->getEditor() == $this->getParentEditor() ) {
        $this->editors = $this->getParent()->getEditors();
      }
    }

    return $this->editors;
  }


}
