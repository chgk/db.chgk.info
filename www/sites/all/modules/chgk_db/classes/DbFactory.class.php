<?php

abstract class DbFactory {

  protected $prefix;

  abstract protected function getClassName();
  
  protected function getFileName() {
    $result = dirname(__FILE__)."/".$this->prefix."/".$this->getClassName().".class.php";
    return $result;
  }
  
  protected function classExists() {
    if ( $this->fileExists() ) {
      require_once($this->getFileName());      
    }
    return class_exists($this->getClassName());
  }
  
  protected function fileExists() {
    return file_exists($this->getFileName());
  }
  
  protected function getDefaultClass() {
    return $this->prefix;
  }
  
}
