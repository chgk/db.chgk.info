<?php

class DbAjaxEditor {

  private $useJS;
  private $questionId;
  public function __construct() {
    ctools_include('modal');
    ctools_include('ajax');
    ctools_modal_add_js();
    ctools_add_js("ajax-responder");

  }
  
  public function getLink( $questionId , $label = FALSE) {
    if ( !$label ) $label = 'Редактировать';
     return ctools_modal_text_button( $label , 'chgk_db/nojs/edit_question/'.$questionId, 'Нажмите, чтобы отредактировать'/*, 'ctools-modal-chgkdb_modal'*/);
  }
  
  private function useJS() {
    return $this->useJS;
  }
  
  private function getFormInfo() {
    $form_info = array(
      'id' => 'animals',
      'path' => "ctools_ajax_sample/" . ($js ? 'ajax' : 'nojs') . "/animal/%step",
      'show trail' => TRUE,
      'show back' => TRUE,
      'show cancel' => TRUE,
      'show return' => FALSE,
      'next callback' =>  'ctools_ajax_sample_wizard_next',
      'finish callback' => 'ctools_ajax_sample_wizard_finish',
      'cancel callback' => 'ctools_ajax_sample_wizard_cancel',
     // this controls order, as well as form labels
      'order' => array(
        'start' => t('Choose animal'),
      ),
     // here we map a step to a form id.
      'forms' => array(
        // e.g. this for the step at wombat/create
        'start' => array(
          'form id' => 'ctools_ajax_sample_start'
        ),
      ),
    );
    return $form_info;
  }
  
  private function getQuestionId() {
    return $this->questionId;
  }
  
  private function getQuestion() {
    if ( !$this->question ) {
      ctools_include('object-cache');
      $this->question = ctools_object_cache_get('ctools_ajax_sample', $this->getQuestionId() );
    }
    
    if ( !$this->question ) {

      module_load_include('class.php', 'chgk_db', 'classes/DbQuestionFactory');
      $factory = new DbQuestionFactory();
      $this->question = $factory -> getQuestionFromTextId( $this->getQuestionId() );
    }
    return $this->question;
  }
  
  public function getForm() {

    $q = $this->getQuestion();

    
    $form['question']['question_id'] = array('#type' => 'hidden','#value'=>$q->getTextId() );

    
    $qText = $q->getFieldValue('Question');
    $regexp = '/^\s*\(pic:\s*([^)]+)\)/';
    $attachment = $image = FALSE;
    if ( preg_match($regexp, $qText, $matches) ) {
      $image = $matches[1];
      $attachment = $q->getAttachment($image);
      if ( $attachment ) {
        $qText =preg_replace($regexp, '', $qText);
      }
    }
    
    
    $files = $q->getNode()->files or $files = array();
    $options = array(
      '' => ' -- '
    );

    foreach ( $files as $file ) {
      $options[ $file->fid ] = $file->description;
    }
    
    $form['question']['razdatka'] = array(
      '#title' => 'Раздаточный материал', 
      '#type'  => 'select',
      '#options' => $options,
      '#default_value' => $attachment?$attachment->fid:''
    );

    $form['question']['Question'] = array(
      '#title'    => 'Вопрос',
      '#type'     => 'textarea',
      '#required' => TRUE,
      '#default_value'   => $qText,
      '#weight'    => 1
    );

    $form['question']['Answer'] = array(
      '#title'     => 'Ответ',
      '#type'      => 'textarea',
      '#required'  => TRUE,
      '#default_value'   => $q->getFieldValue('Answer'),
      '#weight'    => 3
    );

    $form['question']['PassCriteria'] = array(
      '#title'     => 'Зачёт',
      '#type'      => 'textfield',
      '#required'  => FALSE,
      '#default_value'   => $q->getFieldValue('PassCriteria'),
      '#weight'    => 4
    );

    $form['question']['Authors'] = array(
      '#title'     => 'Автор',
      '#type'      => 'textfield',
      '#required'  => FALSE,
      '#default_value'   => $q->getFieldValue('Authors'),
      '#weight'    => 5
    );

    $form['question']['Comments'] = array(
      '#title'     => 'Комментарий',
      '#type'      => 'textarea',
      '#required'  => FALSE,
      '#default_value'   => $q->getFieldValue('Comments'),
      '#weight'    => 6
    );

    $form['question']['Sources'] = array(
      '#title'     => 'Источник(и)',
      '#type'      => 'textarea',
      '#required'  => FALSE,
      '#default_value'   => $q->getFieldValue('Sources'),
      '#weight'    => 7
    );

    $form['submit'] = array('#type' => 'submit', '#value' => t('Save'), '#weight' => 19);
    $form['#submit'] = array('chgk_db_question_edit_submit');
    return $form;
  }
  
  public function getFormDialog( $js, $q ) {
    $this->useJS = $js;
    $this->questionId = $q;
    if ( !$this->useJS() ) {
      return drupal_get_form('chgk_db_edit_question_form');
    }

    $modal_style = array(
      'chgkdb_modal' => array(
        'modalOptions' => array(
           'opacity' => .5,
           'background-color' => '#000',
        ),
        'animation' => 'fadeIn',
        'modalTheme' => 'CToolsChgkDbModal',
#        'throbber' => theme('image', array('path' => ctools_image_path('ajax-loader.gif', 'ul_modal'), 'alt' => t('Loading...'), 'title' => t('Loading'))),
      ),
    );
    drupal_add_js($modal_style, 'setting');
    
    $form_state = array(
      'title' => 'Редактирование вопроса',
      'ajax' => $js,
      'question_id' => $this->getQuestionId(),
      'question' => &$q,
    );
    $commands = ctools_modal_form_wrapper('chgk_db_edit_question_form', $form_state);
    if ( !empty($form_state['executed']) ) {
      $commands = array();
      $commands[] = ctools_modal_command_display(t("Sending form"), "Сохраняем...");
      $commands[] = ctools_ajax_command_reload();
    }
    print ctools_ajax_render( $commands );
    exit;
  }
  
  public function submit( &$form, &$form_state ) {
    $this->questionId = $form_state[ 'question_id' ];
    
    $q = $this->getQuestion();
    $fields = array( 'Question', 'Answer', 'PassCriteria', 'Authors', 'Comments', 'Sources' );
    
    if ( $r = $form_state[ 'values' ][ 'razdatka' ] ) {
      $file = $q->getNode()->files[ $r ]; 
      $image_line = '(pic: '.$file->filename.")";
      $form_state[ 'values' ][ 'Question' ] = $image_line."\n".$form_state[ 'values' ][ 'Question' ];
    }
    $row = array();
    foreach ( $fields as $f ) {
      if ( isset( $form_state[ 'values' ][ $f ] ) ) {
        $q->setFieldValue( $f, $form_state[ 'values' ][ $f ] );
      }
    }

    $q->save();
  }
}