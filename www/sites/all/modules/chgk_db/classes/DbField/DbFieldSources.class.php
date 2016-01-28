<?php

class DbFieldSources extends DbField {
  public function getName() {
    return 'Источник(и)';
  }

  public function pack() {
      $this->value = preg_replace(
              '/\n\s+(\d+)/', ';  $1',
              $this->value
       );
      $this->value = preg_replace(
              '/^\s+/', '',     $this->value
       );
      $this->html=false;
      $this->getHtml();
  }
}
