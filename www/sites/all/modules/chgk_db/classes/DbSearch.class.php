<?php 


class DbSearch {
  protected $type = false;
  protected $searchFields = false;
  protected $defaultSearchFields =  array('Question', 'Answer', 'PassCriteria', 'Comments');
  protected $defaultFieldsString = 'QAZ';
  protected $defaultLimit = 50;
  protected $defaultRandomLimit = 24;
  protected $xml = FALSE;
  protected $defaultQuestionTypes = array(1,2,3,4,5,6);
  protected $questionTypes = false;
  protected $defaultAllAny = 'all_words';
  protected $searchString = false;
  protected $results = false;
  protected $defaultType = 'sphinx';
  protected $defaultSortBy = 'rel';
  protected $formState;
  protected $form;
  protected $allAny = false;
  protected $engine;
  protected $resultsArray;
  protected $author = false;
  protected $redirectUrlArray = false;
  protected $urlIsParsed = false;
  protected $limit = false;
  protected $sortBy = false;
  protected $withAnswers = false;  
  protected $complexity = false;  
  protected $fromDate = false;
  protected $toDate = false;
  protected $fieldMap = array(
    'Q' => 'Question',
    'A' => 'Answer', 
    'Z' => 'PassCriteria',
    'S' => 'Sources',
    'C' => 'Comments',
    'U' => 'Authors'
  );
  
  protected $questionTypeMap = array(
    1 =>'Что? Где? Когда?', 
    2 =>'Брейн-ринг',
    3 =>'Интернет',
    4 =>'Бескрылка',
    5 =>'Своя игра',
    6=>'Эрудитка'
  );
  
  protected $typeNumberMap = array(
    'Ч'=>1, 
    'Б'=>2,
    'И'=>3,
    'Л'=>4,
    'Я'=>5,
    'Э'=>6

  );

  
  protected $searchTypes = false;

  protected function getEmptySearchStringPage() {
    return $this->getForm();
  }

  protected function getEmptySearchStringPageTournaments() {
    return $this->getTournamentsForm();
  }

  protected function getEmptySearchStringPageUnsorted() {
    return $this->getUnsortedForm();
  }
  
  
  protected function doSearch() {
    $factory = new DbSearchEngineFactory();
    $this->engine = $factory->getEngine($this->getType());
    $this->results = '';
    if ( !@$_GET['page'] && !$this->author && !$this->xml ) {
      $this->engine->setLimit(3);
      $this->engine->setNoPager();
      $this->engine->setIndex('tournaments');
      $tournamentsResultsArray = $this->engine->getResults($this->getSearchString());

      if ($tournamentsResultsArray) {
        $this->results .= '<div style="margin-left:30px">';
        $this->results .='<p><i>Найденные пакеты по запросу "'.$this->getSearchString().'"</i></p><ul>';
        foreach ( $tournamentsResultsArray as $t ) {
          $this->results .= "<li><a href=\"{$t['link']}\">{$t['title']}</a></li>";
        }
        $this->results .= "</ul>";
#        $this->results .= theme('search_results', $tournamentsResultsArray, 'unsorted');
        if ($this->engine->getTotal()>1) {
          $this->results.="<p><a href=\"".url('search/tours/'.$this->getSearchString())."\">Ещё</a></p>";
        }
        $this->results .= '</div>';
      }

     $this->engine->setLimit(1);
      $this->engine->setIndex('unsorted');
      $unsortedResultsArray = $this->engine->getResults($this->getSearchString());
      $this->engine->setNoPager(false);




      if ($unsortedResultsArray) {
        $this->results .= '<div class="unsorted_block">';
        $this->results .='<p><i>Из '.l('необработанного', 'unsorted').'</i></p>';
        $this->results .= theme('search_results', $unsortedResultsArray, 'unsorted');
        if ($this->engine->getTotal()>1) {
          $this->results.="<p><a href=\"".url('search/unsorted/'.$this->getSearchString())."\">Ещё</a></p>";
        }
        $this->results .= '</div>';
      }
    }
    
    $this->engine->setIndex('questions');
    $this->engine->setSearchFields($this->getSearchFields());
    $this->engine->setAllParameter($this->getAllAny()=='all_words');
    $this->engine->setQuestionTypes($this->getQuestionTypes());
    $this->engine->setFromDate($this->getFromDate());
    $this->engine->setToDate($this->getToDate());
    $this->engine->setSortBy( $this->getSortBy());
    $this->engine->setLimit($this->getLimit());
    $this->engine->setXML( $this->xml );
    if ( $this->author) { 
      $this->engine->setAuthorId( $this->author );
    }
    
    $this->resultsArray = $this->engine->getResults($this->getSearchString());
    if ($this->xml) {
      $this->xw->writeElement('total', $this->engine->getTotal());
      foreach ($this->resultsArray  as $q ) {
        $q->getQuestionXML( $this->xw );
      }
    } else {
      $this->results .= theme('search_results', $this->resultsArray, 'questions');
    }
  }

