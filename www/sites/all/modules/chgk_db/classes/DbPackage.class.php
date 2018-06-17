<?php

require_once(dirname(__FILE__)."/DbPackage/DbPackageGroup.class.php");
require_once(dirname(__FILE__)."/DbPackage/DbPackageTour.class.php");
require_once(dirname(__FILE__)."/DbPackage/DbPackageChamp.class.php");
require_once(dirname(__FILE__)."/DbPackage/DbPackageRoot.class.php");
require_once(dirname(__FILE__)."/DbPackage/DbPackageError.class.php");

class DbPackage {

  protected $tour;
  protected $db;
  protected $id;
  protected $children = FALSE;
  protected $parent = FALSE;
  protected $isForPrint;
  protected $isForSearch = false;
  public $noAnswers = FALSE;
  protected $editors = null;
  const NOSPACES = TRUE;
  const NEWS_BASE = 'http://news.chgk.info/';
  const RATING_URL = 'http://ratingnew.chgk.info/tournaments.php?displaytournament=%ID';
  private static $tourCache = array();

  public function __construct($row, $parent = FALSE) {
    if ( is_array($row) ) {
      $row = (object) $row;
    }
    $this->setParent($parent);
    $this->db = new DbDatabase();
    if (is_object($row)) {
      $this->tour = $row;
      $this->setId();
    } else {
      $this->id = $row;
      $this->loadFromDatabase();

    }
  }    

  public function setNoAnswers($flag = TRUE) {
    $this->noAnswers = $flag;
  }
  
  public function getFileName() {
    return $this->tour->FileName;
  }
  
  public function setId( $id = FALSE ) {
    if ( $id ) {
      $this->id = $this->tour->TextId = $id; 
      return;
    }
    $this->id = $this->tour->TextId ;
    if ( !$this->id) $this->id = $this->tour->FileName;

    if (!$this->id) {
      $parent = $this->getParent();
      if ($parent) $this->id = $parent->getId().'.'.$this->tour->Number;
    }
  }

  public function getId() {
    return $this->id;
  }

  public static function newRoot() {
      return new DbPackageRoot($row);
  }

  public static function newFromRow($row) {
    if ( is_array($row) ) {
      $row = (object) $row;
    }

    if (!$row) {
      return new DbPackageError($id);
    } elseif ($row->Type == 'Г' ) {
      return new DbPackageGroup($row);
    } elseif ($row->Type == 'Ч' ) {
      return new DbPackageChamp($row);
    } elseif ($row->Type == 'Т' ) {
      return new DbPackageTour($row);
    }
  }

  public static function newFromQuestionRow($row, $prefix) {
        $tour = new stdClass();
        $tour->Id = $row->{"{$prefix}Id"};
        $tour->Title = $row->{"{$prefix}Title"};
        $tour->FileName = $row->{"{$prefix}FileName"};
        $tour->Type = $row->{"{$prefix}Type"};
        if (isset($row->{"{$prefix}PlayedAt"})) $tour->PlayedAt = $row->{"{$prefix}PlayedAt"};
        if (isset($row->{"{$prefix}PlayedAt2"})) $tour->PlayedAt2 = $row->{"{$prefix}PlayedAt2"};
        if (!$tour->FileName) {
          return false;
        }

        return self::newFromRow($tour);
  }
  
  public static function newFromDb($id) { 
    if (self::$tourCache[$id]) return self::$tourCache[$id];    
    $db = new DbDatabase;
    $row = $db->getTournament($id);
    $result = self::newFromRow($row);
     self::$tourCache[$id] = $result;
    return $result;
  }

  public function loadFromDatabase() {
    $this->tour = $this->db->getTournament($this->id);
  }
  public function getAll() {
    return false;
  }
  
  protected function getDbId() {
    return $this->tour->Id;
  }
  
  public function getTitle() {
     return $this->tour->Title;
  }

  public function getFullTitle() {
     return $this->getTitle();
  }

  
  public function getLongTitle() {
    return $this->getTitle();
  }
  public function getInfo() {
    $info = $this->tour->Info;
    $info = preg_replace('/\(att:\s*(.*?)\|(.*)\)/','<a href="/attachments/$1">$2</a>',$info);
#    $info = preg_replace('/(\s+)-+(\s+)/','\1&mdash;$2', $info);

    return $info;
  }

