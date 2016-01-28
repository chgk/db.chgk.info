<?php if ($packages) : 

?>
<table class="last_packages">
<tr>  <th>Название, дата</th>
<?php if (!$mobile) : ?>
<th style="width:100px;">Туры</th>
<?php endif; ?>

<th>Добавлено</th></tr>
<?php foreach ($packages as $package) : ?>
<?php $tours = $package->getToursList();?>
  <tr class="<?php print $i++%2?'even':'odd';?>">
    <td><?php print $tours?$package->getHtmlLinkForList(true, array('rel'=>'nofollow')):$package->getHtmlLinkForList( true );?></td>
<?php if (!$mobile) : ?>
    <td><?php print $tours;?></td>
<?php endif; ?>

    <td><?php print $package->getCreatedAt();?></td>

  </tr>
<?php endforeach;?>
</table>
<?php endif; ?>