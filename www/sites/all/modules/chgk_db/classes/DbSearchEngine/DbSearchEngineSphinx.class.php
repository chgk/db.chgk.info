<?php

require_once( dirname(__FILE__).'/../DbSearchEngine.class.php');

class DbSearchEngineSphinx extends DbSearchEngine {
  protected $searchString;
  protected $sstr;
  protected $success=null;
  protected $index = null;
  protected $page = false;
  protected $noPager = false;
  protected $sphinx_index = false;

  public function __construct() {
    $this->sphinx_index = variable_get('sphinx_index', 'chgk');
    $this->index = $this->index_questions = $this->sphinx_index."_questions";
    $this->index_unsorted = $this->sphinx_index."_unsorted";
    $this->index_tournaments = $this->sphinx_index."_tournaments";
  }

  public function setNoPager( $flag = true ) {
    $this->noPager = $flag;
  }
  
  public function getResults($sstr='') {
     $db = new DbDatabase();
     $this->setNoImages();
     $this->searchString = $sstr;
     
     $this->doSearch($sstr, $this->searchFields, $this->all, $this->questionTypes);
     if (!$this->success) {
      return array();
     }
     if ($this->random) {        
        $this->mysqlRes = $db->getSphinxSearchRes($this->ids);
        $this->makeRandomResults();      
     }
     elseif ($this->index==$this->index_questions) {
        $this->mysqlRes = $db->getSphinxSearchRes($this->ids);
        $this->makeResults();
     } elseif ($this->index==$this->index_unsorted) {
        $this->makeUnsortedResults();        
     } else  {
        $this->mysqlRes = $db->getTournamentsByIdsRes($this->ids);
        $this->makeTournamentsResults();        
     }
     if (!$this->random && !$this->noPager) $this->setPager();
     return $this->results;

  }
  
  protected function setPager() {
    global $pager_page_array, $pager_total, $pager_total_items; 
    $pager_page_array = array($this->getPage());
    $pager_total_items = array($this->count);
    $pager_total = array(ceil($pager_total_items[0] / $this->limit));
    $pager_page_array = array(max(0, min((int)$pager_page_array[0], ((int)$pager_total[0]) - 1)));
  }

  protected function getPage() {
    if ($this->page===false) {
      $this->page = isset($_GET['page']) ? $_GET['page'] : '';
    }
    return $this->page;
  }
  
  private function initSphinx() {
    $this->sphinx = new SphinxClient();
    $this->sphinx->SetServer(
      variable_get('sphinx_server', 'localhost'), 
      variable_get('sphinx_port', 9312)
    );
    $this->sphinx->SetMaxQueryTime(3000);
    $searchType = SPH_MATCH_EXTENDED2;
    $this->sphinx->SetMatchMode($searchType);   # Режим поиска совпадений
    if ($this->random) {
         $this->sphinx->SetSortMode(SPH_SORT_EXTENDED, '@random');
         $this->sortBy = 'random';
    } else{
      $this->sphinx->SetSortMode(SPH_SORT_RELEVANCE);
    }
  }
  
  private function setSortOrder() {
    if ( $this->sortBy == '' ||  $this->sortBy == 'rel' ) {
      $this->sphinx->setSortMode(SPH_SORT_RELEVANCE);
    } elseif ( $this->sortBy == 'random' ) {
         $this->sphinx->SetSortMode( SPH_SORT_EXTENDED, '@random');
    } elseif ( $this->sortBy == 'date' ) {
         $this->sphinx->SetSortMode( SPH_SORT_ATTR_DESC, 'playDate' );
    }
  }
  
  public function setRandomMode() {
      parent::setRandomMode();
      if ($this->sphinx) {
         $this->sphinx->SetSortMode(SPH_SORT_EXTENDED, '@random');
      }
  }
  
  protected function setWeights() {
    $fields=explode(' ', 'Question Answer PassCriteria Sources Authors Comments');

    $weights = array();
    foreach ($fields as $field) $weights[$field] = 0;
    foreach ($searchIn as $field) $weights[$field] = 1000;
    $this->sphinx->SetFieldWeights($weights);   # Вес полей
  }

  protected function setQuestionTypesFilter() {
    if ($this->index!=$this->index_questions) return;
    if ($this->questionTypes) $this->sphinx->setFilter('TypeNum', $this->questionTypes);
  }

  protected function setComplexityFilter() {
    if ( ! $this->random ) return;
    if ( $this->complexity  ) $this->sphinx->setFilter('Complexity', array($this->complexity));

  }
  
  protected function setLimits() {
    if ($this->random) {
      $this->sphinx->SetLimits(1,$this->limit);
    } else {
    $this->sphinx->SetLimits($this->getPage() * $this->limit,
            $this->limit);
    }
  }

  protected function makeSearchString() {
    if ($this->random || $this->index!=$this->index_questions || !$this->searchString) {
      $this->sstr = $this->searchString;
      if ($this->noImages) {
#        $this->sstr .='а -pic';
      }
      return;
    }
    $keys = preg_split('/\s+/', $this->searchString); 
    $this->sstr='@('.implode(',',$this->searchFields).') ';

    $modifiedsearchString = $this->all ? 
       implode(' ', $keys):
       implode(' | ', $keys);
    $this->sstr .= $modifiedsearchString;
  }  
  
  protected function setDateFilter() {  
     if ($this->fromDate) {
        $timestamp1 = mktime(0,0,0,$this->fromDate['month'],
        $this->fromDate['day'], $this->fromDate['year']);
     }

     if ($this->toDate) {
        $timestamp2 = mktime(23,59,59,$this->toDate['month'], 
        $this->toDate['day'], $this->toDate['year']);
     }

     if ($this->fromDate && $this->toDate) {
       $this->sphinx->setFilterRange('playDate', $timestamp1, $timestamp2);
     }
  }

  protected function setAuthorFilter() {
      if ( $this->authorId ) {
        $this->sphinx->setFilter('author_id', array( $this->authorId ) );
      }
  }


  protected function doSearch($sstr, $searchIn, $all, $questionTypes) {  
  
    $this->initSphinx();
    $this->setQuestionTypesFilter();
    $this->setDateFilter();
    $this->setLimits();
    $this->setSortOrder();
    $this->setAuthorFilter();
    $this->setComplexityFilter();
    
    $this->makeSearchString();
    $results = $this->sphinx  -> Query($this->sstr, $this->index);
    if (!$results || !$results['matches']) {
      $this->ids =  array();
      $this -> count = 0;
      $this->words = array();
      $this->success = false;
      $this->total = 0;
    } else {
      $this->ids = array_keys($results['matches']);
      $this->count = $results['total'];
      $this->total = $results['total_found'];
      $this->words = $results['words']?array_keys($results['words']):array();
      $this->success = true;   
    }
  }
  public function setIndex($index) {
    $this->index = $this->sphinx_index."_".$index;
  }
  
  public function getTotal() {
    return $this->total;
  }
  
  protected function makeUnsortedResults() {
    $this->results = array();
    $opts = array(
      'after_match' => '</strong>',
      'before_match' => '<strong>'
    );
    foreach ($this->ids as $nid) {
      $node = node_load($nid);
      $texts = array($node->body);
      $snippet = $this->sphinx->BuildExcerpts($texts, $this->index, $this->searchString, $opts);
      $this->results[] = array(
          'link' => url('node/'.$nid),
          'title' => $node->title,
          'snippet' => implode('<br />', $snippet)
        );
    }
  }

}
