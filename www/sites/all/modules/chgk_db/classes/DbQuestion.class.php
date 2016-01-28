<?php 

class DbQuestion {
  private $question; 
  protected $noAuthorLinks=FALSE;
  private $fieldFactory;
  private $authors;
  protected $incorrect = FALSE;
  protected $tour;
  protected $searchString;
  protected $forSearch = FALSE;
  protected $forPrint;
  protected $noContact = false;
  protected $textId = false;
  public $doNotHideAnswer = false;
  protected $db;
  protected $questionFieldName = 'Вопрос';
  public $noAnswers;
  public $fields;
  private $node = FALSE;
  protected $isEditable = false;
  protected $searchWords = array();
  private $typeMap = array(
    'Я' => 'Jeopardy'
  );
  
  public function __construct($row, $tour = null, $noAnswers=FALSE) {
static $c=0;
if ($c++) {
#print_r(debug_backtrace());
#exit;
}
    if ( is_array($row) ) {
      $row = (object) $row;
    }
    $this->question = $row;
    $this->db = new DbDatabase();
    $this->tour = $tour;
    $this->setNoAnswers($noAnswers);
    if (!$tour && $row->tourId) {
        $this->tour = DbPackage::newFromQuestionRow($row, 'tour');

        $this->tournament = DbPackage::newFromQuestionRow($row,'tournament');
        if (!$this->tour) $this->tour= $this->tournament;
        else     $this->tour->setParent($this->tournament);
    }
    if ( !isset( $this->question->Notices ) ) {
        $this->setNotices();
    }
    $this->fieldFactory = new DbFieldFactory();
    $this->setFields();
  }
  
  public function setNotices() {
     $query = "SELECT * FROM {node} n LEFT JOIN {content_field_issue_2q} f ON n.vid = f.vid 
       LEFT JOIN node_revisions r ON n.vid = r.vid 
       LEFT JOIN content_type_chgk_issue node_data_field_reporter ON n.vid = node_data_field_reporter.vid 
        WHERE (n.type ='chgk_issue') AND (f.field_issue_2q_value = 1 ) 
        AND (n.status = 1) AND (n.title = '".$this->getTextId()."')";
     $res = db_query($query);
     $notices = $this->db->getQuestionNotices( $this->getTextId() );
     $texts = array();
     foreach ($notices as $n) {
          if ( $n->status == 'accepted' ) $this->incorrect = TRUE; 
          $b = trim($n->body)." (".$n->reporter.")";
          $texts[] = check_markup($b, $n->format);

     }
     $this->question->Notices.=implode("<hr/>", $texts);
#     print_r($notices);
        
#    $this->quesiton->notices=$this->getIssues('question_issues_plain');
  }
  
  public function setNoAnswers($flag = TRUE) {
    $this->noAnswers = $flag;
  }

  public function setNoContact($flag = TRUE) {
    $this->noContact = $flag;
  }

  public function setEditable($flag = TRUE) {
    $this->isEditable = $flag;
  }

  protected function getEditor() {
    return chgk_db_get_ajax_editor();
  }

  public function getEditLink( $label = false ) {
    $editor = $this->getEditor();
    if ( !$editor ) {
      return FALSE;
    }
    return $editor->getLink( $this->getTextId(), $label );
  }
  

  
  public function isEditable() {
    return $this->isEditable;
  }
  
  public function getHtml() {
    return theme('chgk_db_question', $this);  
  }

  public function getText() {
    return theme('chgk_db_question_txt', $this);  
  }

  public function getHtmlRandom() {
    return theme('chgk_db_question_random', $this);  
  }

  public function getFb2() {
      return theme('chgk_db_question_fb2', $this);
  }

  public function getImages() {
      $this->images = array();
      foreach ($this->fields as $f) {
          $this->images = array_merge($this->images, $f->getImages());
      }
      return $this->images;
  }
  
  public function getContactUrl() {
    global $_domain;
    global $base_url;
    $questionLink = $this->getQuestionLink();
    if ( !$questionLink ) return false;
    return url('contact/'. $questionLink, array('base_url'=>variable_get('main_site', $base_url), 'absolute'=>true) );
  }

  public function getQuestionXml( $xw ) {
    $xw->startElement('question');
    foreach ($this->question as $key => $value)
    {
      $xw->writeElement($key, $value);
    }

    $xw->endElement();
  }

  public function getField($name) {
    return $this->fields[$name];
  }
  
  public function getFieldValue( $name ) {
    if ( isset ( $this->fields[$name] ) && $this->fields[$name] instanceof DbField ) {
      return $this->fields[$name]->getValue();
    } else {
      return '';
    }
  }
  
  public function getRating() {
    return $this->question->RatingNumber?$this->question->RatingNumber:FALSE;
  }
  
  public function getComplexity() {
    return $this->question->Complexity?$this->question->Complexity:FALSE;
  }
  


  public function getNumber() {
    return $this->question->Number;
  }
  protected function setFields() {
    $this->setQuestionField();
    if ($this->noAnswers) return;
    $fields = array('Answer', 'PassCriteria', 'Comments', 'Sources', 'Authors', 'Notices', 'Rating', 'Notices');
    foreach ($fields as $field) {
      $this->setField($field);
    }
  }
  
  private function setQuestionField() {
    $this->fields['Question'] = $this->fieldFactory->getField(
      'Question', 
      $this->question->Question, 
      $this->question->Number,
      $this
    );
  }

  public function setSearchString($string) {
    foreach ($this->fields as $f) {
//      $f->setSearchString( $searchSting );
    }

    $this->searchString =$string;
  }

