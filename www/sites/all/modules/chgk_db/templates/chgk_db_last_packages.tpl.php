<?php if ($packages) :

?>
<table class="last_packages">
<tr>  <th>Название, дата</th>

<th>Добавлено</th></tr>
<?php foreach ($packages as $package) : ?>
  <tr class="<?php print $i++%2?'even':'odd';?>">
    <td><?php print $tours?$package->getHtmlLinkForList(true, array('rel'=>'nofollow')):$package->getHtmlLinkForList( true );?><?php
        $user = $package->getUser();
        if ($user) :
    ?>, by <?= $user->name ?>
    <?php  endif; ?>
    </td>

    <td><?php
        $date = new DateTime($package->getCreatedAt());
        ini_set('display_errors', 1);
        print $date->format('d.m.Y');
    ?></td>
  </tr>
<?php endforeach;?>
</table>
<?php endif; ?>