  protected function doTournamentsSearch() {
    $factory = new DbSearchEngineFactory();
    $this->results = '';

    $this->engine = $factory->getEngine('sphinx');

    if ( !@$_GET['page']) {
      $this->engine->setLimit(1);
      $this->engine->setNoPager();
      $this->engine->setIndex('unsorted');
      $unsortedResultsArray = $this->engine->getResults($this->getSearchString());
      if ($unsortedResultsArray) {
        $this->results .= '<div class="unsorted_block">';
        $this->results .='<p><i>Из '.l('необработанного', 'unsorted').'</i></p>';
        $this->results .= theme('search_results', $unsortedResultsArray, 'unsorted');
        if ($this->engine->getTotal()>1) {
          $this->results.="<p><a href=\"".url('search/unsorted/'.$this->getSearchString())."\">Ещё</a></p>";
        }
        $this->results .= '</div>';
      }
    }

    $this->engine->setNoPager(false);
    $this->engine->setLimit(50);
    $this->engine->setIndex('tournaments');
    $this->engine->setFromDate($this->getFromDate()
    );
    $this->engine->setToDate($this->getToDate());
      

    $this->engine->setSortBy( $this->getSortBy());
    $this->resultsArray = $this->engine->getResults($this->getSearchString());


    $this->results .= theme('search_results', $this->resultsArray, 'questions');
  }
  
  protected function getSortBy() {

    if ($this->sortBy === false) {
      $this->parseUrl();
    }
    if ($this->sortBy===false) {
      $this->sortBy = 'rel';
    }
    return $this->sortBy;

    return 'date';
  }

  protected function doUnsortedSearch() {
    $factory = new DbSearchEngineFactory();
    $this->engine = $factory->getEngine('sphinx');
    $this->engine->setIndex('unsorted');
    $this->resultsArray = $this->engine->getResults($this->getSearchString());
    $this->results = theme('search_results', $this->resultsArray, 'questions');
  }

  protected function doRandomSearch($noimages = false) {
 
   $factory = new DbSearchEngineFactory();
    $this->engine = $factory->getEngine('sphinx');
    $this->engine->setIndex('questions');
    $this->engine->setFromDate($this->getFromDate());
    $this->engine->setToDate($this->getToDate());
    $this->engine->setQuestionTypes($this->getQuestionTypes());
    $this->engine->setLimit($this->getRandomLimit());
    $this->engine->setWithAnswers($this->getWithAnswers());
    $this->engine->setComplexity($this->getComplexity());
    $this->engine->setRandomMode();
    if ($noimages) {
      $this->engine->setNoImages();
    }
    $this->engine->setXML( $this->xml );

    if ( $this->author) {
      $this->engine->setAuthorId( $this->author );
    }
    $questions = $this->engine->getResults();
    if ($this->xml) {
      foreach ($questions  as $q ) {
        $q->getQuestionXML( $this->xw );
      }
    } else {
      $this->results = theme('chgk_db_random_results', $questions);
    }
  }

  
  protected function getResults() {
    if ($this->results === false) {
      $this->doSearch();
    }
    return $this->results;
  }

  protected function getSearchString() {
    if ($this->searchString === false) {
      $this->parseUrl();
    }
    return $this->searchString;
  }
  
  protected function getQuestionTypes() {
    if ($this->questionTypes === false) {
      $this->parseUrl();
    }
    if (!$this->questionTypes) {
      $this->questionTypes = $this->defaultQuestionTypes;
    }

    return $this->questionTypes;

  }
  
  protected function getType() {
    if ($this->type === false) {
      $this->parseUrl();
    }
    if ($this->type===false) {
      $this->type = 'sphinx';
    }
    return $this->type;
  }

  protected function getAllAny() {
    if ($this->allAny === false) {
      $this->parseUrl();
    }
    if ($this->allAny===false) {
      $this->allAny = 'all_words';
    }
    return $this->allAny;
  }

  protected function getLimit() {
    if ($this->limit === false) {
      $this->parseUrl();
    }
    if ($this->limit===false) {
      $this->limit = $this->defaultLimit;
    }
    return $this->limit;
  }


  protected function getRandomLimit() {
    if ($this->limit === false) {
      $this->parseUrl();
    }
    if ($this->limit===false) {
      $this->limit = $this->defaultRandomLimit;
    }
    return $this->limit;
  }

  protected function getWithAnswers() {
    if ($this->withAnswers === false) {
      $this->parseUrl();
    }
    if ($this->withAnswers===false) {
      $this->withAnswers = 0;
    }
    return $this->withAnswers;
  }

  protected function getComplexity() {

    if ($this->complexity === false) {

      $this->parseUrl();
    }
    
    if ($this->complexity===false) {
      $this->complexity = 0;
    }
    
    return $this->complexity;
  }

  protected function getAuthor() {
    if ( $this->author === false ) {
      $this->parseUrl();
    }
    if ($this->author===false) $this->author='';
    return $this->author;
  }
  protected function getSearchFields() {
    if ($this->searchFields === false) {
      $this->parseUrl();
    }
    if ($this->searchFields===false) {
      $this->searchFields = $this->defaultSearchFields;
    }
    return $this->searchFields;
  }
  
