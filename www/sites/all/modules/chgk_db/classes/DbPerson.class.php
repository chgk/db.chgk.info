<?php

class DbPerson {
  const RATING_LINK = 'http://rating.chgk.info/player/%d';
  private $db;
  private $person;
  private $id;
  private $user;
  private static $user_persons = array();

  const QUESTIONS_NUMBER = 10;
  

  public static function get ( $user ) {
    $uid = is_object( $user )? $user->uid : $user;
    if (  isset( self::$user_persons[ $uid ] ) )   {
      return self::$user_persons[ $uid ];
    } else {
      return new self( $user );
    }

  }

  private function loadUser() {
      if ( $uid = db_result(db_query("SELECT v.uid FROM {profile_fields} f
        LEFT JOIN {profile_values} v ON   f.fid=v.fid  WHERE f.name = 'profile_charid' AND v.value='%s'", $this->id )) ) {
        $this->user = user_load($uid);
      }
  }
  
  public function __construct($row) {
    $this->db = new DbDatabase();
    static $i=0;
    if ( is_object( $row ) && isset($row->uid) ) {
      $this->user = $row;
      $this->id = $this->user->profile_charid;
      if ($this->id) $this->loadFromDatabase();
    } elseif ( is_object($row) && $row->Surname ) {
      $this->person = $row;
      $this->setId();
      $this->loadUser();
    } elseif ( is_numeric( $row ) )   {
      $this->user = user_load($row);
      $this->id = $this->user->profile_charid;
      $this->loadFromDatabase();
    } else {
      $this->id = $row;
      $this->loadFromDatabase();
      $this->loadUser();
    }
    if ($this->user) {
      self::$user_persons[$this->user->uid] = $this;
    }
  }
  
  public function exists() {
    return (bool) $this->person;
  }

  private function loadTours() {
    $res = $this->db->editorToursRes($this->id);
    $this->tours = array();
    while ( $tourRow = db_fetch_object($res) ) {
      $this->tours[] =DbPackage::newFromRow($tourRow);
    }
  }
  
  private function loadQuestions() {
    $res = $this->db->authorQuestionsRes( $this->id, self::QUESTIONS_NUMBER );
    $this->questions = array();
    $factory = new DbQuestionFactory();
    while ( $questionRow = db_fetch_object($res) ) {
      $this->questions[] = $factory->getQuestion($questionRow);
    }
  }
  
  public function isCertifiedEditor() {
    return (bool) $this->person->IsCertifiedEditor;
  }

  public function isCertifiedReferee() {
    return (bool) $this->person->IsCertifiedReferee;
  }
  




    private function sortByYear() {
      foreach ($this->tours as $tour) {
        if ( $tour instanceof DbPackageChamp ) {        
          $this->years[$tour->getYear()][$tour->getId()] = $tour;
        } else {
          $this->years[$tour->getParent()->getYear()][$tour->getParent()->getId()] = $tour->getParent();
          $this->children[$tour->getParent()->getId()][] = $tour;
        }
      }

    }

  public function getNick() {
    if ( !$this->user ) return FALSE;

    return $this->user->name;
  }

  public function getUserId() {
    if ( !$this->user ) return FALSE;
    return $this->user->uid;
  }
  

  private function itIsMyPage() {
    global $user;
    if ( !$this->user ) return FALSE;
    return $this->user->uid == $user->uid ;
  }
  public function getHtmlPage() {
    global $user;
    $this->user = user_load($this->user->uid);
    $output = '';
    $addstyle = $this->user->picture? "style=\"min-height:310px;\"":'';
    $output.="<div class='profile' $addstyle>";

    if ( $this->user ){
      $field = db_fetch_object(db_query("SELECT * FROM {profile_fields} f WHERE f.name='profile_picture'"));
      if ( $this->user->picture ) {
        $output.='<div style="float:right">'.theme('user_picture', $this->user)."</div>";
      }
    }

    if ( !$this->user ) {
      $output.="<p class=\"is-it-you\">Если  это Вы, пожалуйста, ";
      if ( ! $user -> uid ) $output.=l( "зарегистрируйтесь", "user/register", array('query'=>array( 'destination'=>request_uri() ) ) )  ." и ";
      $output .= "<span style=\"text-decoration:underline; font-weight:bold\">".l("сообщите", "contact/person/".$this->getId()."/itsme")."</span> нам.</p>";
    } elseif ($this->itIsMyPage() ) {
      $output.="<p class=\"is-it-you\">Это ваша страница. ";
      $ar = array();
      if ( !$this->user->picture ) $ar[] = l('загрузить фотографию', 'user/'.$user->uid."/edit/" );
      if ( !$this->user->profile_info ) $ar[] = l('рассказать о себе', 'user/'.$user->uid."/edit/Личная%20информация");
      if ( $ar ) {
        $output.="Вы можете ".implode(' и ', $ar).".";
      }
      $output.="</p>";
    }
    if ( $this->IsCertifiedEditor() ) {
      $output.= '<p><strong><em>Сертифицированный редактор МАК</em></strong></p>';
    }
    if ( $this->IsCertifiedReferee() ) {
      $output.= '<p><strong><em>Сертифицированный арбитр МАК</em></strong></p>';
    }


    if ( $this->person->RatingId && $this->person->RatingId !='1111111111' ) {
      $output.= '<p>'.l('Страница в рейтинге МАК', sprintf(self::RATING_LINK, $this->person->RatingId), array('attributes'=>array('rel'=>'nofollow')) ).'</p>';
    }




    if ( $this->person->TNumber ) {
      $output.= '<p>Редакторских работ в базе: '.$this->person->TNumber.'</p>';
    }

    if ( $this->person->QNumber ) {
      $output.= '<p>Вопросов в базе: '.l($this->person->QNumber, 'search/questions/author_'.$this->id).'</p>';
    }


    if ( $this->user ) {
      if ( !_contact_user_tab_access( $this->user ) && !$user->uid ) {
        $output.="<p>".l('Представьтесь','user/login').', чтобы оставить сообщение</p>';
      }
    }
    $fields = array('profile_city', 'profile_team', 'profile_info');
    if ($this->user) {
      foreach ( $fields as $field_name ) {
        $field = db_fetch_object(db_query("SELECT * FROM {profile_fields} f WHERE f.name='$field_name'"));
    
#        $output.=profile_view_field($this->user, $field);
        if ($this->user->$field_name) {
          $value = profile_view_field($this->user, $field);
          $output.=theme('user_profile_item', array('#title'=>check_plain($field->title), '#value'=> $value));
        }
      }
    }
    $output.="</div>";
    $key = __METHOD__.":".$this->person->CharId;
    $cached = cache_get($key);
    if ($cached) {
        $add = $cached->data;
    } else {
        $add = $this->getEditorWorksHTML() . $this->getQuestionsHTML();
        cache_set($key, $add, 'cache', 7200);
    }
    if ( $add ) $output.= "$add\n";
    return $output;
  }
  
  public function getQuestionsHTML() {
    $this->loadQuestions();
    if (!$this->questions) return '';
    $results = array();
    foreach ($this->questions as $q) {
      $q->setForSearch();
      $results[] = array(
          'link' => $q->getUrl(),
          'title' => $q->getSearchTitle(),
          'snippet' => $q->getHTML()
        );

/*      $output.="<h4>".$q->getSearchTitle()."</h4>\n";
      $output.=$q->getHTML();*/
    }
    
    $output="<hr />\n<h2>Вопросы</h2>\n";    
    $output.=theme('search_results', $results);
    if ( $this->person->QNumber > self::QUESTIONS_NUMBER ) {
      $output.= '<p>'.l('Все вопросы', 'search/questions/author_'.$this->id).'</p>';
    }

    return $output;    
  }
  
  
  public static function cmpTours( $a, $b ) {
    if ( $a->getNumber() > $b->getNumber() ) {return 1;}
    elseif ( $a->getNumber() < $b->getNumber() ) {return -1;}
    else return 0;
  }
  
  private function getEditorWorksHTML() {
    $this->loadTours();
    $this->sortByYear();

    if ( !is_array( $this->years ) ) {
      return '';
    }
    $output.= "<hr />\n<h2>Редакторские работы</h2>\n";
    foreach ($this->years as $year=>$tours) {
      $output .= "<h3>".$year."</h3>\n";
      $output .= '<ul>';
      foreach ($tours as $t) {
        $output.="<li>".l($t->getFullTitle(),$t->getLink());
        if ( $this->children[$t->getId()] && count($this->children[$t->getId()]) != $t->getToursNumber() ) {
          usort(  $this->children[$t->getId()], array( __CLASS__, 'cmpTours' ) );
          $output.="<ul>\n";
          foreach ($this->children[$t->getId()] as $c ) {
            $output.="<li>";
            $output.=l($c->getTitle(),$c->getLink());
#            $ed = $c->getEditor();
#            if ($ed) $output .= " ($ed)";
            $output.="</li>\n";
          }
          $output.="</ul>\n";
        } else {
#          $ed = $t->getEditor();
#          if ($ed) $output .= " ($ed)";
        }
        $output.="</li>\n";
        $last = $t;
      }
      $output .= '</ul>';
    }
    return $output;

  }

  private function setId() {
      $this->id = $this->person->CharId;
  }
  
  public function getId() {
    return $this->id;
  }
  
  public function getNumericId() {
    return $this->person->Id;
  }

  private function loadFromDatabase() {
      $this->person = $this->db->getPersonById($this->id);
  }

  public function newFromRow($row) {
      return new self($row);
  }

  public function getLink( $name = FALSE ) {
    if( $name === FALSE ) {
      $name = $this->getFullName();
    }
    return l($name,'person/'.$this->id);
  }
  

  public function getFullName() {
      $r = $this->getShortName();
      if ($this->person->City) 
        $r.=" (".$this->person->City.")";
      return $r;
  }

  public function getShortName() {
      return $this->person->Name. " ". $this->person->Surname;
  }

  public function getBreadcrumb() {
    $breadcrumb = array(
      l('Авторы и редакторы','people')
    );
    return $breadcrumb;
  }
  
  public function getToursNumber() {
    return $this->person->TNumber;
  }
  
  public function getQuestionsNumber() {
    return $this->person->QNumber;
  }
  


}
