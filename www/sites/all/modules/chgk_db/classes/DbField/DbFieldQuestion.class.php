<?php

class DbFieldQuestion extends DbField {
  public function getName( $nohtml = false) {
    $questionLink = $this->parent->getQuestionLink();
    
    if ( $this->isForPrint || $nohtml ) {
      return 'Вопрос '.$this->getNumber();
    }

    if ( $this->parent->isEditable() ) {
      return $this->parent->getEditLink( 'Вопрос '.$this->getNumber() );
    }
    
    if ( $questionLink  ) {
      return l('Вопрос '.$this->getNumber(), $this->parent->getQuestionLink());
    }

    return 'Вопрос '.$this->getNumber();
  }

  public function getFb2Name() {
    return 'Вопрос '.$this->getNumber();
  }

  
  public function getFb2() {
    $result = "\n<empty-line/>\n<empty-line/>\n".parent::getFb2();
    return $result;
  }
  protected function updateFirstParagraph() {
    array_unshift($this->paragraphs,
            '<strong>'.$this->getFb2Name().
            ':</strong> ');
  }

}
