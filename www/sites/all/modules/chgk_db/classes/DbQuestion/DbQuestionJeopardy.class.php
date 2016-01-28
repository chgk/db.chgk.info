<?php

require_once (dirname(__FILE__)."/../DbQuestion.class.php");
require_once (dirname(__FILE__)."/../DbField/DbFieldJeopardyItem.class.php");
require_once (dirname(__FILE__)."/../DbField/DbFieldAnswer.class.php");

class DbQuestionJeopardy extends DbQuestion {

  public $theme;
  public $questions;
  protected $questionFieldName = 'Тема';

  public function getHtml() {
    $this->packSources();
    if(!$this->questions) {
      //Финальный вопрос
      return theme('chgk_db_question', $this);
    } else {
      return theme('chgk_db_jeopardy_question', $this);
    }
  }
  

  protected function setFields() {
    parent::setFields();
    $this->split();
    $this->packSources();
  }

  private function packSources() {
      if (!isset ($this->fields['Sources'])) {
          return;
      }
      $this->fields['Sources']->pack();
  }

  private function split() {
    $this->splitQuestions();
    $this->splitAnswers();
  }

  private function splitAnswers(){
    $answer = $this->getField('Answer');
    if (!$answer) return;
    $a = "dummy\n". $answer->getValue();
    $parts = preg_split('/\n\s+(\d+)\.\s*/', $a, -1, PREG_SPLIT_DELIM_CAPTURE);
    array_shift($parts);
    while ($parts) {
      $number = array_shift($parts);
      $text  = array_shift($parts);
      $this->questions[$number]->Answer=
              new DbFieldAnswer('', $text, $number);
    }
  }

  private function splitQuestions() {
    $q = "\n".$this->getField('Question')->getValue();
    $parts = preg_split('/\n\s+(\d+)\.\s*/', $q, -1, PREG_SPLIT_DELIM_CAPTURE);
    $this->theme = array_shift($parts);
    while ($parts) {
      $number = array_shift($parts);
      $text  = array_shift($parts);
      $row = new stdClass();
      $row->Question = new DbFieldQuestion('', $text, $number);
      $row->number = $number;
      $this->questions[$number]=$row;
    }
  }
}
