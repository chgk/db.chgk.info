<q>
<number><?php print $question->getNumber();?></number>
<url><?php print $question->getAbsoluteQuestionUrl();?></url>
<tour_name><?php print $question->getSearchTitle();?></tour_name>
<tour_url><?php print $question->getAbsoluteTourUrl();?></tour_url>
<?php foreach ($question->fields as $field) {?>
<?php echo $field->getXML();?>

<?php }?>

</q>