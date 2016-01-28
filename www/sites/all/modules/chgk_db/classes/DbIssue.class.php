<?php

class DbIssue{
 
  const CATEGORY_FIELD = 'field_issue_category';
   
  protected $question = NULL;
 
  public function __construct( &$node, $teaser = null, $page =  null ) {
    $this->node = $node;
    $this->teaser = $teaser;
    $this->page = $page;
    $node->instance = $this;
  }
  
  protected function getQuestion() {
    if ( $this->question !== NULL ) return $this->question;
    $factory = new DbQuestionFactory();
    $this->question = $factory->getQuestionFromTextId($this->node->title);
    if ($this->question) {
      $this->question->setForSearch();
      $this->question->setNoContact();
    }
    return $this->question;
  }
  
  public function view() {
    if ( !$this->isLinkedToQuestion() ) {
      return;
    }
    if ($this->page) {
      drupal_set_title( $this->getTitle());
    }
  }
  
  public function update() {
    $this->oldnode = node_load ( $this->node->nid );
    $oldStatus = $this->oldnode->field_issue_status[0]['value'];
    $newStatus = $this->node->field_issue_status[0]['value'];
    
    $attached = $this->node->field_issue_2q[0]['value'];
    if ( $oldStatus=='new' && $oldStatus != $newStatus ) {
      $this->mailStatusUpdate( $newStatus, $attached );
    }
  }
  
  public function mailStatusUpdate( $status, $attached ) {
    $mail = $this->node->field_email[0]['value'];
    $v['subject'] = 'Ваше замечание обработано';
    $v['body'] = "Добрый день!\n\n";
    
    $v['body'].= "Вы писали нам про ошибку в вопросе %1\$s. ";
    
    if ( $status == 'resolved' ) {
      $v['body'] .= "Эта ошибка исправлена. В течение 24 часов изменения будут отображены.\n\n";
    } elseif ( $status == 'accepted' ) {
      $v['body'] .= "Мы признаём эту ошибку. \n\n";
    } elseif ( $status == 'rejected' ) {
      $v['body'] .= "\n\nНам кажется, что этой ошибки в вопросе нет (или ваше сообщение не было сообщением об ошибке).\n\n";
    }
    
    if ( $attached ) {
    $v['body'] .= 'Ваше замечание прикреплено к вопросу.'."\n\n";
    }
    
    
    $v['body'] .= "Постоянный адрес вашего сообщения -- %2\$s.";
    
    if ( $this->oldnode->comment_count ) {
      $v['body'] .= " По этому адресу вы можете прочитать комментарии";
    }

    $v['body'] .= "\n\nСпасибо!\n\n-- \nРоман Семизаров\n";

    $q = $this->getQuestion();
    $v['body'] = sprintf( $v['body'], 
      $q->getAbsoluteQuestionUrl(),
      url('node/'.$this->node->nid, array( 'absolute' => TRUE ) ) 
    );
    drupal_mail('chgk_db', 'issue_status_updated', $mail, language_default(), $v );
  }
  
  public function isQuestionView() {
      $result = isset($this->node->view) && $this->node->view->current_display == 'question_issues';
      return $result;
  }
  
  public function getTitle() {
    $question = $this->getQuestion();
    if ($question) {
      if ( $this->isQuestionView() ) {
        return $this->getCategoryText();
      } else {
        return $this->getCategoryText().'. '.$this->getQuestion()->getFullTitle();
      }
    } else 
      return $this->node->title;
  }
  
  public function getQuestionHtml() {
    if ( !$this->isLinkedToQuestion() ) {
      return '';
    } else {
      return $this->question->getHtml();
    }
  }
  
  
  
  protected function getCategoryId() {
    return $this->getFieldValue( self::CATEGORY_FIELD );
  }
  
  private function fetchCategory() {
    $result =  db_query("SELECT * FROM {contact} WHERE cid = ".$this->getCategoryId());
    $this->category = db_fetch_object($result);
  }
//  public function 
  public function getCategoryText() {
    if (!is_object($this->category)) {
      $this->fetchCategory();
    }
    return $this->category->category;

  }
  
  public function isLinkedToQuestion() {
    return (bool)$this->getQuestion();
  }
  
  protected function getFieldValue( $name ) {

    $field = $this->node->$name;
    $result=$field[0]['value'];
    return $result;
  }
  
  

}
