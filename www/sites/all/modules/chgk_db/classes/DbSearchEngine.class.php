<?php

class DbSearchEngine {
  protected $searchFields = array ('Question', 'Answer', 'PassCriteria');
  protected $results;
  protected $mysqlRes;
  protected $ids;

  protected $questionTypes;
  protected $output;
  protected $limit = 50;
  protected $fromDate = false;
  protected $toDate = false;
  protected $sortBy = 'rel';
  protected $all;
  protected $withAnswers = false;
  protected $random = false;
  protected $noImages;
  protected $total = 0;
  protected $complexity = false;
  protected $xml = FALSE;
  
  public function setQuestionTypes($types) {
    $this->questionTypes = $types;
  }

  public function setSearchFields($fields) {
    $this->searchFields = $fields;
  }

  public function setAuthorId( $nick ) {
    $person = new DbPerson( $nick );

    if ( !$person->exists() ) {
      return;
    }
    $this->authorId = $person->getNumericId();
  }  
  public function setAllParameter($all) {
    $this->all = $all;
  }
  
  public function setLimit($limit) {
    $this->limit = 0+$limit;
  }

  public function setWithAnswers($a) {
    $this->withAnswers = (bool)$a;
  }

  public function setSortBy($sort) {
    $this->sortBy = $sort;
  }

  public function setComplexity($complexity) {
    $this->complexity = $complexity;
  }
  

  protected function makeResults() {  
    $factory = new DbQuestionFactory();
      $results = array();
      $words = $this->words;
      foreach ($words as $i=>$word) {
        $words[$i]= preg_replace('/[^a-zA-Zа-яА-Я_0-9]/u', '', $word);
      }

      $s = "~(.)(".implode('|', $words).")~iu";
      while($a = db_fetch_object($this->mysqlRes)) {
        $q = $factory->getQuestion($a);
        if ($this->isXML()) {
          $results[$q->getId()] = $q;
          continue;
        }
        $q->setForSearch();
        $q->setSearchString($this->searchString);
        $html = $q->getHtml();
        if ($words) {        
  //        $html = preg_replace($s, '$1<strong class="highlight">$2</strong>', $html);
        }
        $results[$q->getId()] = array(
          'link' => $q->getUrl(),
          'title' => $q->getSearchTitle(),
          'snippet' => $html
        );
      }

    $this->results = array();
    foreach ($this->ids as $id) {
      $this->results[] = $results[$id];
    }

  }
  
  protected function makeRandomResults() {
    $factory = new DbQuestionFactory();
    $this->results = array();
    while($a = db_fetch_object($this->mysqlRes)) {
      $q = $factory->getQuestion($a);
      if ($this->withAnswers  ) {
        $q->doNotHideAnswer = true;
      }
      $this->results[] = $q;
    }
  }

  protected function isXML() {
    return $this->xml;
  }
  
  public function setXML( $xml = TRUE ) {
    $this->xml = $xml;
  } 
  
  protected function makeTournamentsResults() {  
      $factory = new DbQuestionFactory();
      $this->results = array();
      $words = $this->words;
#      $s = "~([^a-zA-Zа-яА-Я_0-9])(".implode('|', $words).")~iu";
      while($row = db_fetch_object($this->mysqlRes)) {
        $t= DbPackage::newFromRow($row);
#        $title = preg_replace($s, '$1<strong class="highlight">$2</strong>', $t->getTitle());
        $title = $t->getTitle();
        $date = $t->getPlayedAtDate();
        $snippet = '';
        $snippetArray = array();
        if ($date) {
          $snippetArray[]= '<strong>Дата:</strong> '.$date;
        }
        $editor = $t->getEditorHtml();
        if ($editor) {
          $snippetArray[]= "<p>$editor</p>";
        }
        $snippet = implode('<br/>', $snippetArray);

        $this->results[] = array(
          'link' => url($t->getLink()),
          'title' => $title,
          'snippet' => "<br/>$snippet"
        );
    }
  }
  
  public function setToDate($date) {
    $this->toDate = $date;
  }
  public function setFromDate($date) {
    $this->fromDate = $date;
  }
  public function setRandomMode() {
      $this->random = true;
  }
  
  public function getOutput() {
    return $this->output;
  }
  
  public function setNoImages() {
    $this->noImages = true;
  }
  
  public function getTotal() {
    return $this->total;
  }


}
