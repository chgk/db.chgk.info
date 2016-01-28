<div style="margin-top:20px;"><strong><?php print l('Тема '.$question->getNumber(),$question->getQuestionLink())?></strong>:
<?php echo $question->theme;
if ($question->fields['Authors']) {
?> (<?php echo $question->fields['Authors']->getHtml();?>)
  <?php 
  
  } 
  foreach ($question->questions as $k => $q)  : 
  ?>
    <p><?php print $q->number;?>. <?php if ($q->Question) print $q->Question->getHtml();?>
    </p>
 

	  <?php $collapsible = FALSE;?>
        <?php if ($q->Answer ) :?>
          <?php if ( !$question->isForSearch() ) :?>
          <?php  $collapsible = TRUE;?>
            <div class='collapsible collapsed'>
            <div class="collapse-processed"><a href="#">...</a></div>
          <?php endif;?>

        <p><i>Ответ: </i><?php  echo  $q->Answer->getHtml(); ?></p>
       <?php if ($collapsible) :?>
        </div>
       <?php endif;?>

        <?php endif;?>
   <?php endforeach;?>
    <?php if ($question->fields['Sources'] )  :?>
    <p>
           <i>Источники: </i><?php  echo $question->fields['Sources']->getHtml();?>
    </p>
     <?php endif;?>
    <?php if ($question->fields['Comments'] )  :?>
    <p>
           <i>Комментарий: </i>
    </p>
	  <?php $collapsible = FALSE;?>
          <?php if ( !$question->isForSearch() ) :?>
          <?php  $collapsible = TRUE;?>
            <div class='collapsible collapsed'>
            <div class="collapse-processed"><a href="#">...</a></div>
          <?php endif;?>
<div>
	  <?php  echo $question->fields['Comments']->getHtml();?>
</div>
       <?php if ($collapsible) :?>
        </div>
       <?php endif;?>

     <?php endif;?>
<?php if ( arg(0) != 'contact' && !$question->isNoContact()) : ?>
      <a href="<?php print $question->getContactUrl().'?destination='.request_uri();?>" style="color:red;font-weight:bold;background-color:yellow;width:10px;text-decoration:none" title="Сообщить об ошибке" >&nbsp;!&nbsp;</a>
<?php endif;?>

<?php 
if (arg(0) == 'question') {
    print $question->getIssues();
    print $question->getMetaForm();
}
?>


</div>