  public function getInfoFb2() {
    $info = $this->tour->Info;
#    $info = preg_replace('/(\s+)-+(\s+)/','\1&mdash;$2', $info);
    $info = html_entity_decode($info, ENT_COMPAT, 'UTF-8');
    $info = htmlspecialchars($info, ENT_NOQUOTES, 'UTF-8');

    return $info;
  }
  
  public function hasEditor() {
    return $this->tour->Editors?TRUE:FALSE;
  }
  public function hasInfo() {
    return $this->tour->Info?TRUE:FALSE;
  }

  public function getEditor() {
    return $this->tour->Editors;
  }

  protected function getEditorPreHtml() {
    $ed = $this->tour->Editors;
    if (!$ed)  {
      return '';
    }
    $ed = preg_replace('/(\s+)-+(\s+)/','\1&mdash;$2', $ed);
    
    if (preg_match('/\,/', $ed))  {
      $ob = 'Редакторы';
    } else {
      $ob = 'Редактор';
    }

  }
  public function getEditorHtml() {
    $ed = $this->tour->Editors;
    if (!$ed)  {
      return '';
    }
#    $ed = preg_replace('/(\s+)-+(\s+)/','\1&mdash;$2', $ed);
    
    if (preg_match('/\,/', $ed))  {
      $ob = 'Редакторы';
    } else {
      $ob = 'Редактор';
    }
    $t = new DbPersonInHTML();
    
    $editors = $this->getEditors();
    
    $ed = $t->makeLinks($editors, $ed);
    
    return "<strong>$ob:</strong> $ed";
  }
  


  public function getEditorFb2() {
    $ed = $this->tour->Editors;
    if (!$ed)  {
      return '';
    }
#    $ed = preg_replace('/(\s+)-+(\s+)/','\1&mdash;$2', $ed);
    $ed = html_entity_decode($ed, ENT_COMPAT, 'UTF-8');
    $ed = htmlspecialchars($ed, ENT_NOQUOTES, 'UTF-8');
    
    if (preg_match('/\,/', $ed))  {
      $ob = 'Редакторы';
    } else {
      $ob = 'Редактор';
    }
    return "<strong>$ob:</strong> $ed";

  }
  

  public function getFb2() {
    $this->getAll();
    return theme('chgk_db_fb2', $this);
  }

  public function loadTree() {      
      foreach ($this->getChildren() as $child) {
          $child->loadTree();
      }
  }

  public function getChildren() {
       if ($this->children === FALSE ) {
           $this->loadChildren();
       }
       return $this->children;
   }

  public function loadChildren() {
      $this->children = array();
      $res = $this->db->getChildrenRes($this->getDbId());
      while ($row = $this->db->fetch_row($res)) {
        $this->children[] = DbPackage::newFromRow($row, $this);
      }
    }


  public function getImagesBinaries() {
      $images=$this->getImages();
      $result = '';
      foreach ($images as $i) {
        $name = realpath("images/db/$i");
        if ( is_file($name) ) {
          $file = fopen($name,'rb');
        } else {
          $imageDomain=variable_get('image_domain', $_SERVER['HTTP_HOST']);
          $file = fopen( "https://$imageDomain/images/db/$i", 'rb' );
          if (!$file) {
            drupal_set_message("�� ���������� ������� ����������� $i", 'warning');
          }
        }

        if ( !$file ) continue;

        $str_file=fread($file,filesize($name));

        if ( !$str_file ) {
          fclose( $file );
          continue;
        }

        $result.="<binary content-type='image/jpeg' id='_$i'>";
        $result.=base64_encode($str_file);
        $result.="</binary>";
        fclose($file);
      }
      return $result;
  }

  protected function getEditorsForList() {
    $ed = $this->db->getEditors($this->tour->Id);
    if ($ed) {
      $result = array();    
      foreach ($ed as $editor) {
        $result[] = $editor->Name." ".$editor->Surname;
      }
      return implode(', ',$result);
    } else {
        return '';
    }
  }

