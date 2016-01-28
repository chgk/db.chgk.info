<?php foreach ($question->fields as $name=>$field) : ?>
<?php echo $field->getName(true);?>:  <?php echo $field->getText();?>


<?php endforeach ?>

