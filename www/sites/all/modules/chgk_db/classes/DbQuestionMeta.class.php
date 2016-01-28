<?php

class DbQuestionMeta{

  private $textId;
  
  public function __construct( $q = NULL ) {
	if ( is_object($q) ) {
	    if ( $q instanceof DbQuestion ) {
		$this->question = $q;
	    } else {
		$this->node = $q;
	    }
	} elseif ( is_string( $q ) ) {
	    $this->textId = $q;
	}
    }
    
    
    private function canEdit() {
      $node = $this->getNode();
      if ($node) {
        return node_access('update', $node );
      } else {
        return node_access('create', $node );
      }
    }
    
    public function getForm() {
      $node = $this->getNode();
      if ( !$this->canEdit() ) {
        return '';
      }
      if ( !$node ) {
        $node = array('type' => 'question_meta', 'uid' => $user->uid, 'name' => $user->name);
      }
      $form =  drupal_get_form('question_meta_node_form', $node );
      return $form;
    }
    
    public function getNode() {
      if ( $this->node == NULL ) {
        if ( arg(0) == 'node' && is_numeric(arg(1)) ) {
          $this->node = node_load( arg(1) );
        } else {
          $textId = $this->getTextId();
          if ( $textId ) {
            $this->node = node_load( array( 'title' => $textId ) );
            if ( !$this->node ) {
              $this->node = new stdClass();
              $this->node->type = 'question_meta';
              $this->node = node_prepare($this->node);
            }
          }
        }
      }
      return $this->node;
    }
    
    public function form_alter( &$form ) {
      $this->form = &$form;
      $this->textId = $form['title']['#default_value'] = $this->getTextId();
      $q = $this->getQuestion();
      if (!$q) return;

      $form['body_field']['#access'] = FALSE;
      $form['menu']['#access'] = FALSE;
      $form['revision_information']['#access'] = FALSE;
      $form['author']['#access'] = FALSE;
      $form['options']['#access'] = FALSE;
      $form['path']['#access'] = FALSE; 
      $form['title']['#type'] = 'hidden';


      if ( arg(3) && !$form['nid']['#value'] ) {
#    	    if ( !$form['title']['#default_value']) $form['title']['#default_value'] = arg(3);
      } 
        
      

        if ( $this->isNodeFormPage() ) {
    	    $form['title']['#prefix'] = '<h1>'.$textId."</h1>";
    	    $form['title']['#prefix'] = $q->getHtml();
            drupal_set_title($q->getSearchTitle());
    	}
    }
    
    protected function isNodeFormPage() {
    	    return 
    		( arg(0) == 'node' && arg(1) == 'add' && arg(2) == 'question-meta' && arg(3) ) 
    		    || 
    		( arg(0) == 'node' && arg(2) == 'edit'  ) ;
    }
    
    protected function getQuestion() {
      if ( $this->question !== NULL ) return $this->question;
      if ( !$this->getTextId() ) return ($this->question = FALSE);
      
      $factory = new DbQuestionFactory();
      $this->question = $factory->getQuestionFromTextId($this->getTextId());
      if ($this->question) {
        $this->question->setForSearch();
        $this->question->setNoContact();
      }
      return $this->question;
    }
    
    protected function getTextId() {
      if ( $this->textId===null ) {
        if ( arg(0) == 'node' && arg(1) == 'add' && arg(2) == 'question-meta'  )  {
          if ( !arg(3) ) return '';
          $this->textId = arg(3);
        } elseif ( arg(0) == 'question' ) {
          $this->textId = arg(1).'-'.arg(2);
        } else {
          $node = $this->getNode();
          if ( $node && !empty($node->nid) ) {
            $this->textId = $node->title;
          }
        }
      }
      return $this->textId;
    }


}
