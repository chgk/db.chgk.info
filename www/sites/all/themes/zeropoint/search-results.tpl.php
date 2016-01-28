<?php
// $Id: search-results.tpl.php,v 1.1 2007/10/31 18:06:38 dries Exp $

/**
 * @file search-results.tpl.php
 * Default theme implementation for displaying search results.
 *
 * This template collects each invocation of theme_search_result(). This and
 * the child template are dependant to one another sharing the markup for
 * definition lists.
 *
 * Note that modules may implement their own search type and theme function
 * completely bypassing this template.
 *
 * Available variables:
 * - $search_results: All results as it is rendered through
 *   search-result.tpl.php
 * - $type: The type of search, e.g., "node" or "user".
 *
 *
 * @see template_preprocess_search_results()
 */
if (0 && $user->uid==0 && arg(2) && arg(0)=='search') :
 $sstr = urlencode(iconv('utf-8', 'cp1251',arg(2)));
 $pageno = $_GET['page']?$_GET['page']+1:1;

?>
<script type="text/javascript"><!--

// Размер шрифтов
var yandex_ad_fontSize = 1;

// Настройки объявлений Директа
var yandex_direct_showType = 1;
var yandex_direct_fontColor = '000000';
var yandex_direct_BorderColor = 'FBE5C0';
var yandex_direct_headerBgColor = 'FEEAC7';
var yandex_direct_titleColor = '0000CC';
var yandex_direct_siteurlColor = '006600';
var yandex_direct_linkColor = '0000CC';
function yandex_direct_print(){ }

var yandex_r = Math.round(Math.random() * 100000);

document.write('<sc'+'ript type="text/javascript" src="http://an.yandex.ru/code/61815?rnd=' + yandex_r + '&text=<?php print $sstr;?>&page-no=<?php echo $pageno;?>"></'+'sc'+'ript>');

//--></script>

<!-- Яндекс.Директ должен быть размещен на первом экране страницы с результатами поиска -->
<script type="text/javascript">yandex_direct_print()</script>
<?php endif;?>
<dl class="search-results <?php print $type; ?>-results">
  <?php print $search_results; ?>
</dl>
<?php print $pager; ?>
