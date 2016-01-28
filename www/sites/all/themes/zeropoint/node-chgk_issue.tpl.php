<?php 
$title = check_plain($node->instance->getTitle());
?>
<!-- node --> 
<div id="node-<?php print $node->nid; ?>" class="node <?php print $node_classes; ?>">

  <?php if ($page == 0): ?>
  <h3 class="title"><a href="<?php print $node_url ?>" title="<?php print $title; ?>"><?php print $title ?></a></h3>
  <?php endif; ?>


  <div class="meta">
    <?php if ($submitted): ?>
    <div class="submitted"><?php print $submitted ?></div>
    <?php endif; ?>
  </div>

<?php if ( $node->instance->isLinkedToQuestion() &&  !$node->instance->isQuestionView()) :?>
<div class="question-cite">
<?php print $node->instance->getQuestionHtml();?>
</div>

<?php endif;?>

  <?php if ($content_middle):?>
  <div id="content-middle">
    <?php print $content_middle; ?>
  </div>
  <?php endif; ?>

  <div class="content">
    <?php print $content ?>
  </div>

  <?php if ($links): ?>
  <div class="links">
    <?php print $links; ?>
  </div>
  <?php endif; ?>

  <?php if ($node_bottom && !$teaser): ?>
  <div id="node-bottom">
    <?php print $node_bottom; ?>
  </div>
  <?php endif; ?>
</div>
<!-- /node-<?php print $node->nid; ?> --> 