  public function getSearchString() {
      return $this->searchString;
  }
  private function setField( $field ) {  
    if ( isset( $this->fields[$field] ) && $this->fields[$field] instanceof DbField ) {
      $this->fields[$field]->setValue( $this->question->{$field} );
    } else {
      $f = $this->fieldFactory->getField( $field, $this->question->{$field}, false, $this );
      if ($f->isEmpty()) return;
      if ($this->searchString) $f->setSearchString( $this->searchString );
      $f->setForPrint($this->forPrint);
      $this->fields[$field] = $f;
    }
  }

  public function getUrl() {
      return url($this->tour->getLink());
  }

  public function getAbsoluteTourUrl() {
      return url($this->tour->getLink(), array('absolute'=>TRUE));
  }

  public function getAbsoluteQuestionUrl() {
      $ql = $this->getQuestionLink();
      if ( !$ql ) return false;
      return url( $ql, array('absolute'=>TRUE));
  }


  public function getQuestionLink() {
    $tour = $this->getTour();
    if ($tour instanceof DbPackageError) return false; 
    return 'question/'. $tour->getId().'/'.$this->getNumber();
  }
  
  public function getTextId() {
    if ( 1|| ! $this->textId ) {
      $this->question->TextId = $this->textId = $this->getTour()->getId().'-'.$this->getNumber();
    }
    return $this->textId;
  }
  public function getSearchTitle() {
      return $this->tour->getFullTitle().'. '.$this->tour->getPlayedAtDate();
  }
  
  public function getMetaForm() {
    global $user;
    module_load_include('inc', 'node', 'node.pages');
    $meta = new DbQuestionMeta( $this );
    $form = $meta->getForm();
    return $form;
  }
  
  public function setForSearch( $flag = TRUE ) {
    $this->forSearch = $flag;
  }
  
  public function isForSearch() {
    return $this->forSearch ;
  }
  
  public function getId() {
    return $this->question->QuestionId;
  }
  
  public function exists() {
    return (bool) $this->question->QuestionId;
  }
  
  public function getTour() {
    if (!$this->tour) {
      $row = $this->db->getTournament($this->question->ParentId);
      $this->tour = DbPackage::newFromRow($row);
    }
    return $this->tour;
  }
  
  public function getFullTitle() {
      return $this->getTour()->getFullTitle().'. '.$this->questionFieldName. ' '.$this->getNumber();
  }

  public function setForPrint($flag= TRUE) {
    $this->forPrint = $flag;
    foreach ($this->fields as $f) {
      $f->setForPrint($this->forPrint);
    }
    if ( $flag) {
      $this->setNoContact();
    }
  }
  
  public function isForPrint() {
    return $this->forPrint;
  }

  public function isNoContact() {
    return !$this->getContactUrl() || $this->noContact;
  }


  public function getXML() {
    return theme('chgk_db_question_xml', $this);    
  }
  
  public function getIssues( $display = 'question_issues' ) {
    if ($this->isNoContact()) return '';
    $view_args = array( $this->getTextId() );
    $display_id = 'question_issues';
    $view = views_get_view('issues');
    if (!empty($view)) {
      $r = $view->execute_display($display_id , $view_args);
    }
  }
  
  public function setNode( $node ) {
    $this->node = $node;
  }
  
  public function getNode(  ) {
    return $this->node;
  }
  
  public function setFieldValue( $name, $value ) {
    $this->question->{$name} = $value;
    $this->setField( $name );
  }
  
  public function isUnsorted() {
    return preg_match( '/^unsorted/', $this->getTextId() );
  }
  
  public function save() {
    if ( $this->isUnsorted() ) {
      module_load_include('class.php', 'chgk_db', 'classes/DbUnsorted');
      $unsorted = new DbUnsorted( $this->node );
      $unsorted -> editQuestion ( $this->getTextId(), $this->getText() );
    } else {
      $db = new DbDatabase();
      $q = $db->getQuestionByTextId( $this->getTextId() );
      if ($q) {
        $db->updateQuestion(  $this->question );
        foreach ((array)$q as $k => $v ) {
          if ( !isset( $this->question->$k ) ) {
            $this->question->$k = $v;
          }
        }
      } else {

        $this->question->QuestionId = $db->addQuestion( $this->question );
      }
    }
  }

  public function getAttachment( $name ) {
      $node = $this->getNode();
      if ( !$node ) return FALSE;
      foreach ( $node->files as $file ) {
        if (
          $file->filename == $name || 
          $file->description == $name ||
          $file->filepath == $name || 
          url($file->filepath) == $name || 
          url($file->filepath, array('absolute'=>TRUE)) == $name
        ) {
          return  $file ;
        }
      }
      return FALSE;
  }

  public function delete() {
    $db = new DbDatabase();
    $db ->deleteQuestion( $this->question->TextId );
  }
  
  public function getAuthors() {
    if ( $this->authors === null ) {
      $db = new DbDatabase();
      $this->authors = array();
      $res = $db->getPersonsByQuestionIdRes($this->getId());
      while ( $row = $res->fetch_object() ) {
        $this->authors[] = new DbPerson( $row );
      }
    }
    return $this->authors;
  }

  public function noAuthorLinks() {
    return $this->noAuthorLinks;
  }

  public function setNoAuthorLinks( $flag = TRUE ) {
    $this->noAuthorLinks = $flag;
  }
  
  public function isIncorrect() {
    return $this->incorrect;
  }

}
