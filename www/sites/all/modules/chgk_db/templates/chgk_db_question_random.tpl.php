<div class='random_question'>
<hr />
<p><a href="<?php echo $question->getUrl();?>"><?php echo $question->getSearchTitle();?></a></p>
 <strong>Вопрос <?php print $number;?>:</strong> <?php echo $question->getField('Question')->getHtml();?>
<?php if(!$question->doNotHideAnswer) :?>
 <div class='collapsible collapsed'>
 <div class="collapse-processed"><a href="#">...</a></div>
<?php endif;?>
 <?php foreach ($question->fields as $key=>$field) :?>
 <?php if ($key!='Question') :?>
<p>
    <strong><?php echo $field->getName();?>:</strong> <?php echo $field->getHtml();?>
</p>
<?php endif;?>
<?php endforeach;?>
<?php if(!$question->doNotHideAnswer) :?>
 </div>
 </div>
<?php endif; ?>


