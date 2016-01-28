<!-- comment --> 
<div class="comment <?php print $comment_classes;?> clear-block">
  <?php print $picture ?>
  <?php if ($comment->new): ?>
  <span class="new"><?php print drupal_ucfirst($new) ?></span>
  <?php endif; ?>
  <div class="submitted">
    <?php print $submitted ?>
  </div>
  <div class="content">
    <?php print $content ?>
    <?php if ($signature && $comment->cid > 0): ?>
    <div class="signature">
      <?php print $signature ?>
    </div>
    <?php endif; ?>
  </div>
  <?php if ($links): ?>
  <div class="links">
    <?php print $links ?>
  </div>
  <?php endif; ?>
</div>
<!-- /comment --> 