  protected function isEmptySearchString() {
    return trim($this->getSearchString()) == '';
  }

  protected function getSearchPage() {
    $this->doSearch();
    if ($this->results) {
      return $this->getResultsPage();
    }
    else {
    }
    $output = $this->getForm();
    $output .= $results;
    return $output;
  }
  
  
  public function getRandomPage() {
    $results = '';
    $this->doRandomSearch();
#    $output.=theme('box', t('Search results'), $this->results);    
    $output = $this->getDrupalRandomForm();
    $output .= $this->results;
    return $output;
  }
  
  public function getRandomBlock($number = 1) {
    $results = '';
    $i=0;
    $found = false;
    $this->questionTypes = array(1);
    $this->limit = $number;
    $this->fromDate = array('year'=>2008, 'month'=>1, 'day'=>1);
    while (!$found) {
 
      $this->doRandomSearch($number);
      $found = $i++>1000 || strpos( $this->results, 'embed') ===false && strpos( $this->results, 'img') ===false;
    }
    $output = $this->results;
    return $output;

  }
  
  


  protected function getSearchPageTournaments() {
    $this->doTournamentsSearch();       
    if ($this->results) {
      return $this->getTournamentsResultsPage();
    }
    else {
    }
    $output = $this->getTournamentsForm();
    $output .= $results;
    return $output;
  }

  protected function getSearchPageUnsorted() {
    $this->doUnsortedSearch();       
    if ($this->results) {
      return $this->getUnsortedResultsPage();
    }
    else {
    }
    $output = $this->getUnsortedForm();
    $output .= $results;
    return $output;
  }


  
  
  protected function getResultsPage() {
    $output = '';
//    $output = "<em style ='color:red'>Примерно с начала августа до 15:00 8 августа из-за сбоя результаты поиска отображались неверно.</em>";
//    $output .= "<em style ='color:red'>Если вы в этот период, проверяли вопросы на свеченность, стоит перепроверить</em>";
    $output .= $this->getForm();
    $output.=theme('box', $this->getFoundString(), $this->results);
    return $output;
  }
  
  public function getTitle() {
    if ( $this->personExists() ) {
      return $this->getPerson()->getFullName().'. Поиск вопросов.';
    } else {
      return 'Поиск вопросов';
    }
  }
  
  private function getPerson() {
    if ( $this->person === null ) {
      if ( !$this->getAuthor() ) {
        $this->person = FALSE;
      } else {
        $this->person = new DbPerson( $this->getAuthor() );
        if ( !$this->person->exists() ) {
          $this->person = FALSE;
        }
      }
    }
    return $this->person;
  }
  
  private function personExists() {
    return $this->getPerson() ? TRUE: FALSE;
  }
  
  protected function getFoundString() {
    $hits = $this->engine->getTotal();
    $des = $hits%100;
    $ed = $hits%10;
    if ($des<20&& $des>=10||$ed==0||$ed>=5) {
      $suffix = 'ов';
    } elseif ($ed == 1) {
      $suffix = '';
    } else {
      $suffix = 'а';
    }
               
    $result = '';
    $sstr = $this->getSearchString();
    if ($sstr) {
      $result .= "Результаты поиска строки <em>".$this->getSearchString()."</em>. ";
    }
    
    $result .="$hits  вопрос$suffix";
    return $result;
  }

  protected function getTournamentsResultsPage() {
    $output = $this->getTournamentsForm();
    $output.=theme('box', 'Результаты', $this->results);
    return $output;
  }

  protected function getUnsortedResultsPage() {
    $output = $this->getUnsortedForm();
    $output.=theme('box', 'Результаты', $this->results);
    return $output;
  }
  
  protected function getNoResultsPage() {
    $output = $this->getForm();
    $results = theme('box', 'Ничего не найдено', search_help('search#noresults', drupal_help_arg()));
    return $output;
  }
  
  public function getXML() {
    $this->parseUrl;
    $this->xml = TRUE;
    $this->xw = new xmlWriter();
    $this->xw->openMemory();
    $this->xw->startDocument('1.0','utf-8');
    $this->xw->startElement('search'); 
    if (arg(1) == 'random') {
      $this->doRandomSearch();
    } else {
      $this->doSearch();
    }
    $this->xw->endElement(); 
    $xmlResult = $this->xw->outputMemory(true);   

    $xmlResult = preg_replace('/\>\s*\</', ">\n<", $xmlResult);	
    return $xmlResult;
  }


  
  public function getPage() {
    if (!isset($_POST['form_id'])) {
      $this->parseUrl();
      if ($this->isEmptySearchString() && !$this->personExists() ) {
        return $this->getEmptySearchStringPage();
      } else {
        return $this->getSearchPage();
     }
    }
    return drupal_get_form('chgk_db_search_questions_form');
  }