  public function getHtmlLinkForList( $withDate = true, $attr = array() ) {
      $date = $this->getPlayedAtDate();
      if ($withDate && $date) {
        $result = l($this->getTitle().'. ', $this->getLink(), array('attributes'=>$attr) ). $this->getPlayedAtDate();
      } else {
        $result = l($this->getTitle(), $this->getLink(), array('attributes'=>$attr));
      }
      return $result;
  }

  public function getToursList() {
    $tours = $this->tour->tours;
    if (!$tours) return false;
    $a = explode('::', $tours);
    if (sizeof($a) == 1) return false;
    $thisLink = $this->getLink();
    $i=0;
    foreach ($a as $t) {
      $links[] = '<a href="'.$thisLink.'.'.(++$i).'">'.str_replace(' ','&nbsp;',$t).'</a>';
    }
    return implode(', ', $links);
  }

  public function getHtmlLinkForBreadrumb() {
      return l($this->getTitle(), $this->getLink());
  }
 
  public function getLink() {
      $this->setId();
      return "tour/".$this->id;
  }

  public function htmlTree($level = 0) {
      $result = $this->getHtmlLinkForList();
      $children_html = '';
      foreach ($this->getChildren() as $child)  {
          if ($child->isEmpty()) {
            continue;
          }

          if (!self::NOSPACES) {
              $children_html.=str_repeat(' ',$level*4+4);
          }
          $children_html.= "<li>".$child->htmlTree($level+1)."</li>";
          if (!self::NOSPACES) {
            $children_html .= "\n";
          }
      }
      if ($children_html) {
          if (!self::NOSPACES) {
              $result.="\n".str_repeat(' ',$level*4+2);
          }
          $result.="<ul>";
          if (!self::NOSPACES) {
              $result.="\n";
          }
          $result.=$children_html;
          if (!self::NOSPACES) {
            $result .= str_repeat(' ',$level*4+2);
          }
          $result.="</ul>";
          if (!self::NOSPACES) {        
            $result.="\n".str_repeat(' ',$level*4);
          }
      }
      return $result;
  }

  public function setParent($parent = FALSE) {
    if ($parent) {
        $this->parent = $parent;
    } elseif($this->tour->ParentId) {
        $this->parent = DbPackage::newFromDb($this->tour->ParentId);
    } elseif ($this->tour->ParentId === '0') {
        $this->parent = DbPackage::newRoot();
    }
  }
  public function getParent() {
    if ($this->parent === FALSE) {
      $this->setParent();
    }
    return $this->parent;
  }

  public function setForPrint($flag = TRUE) {
    $this->isForPrint = $flag;
  }

  public function setForSearch($flag= TRUE) {
    $this->isForSearch = $flag;
  }

  public function isForSearch() {
    return $this->isForSearch;
  }
  

  public function getPrintVersion() {
    $this->setForPrint();
    $content = $this->getHtmlContent();
    return theme('chgk_db_print', $this->getLongTitle(),
            $content,
            url($this->getLink(), array('absolute'=>TRUE)));
  }
 
  public function getHtmlContent() {
    return '';
  }
  
  public function getBreadcrumb() {
    $this->loadBranch();
    $result = array();
    for ($current=$this->getParent(); $current; $current=$current->getParent()) {
      array_unshift($result,$current->getHtmlLinkForBreadrumb()); 
    }
    return $result;
  }
  
  public function hasPrintVersion() {
    return FALSE;
  }

  public function hasFb2() {
    return FALSE;
  }

  protected function getPlayedAt() {
    return $this->tour->PlayedAt;
  }

  protected function getPlayedAt2() {
    return $this->tour->PlayedAt2;
  }

  public function getCreatedAt() {
    return $this->tour->CreatedAt;
  }

  public function getLastUpdated() {
    return $this->tour->LastUpdated;
  }

  public function getXML() {
    $xw = new xmlWriter();
    $xw->openMemory();
    $xw->startDocument('1.0','utf-8');
    $xw->startElement('tournament'); 

    $this->getFieldsXml( $xw );

    $this->getChildrenXml( $xw );
    $this->getQuestionsXml( $xw );

    $xw->endElement(); 

    $xmlResult = $xw->outputMemory(true);   
    $xmlResult = preg_replace('/\>\s*\</', ">\n<", $xmlResult);	
    return $xmlResult;
  }

