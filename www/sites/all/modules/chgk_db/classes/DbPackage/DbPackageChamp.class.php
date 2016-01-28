<?php

class DbPackageChamp extends DbPackage {
  private $tours = FALSE;
  
  public function setId( $id = FALSE ) {
    if ( !$id ) { 
       $this->id = $this->tour->TextId or
         $this->id = str_replace('.txt', '', $this->tour->FileName);
    } else {
       parent::setId( $id );
    }
  }
  
  public function getAll() {
    $this->loadTours();
  }
  

  public function getChildrenXml( $xw ) {
    $tours = $this->loadTours();
    if ( sizeof($tours) > 1 ) {
      foreach ( $tours as $child ) {
	      $xw->startElement('tour');
	      $child->getFieldsXml( $xw );
	      $xw->endElement();
      }
    } 
  }
  
  public function getQuestionsXml( $xw ) {
    $tours = $this->loadTours();
    if ( sizeof($tours) == 1 ) {
        current($tours)->getQuestionsXml( $xw );
    }
  }

  private function loadTours($withQuestion = TRUE)  {
    if ($this->tours !== FALSE) {
        return $this->tours;
    }
    $res = $this->db->getChildrenRes($this->getDbId());
    while ($row = $this->db->fetch_row($res)) {
      $this->addTour($row);
    }
    
    if ( $withQuestion ) {
      foreach ($this->tours as $t) {
        $t->loadQuestions();
      }
    }
    return $this->tours;
  }
/*  public function getPrintVersion() {
    $this->loadTours();
    $content = theme('chgk_db_champ_full', $this);
    return theme('chgk_db_print', $this->getTitle(), $content);
  }
*/

  public function loadChildren() {
     $this->children = array();
  }
  
  public function getTours() {
      return $this->tours;
  }
  public function isSingleTour() {
      $res = is_array($this->tours) && sizeof($this->tours)==1;
      return $res;
  }

  public function getImages() {
    $this->images = array();
    foreach ($this->tours as $t) {
        $this->images = array_merge($this->images, $t->getImages());
    }
    return $this->images;
  }

  public function getFb2MainPart() {
      return theme('chgk_db_tours_fb2', $this);
  }
  
  public function getHtmlContent() {
    $this->loadTours();
    return theme('chgk_db_champ_full', $this);  
  }

  public function hasPrintVersion() {
    return TRUE;
  }

  public function hasFb2() {
    return TRUE;
  }

  public function getQuestion($number, $tour = FALSE ) {
    if (!$tour) $tour = 1;
    if ( isset( $this->tours[ $tour ] ) && $q = $this->tours[ $tour ] -> getQuestion( $number ) ) {
      return $q;
    }

      $factory = new DbQuestionFactory();
      $row = $this->db->getQuestionByNumber($this->getDbId(), $number, TRUE);
      return  $factory->getQuestion($row);
  }
  
  public function getTourList() {
    $links = array();
    $tours = $this->loadTours();
    if (sizeof($tours)<=1) {
      return '';
    }
    foreach ($tours as $t) {
      $links[] = $t->getHtmlLinkForList(FALSE);
    }
    return theme('chgk_db_links', $links);
  }

  public function addTour( $row ) {
    if ( is_array($row) ) $row = (object) $row;
#    $row['ParentId'] = $
    $t = new DbPackageTour($row, $this);
    $t->setForPrint($this->isForPrint);
    $t->setForSearch($this->isForSearch);
    $t->setNoAnswers($this->noAnswers);
    return $this->tours[ $row->Number ] = $t;
  }
  
  public function save() {
    parent::save();
    foreach ( $this->tours as $tour ) {
      $tour->setDbField('ParentId', $this->tour->Id);
      $tour->setId( $this->tour->TextId.'.'.$tour->getNumber() );
      $tour->save();
    }
  }

  public function delete() {
    $this->loadTours();

    foreach ( $this->tours as $tour ) {
      $tour->delete();
    }
    parent::delete();
  }
  
  public function getToursNumber() {
    $this->loadTours();
    return count($this->tours);
  }

}
