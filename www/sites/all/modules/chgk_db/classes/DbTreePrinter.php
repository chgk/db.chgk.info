<?php
/// UNUSED!
class DbTreePrinter {
    private $package = FALSE;
    private $level = 0;
    private $html = '';

    public function __construct($package, $level = 0) {
        $this->package = $package;
        $this->level = $level;
    }

    private function addString($string) {
        $this->html .= $string;
    }

    private function addSpacesToChildrenHtml($count) {
        $this->childrenHtml.=str_repeat(' ',$this->level*4+$count*2);
    }

    private function getChildrenHtml() {
      $result = '';
      foreach ($this->package->getChildren() as $child)  {
          $this->addSpacesToChildrenHtml(2);
          $this->addToChildrenHtml(
                  "<li>".$child->htmlTree($level+1)."</li>"
          );
          $this->addNewLineToChildrenHtml();
      }
    }

    private function addNewLineToChildrenHtml() {
        $this->childrenHtml.="\n";
    }
    public function getHtml() {
      if ($this->html) {
          return $html;
      }
      $this->html = '';
      $this->addString($this->package->getHtmlLinkForList());
      $childrenHtml = $this->getChildrenHtml();
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

    }
}

?>
