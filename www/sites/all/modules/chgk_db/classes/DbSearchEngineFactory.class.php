<?php

class DbSearchEngineFactory extends DbFactory {
  protected $prefix = 'DbSearchEngine';
  private $map = array(
    'oldstyle' => 'DbSearchEngineOldStyle',
    'rus' => 'DbSearchEngineSphinx',
    'sphinx' => 'DbSearchEngineSphinx',
  );
  protected $type;

  public function getEngine($type) {
    $this->type = $type;
    if ($this->classExists()) {
      $ref = new ReflectionClass($this->getClassName());
      $result = $ref->newInstance();
    } else {
      if ($this->type == 'sphinx') return false;
      return $this->getEngine('sphinx');
    }
    return $result;
  }
  
  protected function getClassName() {
    if (!(isset($this->map[$this->type]))) return $this->getDefaultClass();

    return $this->map[$this->type];
  }

  protected function getDefaultClass() {
    return $this->map['sphinx'];
  }
}
