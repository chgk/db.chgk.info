<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?php print $language->language ?>" xml:lang="<?php print $language->language ?>">
<head>
  <?php print $head;
  $left = preg_replace('~<script>.*?</script>~ms', '', $left); 
  ?>
  <title><?php print $head_title ?></title>
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <style>
    #search-theme-form .form-submit, #search-theme-form .form-item {
      display: block;
      float: left;
    }
    h2 {
      clear:both;
    }
    #search-theme-form label {
      display:none;
    }
    #skip, #desktop, p.toplinks a {
      font-size:24px;
    /*  font-weight:bold;*/
      margin-bottom: 13px;
      margin-right:60px;
    }
    .pager li {
      display: inline-block;
    }
  </style>
</head>

<body <?php print theme("onload_attribute"); ?>>

<p class="toplinks">
<a id="skip" href="<?php print url($_GET['q'], array('query' => NULL, 'fragment' => 'nav', 'absolute' => TRUE)); ?>"><?php print t('skip to navigation');?></a>

<?php print l('Поступления', 'last/mobile');?>

<a id="desktop" href="http://db.chgk.info/?device=desktop">Полная&nbsp;версия</a></p>
		<?php if ($search_box): ?>
		  <div id="search-box"><?php print $search_box; ?></div>
		<?php endif; ?>

<?php if ($title != ""): ?>

<h2 class="content-title"><?php print $title ?></h2>
<?php endif; ?>  
<?php if ($help != ""): ?>
<p id="help"><?php print $help ?></p>
<?php endif; ?> 
<?php if ($messages != ""): ?>
<div id="message"><?php print $messages ?></div>
<?php endif; ?>
<?php print $content ?>
<?php if ($tabs != ""): ?>
<?php print $tabs ?>
<?php endif; ?>
<a name="nav"></a>
<h2>Меню</h2>
<?php print menu_tree('primary-links'). menu_tree('secondary-links')./*$left . */$right; ?> 
<?php if ($footer_message) : ?>
<?php print $footer;?>
<?php endif; ?>
<?php print $closure;?>

<!-- Yandex.Metrika counter -->
<script src="//mc.yandex.ru/metrika/watch.js" type="text/javascript"></script>
<div style="display:none;"><script type="text/javascript">
try { var yaCounter1249121 = new Ya.Metrika(1249121);} catch(e){}
</script></div>
<noscript><div style="position:absolute"><img src="//mc.yandex.ru/watch/1249121" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->

</body>
</html>

