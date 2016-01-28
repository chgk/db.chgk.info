<?php 
  foreach ($tour->questions as $question) {
    print theme('chgk_db_question', $question);
  }


