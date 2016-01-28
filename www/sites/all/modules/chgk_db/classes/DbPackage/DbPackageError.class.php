<?php

class DbPackageError extends DbPackage {
  public function getPrintVersion() {
    return 'No package with id '.$this->id;
  }
  public function loadFromDatabase() {
    return;
  }
  
#  public function getFb2() {
#    return false;
#  }

}
