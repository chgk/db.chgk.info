<?php if ($alone || $tour->getPlayedAtDate() != $tour->getParent()->getPlayedAtDate()): ?>
<p><strong>Дата:</strong> <?php print $tour->getPlayedAtDate();?>
<?php endif;?>

<?php if ($tour->hasEditor() &&
          ($alone || $tour->getEditor() !=$tour->getParentEditor())
        ) {?>
<div class='editor'><?php print $tour->getEditorHtml();?></div>
<?php }?>

<?php
  if ( $tour->hasInfo() && 
          ($alone || $tour->getInfo() !=$tour->getParentInfo()) )  {?>
    <div class='info'><?php print $tour->getInfo();?></div>
  <?php } ?>
<?php if ($alone && !$tour->isForSearch() ) :?>
<p id="toggleAnswersP">
<a id="toggleAnswersLink" href="#" class="toggleLink answersHidden">Показать ответы</a>
</p>
<?php endif;?>

<?php
  foreach ($tour->questions as $question) {
    print $question->getHtml();
  }
?>

<?php if ($alone) :?>
<p>
<hr/>
<p>
<?php
echo '<a href="'.url('tour/'. $tour->getId().'/xml').'">[XML]</a>';
?>
</p>


<?php endif;?>