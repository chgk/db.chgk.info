<?php
// $Id: vk-openapi-init-auth.tpl.php,v 1.9 2010/10/06 19:35:51 romka Exp $
?>

<?php
  global $user;
  $user_data = unserialize($user->data);
  $vkuid = $user_data['vk_data']['vkuid'];
?>  

<div id="vk_api_transport"></div>
<script type="text/javascript">
  window.vkAsyncInit = function() {
    VK.init({
      apiId: <?php print $apiID; ?>,
      nameTransportPath: "<?php print $path; ?>",
      status: true
    });
  }

  (function() {
    var el = document.createElement("script");
    el.type = "text/javascript";
    el.charset = "windows-1251";
    el.src = "http://vkontakte.ru/js/api/openapi.js";
    el.async = true;
    document.getElementById("vk_api_transport").appendChild(el);
  }());
}
</script>