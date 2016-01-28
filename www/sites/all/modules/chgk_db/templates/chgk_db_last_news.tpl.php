<?php foreach ($nodes as $node) :?>
	<p><em><?php print format_date($node->created,"custom", "d.m");?></em>
	<?php print l($node->title, 'node/'.$node->nid); ?></p>
<?php endforeach;?>

<?php print l('Все новости', 'node');?>