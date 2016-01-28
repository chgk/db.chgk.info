<p><strong>Дата:</strong> <?php print $tour->getPlayedAtDate();?>
</p>
<?php if ($tour->hasEditor()) {?>
<div class='editor'><?php print $tour->getEditorHtml();?></div>
<?php }?>

<?php if ($tour->hasInfo()) : ?>
<div class='info'><?php print $tour->getInfo();?></div>
<?php endif;?>
<?php if (!$tour->isForSearch()) :?>
<p id="toggleAnswersP">
<a id="toggleAnswersLink" href="#" class="toggleLink answersHidden">Показать ответы</a>
</p>
<?php endif;?>
<?php
foreach ($tour->getTours() as $t)  :?>
  <?php if(!$tour->isSingleTour()) :?>
    <h2><?php print $t->getTitle(); ?></h2>
  <?php endif;?>
  <?php echo theme('chgk_db_tour', $t, FALSE); ?>
<?php endforeach;?>
<p>
<hr/>
<p>
<?php
echo l('[TXT]','txt/'.$tour->getFileName()).' '.l('[XML]','tour/'. $tour->getId().'/xml');
?>
</p>