  public function getTournamentPage() {
    if (!isset($_POST['form_id'])) {
      $this->parseUrl();
      if ($this->isEmptySearchString() && $this->isDefaultTo() && $this->isDefaultFrom()) {
        return $this->getEmptySearchStringPageTournaments();
      } else {
        return $this->getSearchPageTournaments();
     }
    }
    return drupal_get_form('chgk_db_search_tournaments_form');
  }

  public function getUnsortedPage() {
    if (!isset($_POST['form_id'])) {
      $this->parseUrl();
      if ($this->isEmptySearchString()) {
        return $this->getEmptySearchStringPageUnsorted();
      } else {
        return $this->getSearchPageUnsorted();
     }
    }
    return drupal_get_form('chgk_db_search_unsorted_form');
  }

  protected function isDefaultFrom() {
    return $this->joinDate($this->getFromDate()) === 
    $this->joinDate($this->getDefaultFromDate());
  }

  protected function isDefaultTo() {
    return $this->joinDate($this->getToDate()) === 
    $this->joinDate($this->getDefaultToDate());
  }

  
  protected function getForm() {
    return drupal_get_form('chgk_db_search_questions_form');
  }

  protected function getDrupalRandomForm() {
    return drupal_get_form('chgk_db_get_random_form');
  }

  protected function getTournamentsForm() {
    return drupal_get_form('chgk_db_search_tournaments_form');
  }

  protected function getUnsortedForm() {
    return drupal_get_form('chgk_db_search_unsorted_form');
  }

  protected function checkUrlDate($part) {
    $result = false;
    if (preg_match('/^from_(\d{4})\-(\d{2})\-(\d{2})$/',$part, $matches)) {
      $this->fromDate = array('year'=>$matches[1],'month'=>$matches[2], 'day'=>$matches[3]);
      $result = true;
    } elseif (preg_match('/^to_(\d{4})\-(\d{2})\-(\d{2})$/',$part, $matches)) {
      $this->toDate = array('year'=>(int)$matches[1],'month'=>(int)$matches[2], 'day'=>(int)$matches[3]);
      $result = true;
    }
    return $result;
  }
  
  protected function parseUrl() {
    if ($_POST && sizeof($_POST)) {
      return $this->parsePost();
    }
    if ($this->urlIsParsed) return;
    $path = explode('/', $_GET['q']);
    $page = array_shift($path);
    if ($page == 'xml') {
        $this->xml = TRUE;
        $page = array_shift($path);
    }
    if ($page=='search') {
      $page = array_shift($path);
    }
    $keys = array();
    if ($page=='tours') {
      foreach ($path as $part) {
        $this->checkUrlDate($part) ||
        $this->checkUrlSort($part) || 
        $keys[] = $part;
      }
    } elseif($page=='random') {
      foreach ($path as $part) {
        $found = $this->checkUrlDate($part) || $this->checkUrlQuestionTypes($part) || $this->checkUrlLimit($part)||$this->checkUrlWithAnswers($part)
        || $this->checkUrlComplexity($part) || $this->checkUrlAuthor($part);
      }
    } else {
        $this->allAny = 'all_words';
        foreach ($path as $part) {
          if ( $part=='old_style' || $part=='sphinx') {
            $this->type=$part;
          } elseif (preg_match('/^[QAZSCU]+$/',$part)) {
            $this->searchFields = array();
            foreach ( str_split($part)  as $type) {
              $this->searchFields[] = $this->fieldMap[$type];
            }
          } elseif ($this->checkUrlQuestionTypes($part)) {
          } elseif ($this->checkUrlSort  ( $part ) ) {
          } elseif ($this->checkUrlLimit ( $part ) ) {
          } elseif ($part=='any_word' || $part=='all_words') {
            $this->allAny = $part;
          } elseif ($this->checkUrlDate($part)) {
          } elseif ($this->checkUrlAuthor($part)) {
          } else {
            $keys[] = $part;
          }
       }
    }
    $this->searchString = implode('/', $keys);
    $this->urlIsParsed = true;
  }

  protected function checkUrlLimit($part) {
    $result = false;
    if (preg_match('/^limit(\d+)$/',$part, $matches)) {
        $this->limit=(int)$matches[1];
        $result = true;
    }
    return $result;
  }
  
  protected function checkUrlComplexity($part) {
    $result = false;
    if (preg_match('/^complexity(\d+)$/',$part, $matches)) {
        $this->complexity=(int)$matches[1];
        $result = true;
    }
    return $result;

  }

  protected function checkUrlSort($part) {
    $result = false;
    if (preg_match('/^sort_(.*)$/',$part, $matches)) {
        $this->sortBy=$matches[1];
        $result = true;
    }
    return $result;
  }

  protected function checkUrlAuthor($part) {
    $result = false;
    if (preg_match('/^author_(.*)$/',$part, $matches)) {
        $this->author=$matches[1];
        $result = true;
    }
    return $result;
  }

