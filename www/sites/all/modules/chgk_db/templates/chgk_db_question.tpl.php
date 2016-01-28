<div class="question<?php if($question->isIncorrect()) print ' incorrect';?>" id="<?php print $question->getTextId();?>">

<?php 
  $collapsible = FALSE;
foreach ($question->fields as $name=>$field) {
  if($name=='Answer' && !$question->isForSearch()) :?>
 <div class='collapsible collapsed'>
 <div class="collapse-processed"><a href="#">...</a></div>
<?php  $collapsible = TRUE;?>
  <?php endif;?>
<p>
    <strong class="<?php print $field->getCssClass();?>"><?php echo $field->getName();?>:</strong> <?php 
    $field_value = $field->getHtml();
    if ( preg_match('/^\s*<p>/', $field_value ) /*&& preg_match('~</p>\s*$~', $field_value )*/)  {
      $field_value = preg_replace( '/^\s*<p>/', '', $field_value );
      $field_value = preg_replace( '~</p>\s*$~', '', $field_value );
    }
    echo $field_value;?>
    <?php if ($name=='Question') :?>
    <?php endif;?>
</p>
<?php }?>
<?php if ( arg(0) != 'contact' && !$question->isNoContact()) : ?>
      <a href="<?php print $question->getContactUrl().'?destination='.request_uri();?>" style="color:red;font-weight:bold;background-color:yellow;width:10px;text-decoration:none" title="Сообщить об ошибке" >&nbsp;!&nbsp;</a>
<?php endif;?>
<?php if ($collapsible) :?>
</div>
<?php endif;?>

<?php 
if (arg(0) == 'question') {
    print $question->getIssues();
//    print $question->getMetaForm();
}
?>
</div>