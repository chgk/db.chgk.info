<?php

class DbFieldAnswer extends DbField {
  public function getName() {
    return 'Ответ';
  }
  public function getFb2() {
    $result = "\n<empty-line/>\n".parent::getFb2();
    return $result;
  }

}