  protected function checkUrlWithAnswers($part) {

    if($part=='answers') {
      $this->withAnswers = 1;
      return true;
    } else {
      return false;
    }
  }
  
  
  protected function checkUrlQuestionTypes($part) {
    $result = false;
    if (preg_match('/^types(\d+)$/',$part, $matches)) {
        $this->questionTypes = array();
        foreach ( str_split($matches[1])  as $type) {
         $this->questionTypes[] = $type;
        }
      $result = true;
    }
    return $result;
  }
  
  protected function parsePost() {
    if (!$this->formState) {
      return;
    }
    if ($this->formState['values']) {
      $values = $this->formState['values'];
    } else {
      $values = $this->formState['post'];
    }
    $this->form_id = @$values['form_id'];
    $this->type = @$values['type'];
    $this->allAny = @$values['all'];
    $this->complexity = @$values['complexity'];
    $this->limit = @$values['limit'];
    $this->author = @$values['author'];

    $this->withAnswers = (int)@$values['answers'];
    if (isset($values['searchin'])) {
      $this->searchFields = array_values(@$values['searchin']);
    } else {
      $this->searchFields = array();
    }
    $this->questionTypes = @$values['question_type'];
    $this->fromDate = @$values['from_date'];
    if (!$this->fromDate) $this->fromDate = @$values['date_interval']['from_date'];
    if (!$this->fromDate) $this->fromDate = @$values['advanced']['date_interval']['from_date'];

    $this->toDate = @$values['to_date'];
    if (!$this->toDate) $this->toDate = @$values['date_interval']['to_date'];
    if (!$this->toDate) $this->toDate = @$values['advanced']['date_interval']['to_date'];

    if (!is_array($this->fromDate) && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $this->fromDate, $matches)) {
        $this->fromDate = array('year' => (int)$matches[0], 'month' => (int)$matches[2], 'day' => (int)$matches[3]);
    }
    if (!is_array($this->toDate) && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $this->toDate, $matches)) {
        $this->toDate = array('year' => (int)$matches[0], 'month' => (int)$matches[2], 'day' => (int)$matches[3]);
    }

    $this->sortBy = @$values['sort_by'];
    
    $this->searchString = trim($values['keys']);
  }
  
  protected function joinDate($date) {
    return sprintf("%04d-%02d-%02d", $date['year'], $date['month'], $date['day']);
  }

  protected function makeRedirectUrl() {
    $path = explode('/', $_GET['q']);
    if ($path[0] == 'search') {
      $this->redirectUrlArray = array($path[0].'/'.$path[1]);
      $page = $path[1];
    } else {
      $this->redirectUrlArray = array($path[0]);
      $page = $path[0];
    }
    
    if ($this->getSearchString()) {
      $this->addToRedirectUrl($this->getSearchString());
    }
    if ($page=='tours') {
      $this->addDatesToRedirectUrl();
      $this->addSortByToRedirectUrl();
      $this->addSearchFieldsToRedirectUrl();
    } elseif ($page == 'random') {
      $this->addDatesToRedirectUrl();
      $this->addWithAnswersToRedirectUrl();
      $this->addQuestionTypesToRedirectUrl();
      $this->addComplexityToRedirectUrl();
      $this->addToRedirectUrl(rand());
      $this->addRandomLimitToRedirectUrl();
    } elseif ($page== 'questions') {
      $this->addAuthorToRedirectUrl();

/*      if ($this->getType() !== $this->defaultType) {
        $this->addToRedirectUrl($this->getType());
      }*/
      if ($this->getAllAny() !== $this->defaultAllAny) {
        $this->addToRedirectUrl($this->getAllAny());
      }

      $this->addQuestionTypesToRedirectUrl();
      $this->addSearchFieldsToRedirectUrl();
      $this->addDatesToRedirectUrl();
      $this->addLimitToRedirectUrl();
    }
    
  }
  

  protected  function addRandomLimitToRedirectUrl() {
    $limit = $this->getLimit();
    if ($limit!=$this->defaultRandomLimit) {
      $this->addToRedirectUrl('limit'.$limit);
    }
  }

  protected  function addComplexityToRedirectUrl() {
    $complexity = $this->getComplexity();
    if ( $complexity!=0 ) {
      $this->addToRedirectUrl('complexity'.$complexity);
    }
  }

  protected  function addAuthorToRedirectUrl() {
    $author = $this->getAuthor();
    if ( $author!='' ) {
      $this->addToRedirectUrl('author_'.$author);
    }
  }

  protected  function addWithAnswersToRedirectUrl() {
    $a = $this->getWithAnswers();
    if ($a) {
      $this->addToRedirectUrl('answers');
    }
  }
  
  protected  function addLimitToRedirectUrl() {
    $limit = $this->getLimit();
    if ($limit!=$this->getDefaultLimit()) {
      $this->addToRedirectUrl('limit'.$limit);
    }
  }

  protected  function addSortByToRedirectUrl() {
    $sort = $this->getSortBy();
    if ($sort!=$this->getDefaultSortBy()) {
      $this->addToRedirectUrl('sort_'.$sort);
    }
  }

  protected function getDefaultLimit() {
    return $this->defaultLimit;
  }
  
  protected function getDefaultSortBy() {
    return $this->defaultSortBy;
  }
  


  protected function addDatesToRedirectUrl() {
      if ($this->joinDate($this->getFromDate()) !== $this->joinDate($this->getDefaultFromDate())) {
        $date = $this->getFromDate();
        $this->addToRedirectUrl(sprintf("from_%4d-%02d-%02d", 
          $date['year'], $date['month'], $date['day']));
      }
      if ($this->joinDate($this->getToDate()) !== $this->joinDate($this->getDefaultToDate())) {
        $date = $this->getToDate();
        $this->addToRedirectUrl(sprintf("to_%4d-%02d-%02d", 
        $date['year'], $date['month'], $date['day']));
      }
  }
  
  protected function getDefaultQuestionTypes() {
    $result = array_keys($this->questionTypeMap);
    sort($result);
    return $result;
  }
  protected function addToRedirectUrl($part) {
    $this->redirectUrlArray[] = $part;
  }
  
  protected function getRedirectUrl() {
    if ($this->redirectUrlArray === false) {
      $this->makeRedirectUrl();
    }
    return implode('/', $this->redirectUrlArray);
  }

  

  protected function addQuestionTypesToRedirectUrl() {
    $questionTypesString = $this->getQuestionTypesString();
    $defaultQuestionTypesString = $this->getQuestionTypesString($this->defaultQuestionTypes);
    if ($questionTypesString && $questionTypesString!=$defaultQuestionTypesString) {
      $this->addToRedirectUrl($questionTypesString);
    }
  }

  protected function getQuestionTypesString($types = false) {
    if ($types === false) $types = $this->getQuestionTypes();
    $str = '';
    foreach ($types as $key=>$value) {
        if ($value) $str.=$value;
    }
    return 'types'.$str;
  }

  protected function addSearchFieldsToRedirectUrl() {
    $fieldsString = $this->getFieldsString();
    $defaultFieldString = $this->getFieldsString($this->defaultSearchFields);
    if ($fieldsString && $fieldsString!=$defaultFieldString) {
      $this->addToRedirectUrl($fieldsString);
    }
  } 
 
  protected function getFieldsString($fields = false) {
    if ($fields === false) $fields = $this->getSearchFields();
    if (!$fields) return array();
    $fields = array_intersect(array_values($this->fieldMap),$fields);
    $fieldsString = '';
    foreach ($this->fieldMap as $key=>$value) {
      if (in_array($value, $fields)) {
        $fieldsString .= $key;
      }
    }
    return $fieldsString;
  }
  
  protected function redirect() {
    $url = $this->getRedirectUrl();
    if (strlen($url)>200) {
      drupal_set_message("Слишком длинная поисковая строка");
      return;
    }
    drupal_redirect_form($this->form, $this->getRedirectUrl());
#    $this->form['#redirect'] = $this->getRedirectUrl();

  }
  
  public function formSubmit($form, &$form_state) {
    $this->form = $form;
    $this->formState = &$form_state;
    $this->parsePost();
    $this->redirect();
#    drupal_redirect_form($form,'search/chgk_db/'.$sstr.'?'."typae=$type");
#    $form_state['redirect'] = 'search/chgk_db/'.$sstr.'?'."type=$type";
  }
  

  public function tournamentsFormSubmit($form, &$form_state) {
    $this->form = $form;
    $this->formState = &$form_state;
    $this->parsePost();
    $this->redirect();
  }
  
  public function randomSubmit($form, &$form_state) {
    $this->form = $form;
    $this->formState = &$form_state;
    $this->parsePost();
    $this->redirect();
  }
  

  
  
  public function getSearchForm(&$form_state) {
    $this->formState = $form_state;
    $form['keys'] = array(
      '#title' => 'Ключевые слова для поиска вопроса',
      '#type' => 'textfield',
      '#size' => "100",
      '#default_value' => $this->getSearchString(),
      '#maxlength'=>"100",
    );

    $form['#submit'] = array('chgk_db_search_submit');

#    $form['#method'] = 'GET';
    $form['advanced'] = array(
      '#type' => 'fieldset',
      '#title' => 'Дополнительные параметры',
      '#collapsible' => true,
      '#collapsed' => TRUE,
      '#attributes' => array('class' => 'search-advanced'),
    );
    
    $form['author'] = array(
      '#type' => 'hidden',
      '#value' => $this->getAuthor() 
    );

/*    $form['advanced']['type'] = array(
        '#type' => 'select',
        '#title' => 'Тип поиска',
        '#prefix' => '<div class="criterion">',
        '#size' => 2,
        '#suffix' => '</div>',
        '#default_value' => $this->getType(),
        '#options' => array('sphinx'=>'С учётом морфологии', 'old_style'=>'По подстроке'),
        '#multiple' => FALSE
      );*/
    $form['advanced']['searchin'] = array(
        '#type' => 'checkboxes',
        '#title' => 'Поля',
        '#prefix' => '<div class="criterion">',
        '#size' => 7,
        '#suffix' => '</div>',
        '#default_value' => $this->getSearchFields(),
        '#options' => array(
          'Question'=>'Вопрос', 
          'Answer'=>'Ответ',
          'PassCriteria'=>'Зачёт',
          'Comments'=>'Комментарий',
          'Sources'=>'Источник(и)',
          'Authors'=>'Автор(ы)'
        ),
        '#multiple' => TRUE
      );

    $form['advanced']['question_type'] = array(
        '#type' => 'checkboxes',
        '#title' => 'Типы вопросов',
        '#prefix' => '<div class="criterion">',
        '#size' => 7,
        '#suffix' => '</div>',
        '#default_value' => $this->getQuestionTypes(),
        '#options' => $this->questionTypeMap,
        '#multiple' => TRUE
      );

    $form['advanced']['all'] = array(
        '#type' => 'select',
        '#title' => 'Искать',
        '#prefix' => '<div class="criterion">',
        '#size' => 2,
        '#default_value' => $this->getAllAny(),
        '#suffix' => '</div>',
        '#options' => array(
          'any_word'=>'Любое слово', 
          'all_words'=>'Все слова',
        ),
        '#multiple' => FALSE
      );

    $form['advanced']['limit'] = array(
      '#title'     => 'По',
      '#required' =>FALSE,
      '#size' => 3,
      '#prefix' => '<div class="criterion">',
      '#suffix' => '</div>',
      '#type'      => 'textfield',
      '#default_value' =>$this->getLimit()
    );

    $form['advanced']['date_interval'] = array(
      '#prefix' => '<div class="criterion">',
      '#suffix' => '</div>',
      '#type' => 'fieldset',
      '#title' => 'Интервал',
      '#collapsible' => FALSE,
    );


    $form['advanced']['date_interval']['from_date'] = array(
      '#title'     => '',
      '#date_format' => 'd-m-Y',
      '#required' =>FALSE,
      '#type'      => 'date_select',
      '#formatter' => 'iso',
      '#default_value'=> $this->joinDate($this->getFromDate()),
      '#date_year_range' => (1990-date('Y')).':0',
    );
    
    $form['advanced']['date_interval']['to_date'] = array(
      '#title'     => '',
      '#date_format' => 'd-m-Y',
      '#required' =>FALSE,
      '#type'      => 'date_select',
      '#formatter' => 'iso',
      '#default_value' =>$this->joinDate($this->getToDate()),
#      '#formatter' => 'iso',
      '#date_year_range' => (1990-date('Y')).':0',
      '#settings' => array('maxdate'=>1)
    );

    $form['advanced']['sort_by'] = array(
        '#type' => 'select',
        '#title' => 'Сортировка',
        '#prefix' => '<div class="criterion">',
        '#suffix' => '</div>',
        '#default_value' => $this->getSortBy(),
        '#options' => array('rel'=>'Релевантность', 'date'=>'Дата'),
        '#multiple' => FALSE
      );



    $form['submit'] = array('#type' => 'submit', '#value' => 'Искать');

    return $form;
  }

  protected function getFromDate() {
    if ($this->fromDate === false) {
      $this->parseUrl();
    }

    if ($this->fromDate===false || !$this->fromDate['year'] || !$this->fromDate['month'] || !$this->fromDate['day']) {
      $this->fromDate = $this->getDefaultFromDate();
    }    
    return $this->fromDate;
  }
  
  protected function getDefaultFromDate() {
    $date = array('year'=>1990, 'day'=>1, 'month'=>1);
    return $date;
  }
  
  protected function getDefaultToDate() {
    $date = array('day' => format_date(time(), 'custom', 'j'),
                            'month' => format_date(time(), 'custom', 'n'),
                            'year' => format_date(time(), 'custom', 'Y'));

    return $date;
  }

  
  protected function getToDate() {
    if ($this->toDate === false) {
      $this->parseUrl();
    }
    if ($this->toDate===false) {
      $this->toDate = $this->getDefaultToDate();
    }
    return $this->toDate;

  }

  


  public function getRandomForm(&$form_state) {
    $this->formState = $form_state;
    $form = array();
    $form['advanced']['question_type'] = array(
        '#type' => 'checkboxes',
        '#title' => 'Типы вопросов',
        '#prefix' => '<div class="criterion">',
        '#size' => 7,
        '#suffix' => '</div>',
        '#default_value' => $this->getQuestionTypes(),
        '#options' => $this->questionTypeMap,
        '#multiple' => TRUE
      );

    $from_date = array('year'=>1900, 'day'=>1, 'month'=>1);
    $form['date_interval'] = array(
      '#prefix' => '<div class="criterion">',
      '#suffix' => '</div>',
      '#type' => 'fieldset',
      '#title' => 'Интервал',
      '#collapsible' => FALSE,
    );

    $form['date_interval']['from_date'] = array(
      '#title'     => '',
      '#date_format' => 'd-m-Y',
      '#required' =>FALSE,
      '#type'      => 'date_select',
      '#formatter' => 'iso',
      '#default_value'=> $this->joinDate($this->getFromDate()),
      '#date_year_range' => (1990-date('Y')).':0',
    );
    
    $form['date_interval']['to_date'] = array(
      '#title'     => '',
      '#date_format' => 'd-m-Y',
      '#required' =>FALSE,
      '#type'      => 'date_select',
      '#formatter' => 'iso',
      '#default_value' =>$this->joinDate($this->getToDate()),
#      '#formatter' => 'iso',
      '#date_year_range' => (1990-date('Y')).':0',
      '#settings' => array('maxdate'=>1)
    );
    
    $form['advanced']['complexity'] = array(
      '#title'     => 'Сложность пакета',
      '#required' =>FALSE,
      '#size' => 1,
      '#prefix' => '<div class="criterion">',
      '#suffix' => '</div>',
      '#type'      => 'select',
      '#default_value' => $this->getComplexity(),
      '#options' => array(0 => ' -- ', 1=>'Очень простой', 2=>'Простой', '3'=>'Средний', 4=>'Сложный', 5=>'Очень сложный'),
    );

    $form['advanced']['limit'] = array(
      '#title'     => 'По',
      '#required' =>FALSE,
      '#size' => 3,
      '#prefix' => '<div class="criterion">',
      '#suffix' => '</div>',
      '#type'      => 'textfield',
      '#default_value' =>$this->getRandomLimit()
    );

    $form['advanced']['answers'] = array(
      '#title'     => 'С ответами',
      '#required' =>FALSE,
      '#size' => 3,
      '#prefix' => '<div class="criterion">',
      '#suffix' => '</div>',
      '#type'      => 'checkbox',
      '#default_value' =>$this->getWithAnswers()
    );



    $form['submit'] = array(
      '#type' => 'submit', 
      '#value' => 'Получить пакет',
      '#suffix' => '<div style="clear:all"></div>',
    );

    $form['#submit'] = array('chgk_db_search_submit');
    return $form;
  }

  public function getSearchTournamentsForm(&$form_state) {
    $this->formState = $form_state;
    $form = array();
    $form['keys'] = array(
      '#title' => 'Ключевые слова для поиска турнира',
      '#type' => 'textfield',
      '#size' => "100",
      '#maxlength'=>"100",
      '#default_value' => $this->getSearchString()
    );

/*    $form['advanced']['question_type'] = array(
        '#type' => 'checkboxes',
        '#title' => 'Типы вопросов',
        '#prefix' => '<div class="criterion">',
        '#size' => 7,
        '#suffix' => '</div>',
        '#default_value' => $this->getQuestionTypes(),
        '#options' => $this->questionTypeMap,
        '#multiple' => TRUE
      );
*/

    $from_date = array('year'=>1900, 'day'=>1, 'month'=>1);
    $form['date_interval'] = array(
      '#prefix' => '<div class="criterion">',
      '#suffix' => '</div>',
      '#type' => 'fieldset',
      '#title' => 'Интервал',
      '#collapsible' => FALSE,
    );

    $form['date_interval']['from_date'] = array(
      '#title'     => '',
      '#date_format' => 'd-m-Y',
      '#required' =>FALSE,
      '#type'      => 'date_select',
      '#formatter' => 'iso',
      '#default_value'=> $this->joinDate($this->getFromDate()),
      '#date_year_range' => (1990-date('Y')).':0',
    );
    
    $form['date_interval']['to_date'] = array(
      '#title'     => '',
      '#date_format' => 'd-m-Y',
      '#required' =>FALSE,
      '#type'      => 'date_select',
      '#formatter' => 'iso',
      '#default_value' =>$this->joinDate($this->getToDate()),
#      '#formatter' => 'iso',
      '#date_year_range' => (1990-date('Y')).':0',
      '#settings' => array('maxdate'=>1)
    );
    

    $form['advanced']['sort_by'] = array(
        '#type' => 'select',
        '#title' => 'Сортировка',
        '#prefix' => '<div class="criterion">',
        '#suffix' => '</div>',
        '#default_value' => $this->getSortBy(),
        '#options' => array('rel'=>'Релевантность', 'date'=>'Дата'),
        '#multiple' => FALSE
      );


    $form['submit'] = array(
      '#type' => 'submit', 
      '#value' => 'Искать',
      '#attributes'=> array('class'=>'random_submit'),

    );

    $form['#submit'] = array('chgk_db_search_submit');
    return $form;
  }

  public function getSearchUnsortedForm(&$form_state) {
    $this->formState = $form_state;
    $form = array();
    $form['keys'] = array(
      '#title' => 'Ключевые слова для поиска',
      '#type' => 'textfield',
      '#size' => "100",
      '#maxlength'=>"100",
      '#default_value' => $this->getSearchString()
    );


    $form['submit'] = array(
      '#type' => 'submit', 
      '#value' => 'Искать',
    );

    $form['#submit'] = array('chgk_db_search_submit');
    return $form;
  }


}
