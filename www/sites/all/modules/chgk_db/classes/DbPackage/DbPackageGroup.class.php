<?php

class DbPackageGroup extends DbPackage {
  protected $children = FALSE;
  public function getFb2() {
    return FALSE;
  }

  public function getChildrenXml( $xw ) {
      foreach ( $this->getChildren() as $child ) {
	      $xw->startElement('tour');
	      $child->getFieldsXml( $xw );
	      $xw->endElement();
      }
  }

  public function getHtmlList($withoutChildren = false) {
    $this->loadChildren();
    $result='<ul>';
    foreach ($this->getChildren() as $child) {
      if ($child->isEmpty()) {
        continue;
      }
      $result .= "<li>";
      $result .= $child->getHtmlLinkForList();
      if (!$withoutChildren) $result .= $child->getHtmlList(true);
      $result .= "</li>";
    }
    $result.="</ul>";
    return $result;
  }
  public function getHtmlContent() {
    $result=$this->getHtmlList();
    $result.= '<hr/><p>'.l('[XML]','tour/'. $this->getId().'/xml').'</p>';

    return $result;
    return theme('chgk_db_champ_full', $this);  
  }
  
}
