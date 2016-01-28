<?php if ($tour->hasEditor() &&
          (!$alone && $tour->getEditor() !=$tour->getParentEditor())
        ) {?>
<p><?php print $tour->getEditorHtml();?></p>
<?php }?>

<?php
  if ( $tour->hasInfo() && 
          (!$alone && $tour->hasOwnInfo() ) )  {?>
    <p><?php print $tour->getInfo();?></p>
  <?php } 
  foreach ($tour->questions as $question) {
    print $question->getFb2();
  }

