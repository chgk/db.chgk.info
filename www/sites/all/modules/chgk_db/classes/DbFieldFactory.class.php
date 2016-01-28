<?php

class DbFieldFactory extends DbFactory {
  private $field;
  private $value; 
  protected $prefix = 'DbField';

  public function getField($field, $value, $number = false, $parent = null) {
    $this->field = $field;
    $this->value = $value;
    if ($this->classExists()) {
      $ref = new ReflectionClass($this->getClassName());
      $result = $ref->newInstance($field, $value, $number, $parent);
    } else { 
      $result = new DbField($field, $value, $number, $parent);
    }
    return $result;
  }

  protected function getClassName() {
    return 'DbField'.$this->field;
  }

  
}
