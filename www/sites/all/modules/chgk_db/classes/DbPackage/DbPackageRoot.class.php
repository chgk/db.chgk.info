<?php

class DbPackageRoot extends DbPackageGroup {
  public function __construct() {
    $row = new stdClass();
    $row->Id = 0;
    parent::__construct($row);
  }

  public function getTitle() {
    return 'Корень';
  }
  
  public function getFullTitle() {
    return 'Корень дерева турниров';
  }
  
  
  
   
  public function getHtmlLinkForList() {
     return '';
   }
}

?>
