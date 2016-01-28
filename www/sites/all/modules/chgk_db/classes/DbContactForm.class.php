<?php

class DbContactForm {
  const CATEGORY_NAME = 'Ошибка в вопросе';
  const PAGE_TITLE = 'Ошибка в вопросе';
  const AUTHOR_CATEGORY = 4;

  private $category_data = NULL;

  private $question = NULL;
  
  private $categories = NULL;
  
  private $default_category = NULL;
  
  private $itsme;
  
  public function __construct( &$form, &$form_state ) {
    $this->form = &$form;
    $this->form_state = &$form_state;
  }
  
  private function fetchCategoryData() {
    $result =  db_query("SELECT * FROM {contact} WHERE category LIKE '%вопрос%'");
    while ($category = db_fetch_object($result)) {
      $this->categories[$category->cid] = $category->category;
      if ($category->selected) {
        $this->default_category = $category->cid;
      }
    }
  }

  private function getCategoryId() {
      if ( $this->category_data === NULL ) {
        $this->fetchCategoryData();
      }
      return isset( $this->category_data[ 'cid' ] ) ?  $this->category_data[ 'cid' ] : FALSE;
  }
  
  private function getCategories() {
      if ( $this->categories === NULL ) {
        $this->fetchCategoryData();
      }

      return $this->categories;
  }

  private function getDefaultCategory() {
      if ( $this->categories === NULL ) {
        $this->fetchCategoryData();
      }
      return $this->defaultCategory;
  }  
  
  
  private function getQuestion() {
    if ( $this->question !== NULL ) return $this->question;
    

  }
  
  public function parseURL() {
    $this->question = FALSE;
    if ( arg(1) == 'person' )  {
      if ( !arg(2) ) return FALSE;
      $this->person = new DbPerson( arg(2) );
      $this -> itsme = arg(3) == 'itsme';
      return TRUE;    
    } elseif ( arg(1) == 'question' ) {
      if ( !arg(3) ) return FALSE;
      $tournament = DbPackage::newFromDb( arg(2) );
      if ( !$tournament ) return FALSE;
      $this->question = $tournament->getQuestion( arg(3) );
      $this->question->setForSearch();
      return TRUE;
    } else {
      return FALSE;
    }
  }
  
  public function submit() {
    global $user;
    if ( !$this->parseURL() ) return;
    $this->question->setForPrint();
    
    $node = new stdClass;
    $node->type = 'chgk_issue';
    $node->body = $this->form_state['values']['message'];
    if ($this->question) {
      $node->title = $this->question->getTextId();
    }
    $node->field_email[]['value'] = $this->form_state['values']['mail'];
    $node->field_issue_status[]['value'] = 'new';
    $node->field_issue_category[]['value'] = $this->form_state['values']['cid'];
    $node->field_reporter[]['value'] = $this->form_state['values']['name'];
    $node->uid = $user->uid;
    $node->comment = 2;
    node_save($node);
    
    $this->form_state['values']['message'] = $this->question->getText()."\n====\n\n". $this->form_state['values']['message'];
  }
  
  public function alter() {
    if ( !$this->parseURL() ) return;

    if ( $this->question ) {
      drupal_set_title(self::PAGE_TITLE);
      $q = $this->getQuestion();
      $this->form['contact_information'] = array(
        '#type' => 'markup',
        '#value' => $q->getHTML()//'Введите информацию об ошибке и она будет выслана хранителям'
      );
      $this->form['subject'] = array('#type' => 'hidden',
        '#value' => arg(2)."/".arg(3)
      );

      $this->form['cid'] = array('#type' => 'select',
        '#title' => 'Категория',
        '#default_value' => $default_category,
        '#options' => $this->getCategories(),
        '#required' => TRUE,
      );

      array_unshift($this->form['#submit'], 'chgk_db_contact_submit');
    } elseif ( $this->person ) {
      drupal_set_title('Сообщение об авторе');
      $this->form['cid'] = array('#type' => 'hidden',
        '#value' => self::AUTHOR_CATEGORY,
      );

      if ( $this->itsme ) {
        $this->form['subject']['#value'] = $this->person->getFullName()." (".$this->person->getId().") - это я";
        $this->form['message']['#value'] = "Я зарегистрировался на сайте, указав настоящие имя и фамилию. ".
        "На странице ".url("person/".$this->person->getId(), array('absolute'=>TRUE ) ). " именно мои турниры и/или вопросы.";
      } else {
        $this->form['subject']['#value'] = $this->person->getFullName();
      }
    }
  }
}