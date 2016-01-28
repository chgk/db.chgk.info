<?php

class DbFieldRating extends DbField {
  public function getName() {
      return 'Результат';
  }
  public function getHtml() {
    $html = parent::getHtml();
    $rating = $this->parent->getRating();
    $complexity = $this->parent->getComplexity();
    if ($rating || $complexity ) {
      $title = sprintf("Рейтинг: %.02f; Сложность: %d", $rating, $complexity);
      $html = "<span title=\"$title\">".$html."</span>";
    }
    return $html;
  }
}