  public function getQuestionsXml( $xw ) {
  }
  
  public function getChildrenXml( $xw ) {
  }
  


  
  protected function getFieldsXml( $xw ) {
    foreach ($this->tour as $key => $value)
    {
  	  $xw->writeElement ($key, $value);
    }
  }

  public function getYear() {
    $date = $this->getPlayedAt();
    $ar = explode('-', $date);
    return $ar[0];
  }
  
  public function getQuestion($number) {
    return FALSE;
  }

  private function reformatDate( $date ) {
    list($year, $month, $day) = explode('-', $date);
    if (!$day) return false;
    return $day.'.'.$month.'.'.$year;
  }
  
  public function getPlayedAtDate() {
    $date = $this->getPlayedAt();
    $date2 = $this->getPlayedAt2();
    $d1 = $this->reformatDate($date);
    if (!$d1) return FALSE;
    if (!$date2 || $date == $date2 ) {
    	return $date;
    } 
    $d2 = $this->reformatDate($date2);
    if (!$d2) return $d1;
    return "$d1 - $d2";
  }

  
  private function loadBranch() {
    $parent = $this->getParent();
    if ($parent) $parent->loadBranch();    
  }

  public function isEmpty() {
    return $this->getQuestionsNumber() == 0;
  }


  public function getQuestionsNumber() {
    return $this->tour->QuestionsNum;
  }

  public function getHtmlList($withoutChildren = false) {
    return '';
  }

  protected function getNewsURL($url = '') {
    if ($url === '') {
      $url = $this->tour->URL;
    }
    return preg_replace('~^/znatoki/boris/reports/~', self::NEWS_BASE, $url);
  }

  protected function getRatingURL() {
    $id = $this->tour->RatingId;
    if (!$id) return false;
    return preg_replace('~%ID~', $id, self::RATING_URL);
  }

  protected function getSrcLinks() {
    $urlsBlock = $this->tour->URL;
    if (!$urlsBlock) return array();
    $urls = explode("\n", $urlsBlock);
    $links = array();
    foreach ($urls as $url) {
      $regexp = '/\(att:\s*(.*?)\|(.*?)\)/';
      if (preg_match($regexp, $url, $matches)) {
        $links[] = l($matches[2], 'attachments/'.$matches[1]);
      } else {
        $links[] = l('Дополнительная информация о турнире', $this->getNewsUrl($url), array('attributes'=>array('rel'=>'nofollow'))); 
      }
    }
    return $links;

  }



  public function getTourInfoBlock() {
    $links = array();
    $links = $this->getSrcLinks();
    if ($url = $this->getRatingURL()) {
      $links[] = l('Страница турнира в рейтинге', $url, array('attributes'=>array('rel'=>'nofollow'))); 
    }

    if (!$links) return '';
    return theme('chgk_db_links', $links);
    $result = "<ul><li>".join('</li><li>', $links)."</li></ul>";
    return $result;
  }

  public function getTourList() {
    return '';
  }  

  public function save() {
    $db = new DbDatabase();
    $t = $db->getTournamentByTextId( $this->getId() ); 
    if ($t) {
      $db->updateTournament(  $this->tour );
      foreach ((array)$t as $k => $v ) {
        if ( !isset( $this->tour->$k ) ) {
          $this->tour->$k = $v;
        }
      }
    } else {
      $this->tour->Id = $db->addTournament( $this->tour );
    }
    $this->setId();
  }

  public function delete() {
    $db = new DbDatabase();
    $db ->deleteTournament( $this->tour->TextId );
  }
  
  public function setDbField( $name, $value) {
    $this->tour->$name = $value;
  }
  
  public function getEditors() {
    if ( $this->editors === null ) {
      $db = new DbDatabase();
      $this->editors = array();
      $res = $db->getPersonsByTournamentIdRes( $this->getDbId() );
      if ($res) {
          
        while ( $row = $res->fetch_object() ) {
            $this->editors[] = new DbPerson( $row );
        }
      }
    }
    return $this->editors;
  }

}
