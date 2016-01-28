<?php foreach ($tournament->getTours() as $t)  :?>
        <?php if(!$tournament->isSingleTour()) :?>
            <section>
                <title><p><?php print $t->getTitle(); ?></p></title>
                    <?php echo theme('chgk_db_tour_fb2', $t, FALSE); ?>
              </section>
	<?php else:?>
              <?php echo theme('chgk_db_tour_fb2', $t, FALSE); ?>
	<?php endif;?>
<?php endforeach;?>