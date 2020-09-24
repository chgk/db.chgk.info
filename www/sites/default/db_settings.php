<?php

$update_free_access = FALSE;

ini_set('arg_separator.output',     '&amp;');
ini_set('magic_quotes_runtime',     0);
ini_set('magic_quotes_sybase',      0);
ini_set('session.cache_expire',     200000);
ini_set('session.cache_limiter',    'none');
ini_set('session.cookie_lifetime',  2000000);
ini_set('session.gc_maxlifetime',   200000);
ini_set('session.use_cookies',      1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid',    0);
ini_set('url_rewriter.tags',        '');

$conf['default_theme'] = 'chgkdb_mobile';
$conf['cache_inc'] = './sites/all/modules/memcache/memcache.inc';

$conf['memcache_bins'] = array(
  'cache'         => 'default',
  'cache_filter'  => 'default',
  'cache_menu'    => 'default',
  'cache_page'    => 'default',
  'session'       => 'default',
  'users'         => 'default'
);


$conf['main_site'] = 'https://db.chgk.info';
$conf['image_domain'] = '';

$db_prefix = array(
  'Questions' => $conf['chgk_db'].'.',
  'Tournaments' => $conf['chgk_db'].'.',
  'P2T' => $conf['chgk_db'].'.',
  'P2Q' => $conf['chgk_db'].'.',
  'People' => $conf['chgk_db'].'.'
);

$conf['locale_custom_strings_ru'] = array(
  'Log out'=>'Выйти', 
  'Log in/Create account'=>'Войти/зарегистрироваться',
  'Create new account' => 'Регистрация',
  'Log in' => 'Войти',
  'Request new password' =>'Забыли пароль?',
  'Enter your @s username.'=>'Укажите ваше имя на сайте @s.',
  'Enter the password that accompanies your username.' => 'Укажите пароль, соответствующий вашему имени пользователя.',
  'User account' => 'Профиль пользователя',
  'Search' => 'Поиск',
  'Day' => 'День',
  'Month' => 'Месяц',
  'Year' => 'Год',
  'Home' => 'Главная',
  'Add new comment' => 'Добавить комментарий',
  'Reply to comment' => 'Добавление комментария',
  'Reply' => 'Ответить',
  'Your name' => 'Ваше имя',
  'Subject' => 'Тема',
  'Comment' => 'Комментарий',
  'Math question' => 'Задача по математике',
  'Read more' => 'Далее',
  'skip to navigation' => 'К меню',
  'Your e-mail address' => 'Ваш e-mail',
  'Message' => 'Сообщение',
  'Send yourself a copy.'=> 'Послать себе копию',
  'Send e-mail' => 'Послать e-mail',
  'You can send !category a message using the contact form below.' =>'',
  'Contact !category' => 'Сообщение на тему "!category"',
  'Comments' => 'Комментарии',
  '« first' => '<<',
  'Go to first page' => 'Перейти на первую страницу',
  '‹ previous' => 'предыдущая',
  'next ›' => 'следующая',
  'last »' => '>>',
  'Go to previous page'=>'Перейти на предыдущую страницу',
  'Go to next page' => 'Перейти на следующую страницу',
  'Go to last page' => 'Перейти на последнюю страницу',
  'Go to page @number' => 'Перейти на страницу @number',
  'Username' => 'Имя пользователя',
  'Password' => 'Пароль',
  'E-mail address' => 'E-mail адрес',
  'Spaces are allowed; punctuation is not allowed except for periods, hyphens, and underscores.' =>  'Ваше имя пользователя; не применяйте в нёт знаков пунктуации за исключением точек, знаков переноса и подчеркивания.',
  'A valid e-mail address. All e-mails from the system will be sent to '
  .'this address. The e-mail address is not made public and will only be '
  .'used if you wish to receive a new password or wish to receive certain '
  .'news or notifications by e-mail.'=>
  'Существующий адрес электронной почты. '
  .'Все почтовые сообщения с сайта будут '
  .'отсылаться на этот адрес. Адрес '
  .'электронной почты не будет '
  .'публиковаться и будет использован '
  .'только по вашему желанию: для '
  .'восстановления пароля или для '
  .'получения новостей и уведомлений по '
  .'электронной почте.',
  'Username or e-mail address' => 'Имя пользователя или адрес электронной почты',
  'E-mail new password' => 'Выслать новый пароль',
  'Your password and further instructions have been sent to your e-mail address.' =>  'Ваш пароль и дальнейшие инструкции высланы на ваш e-mail адрес.',
  '<p>This is a one-time login for %user_name and will expire on %expiration_date.</p><p>Click on this button to login to the site and change your password.</p>' =>
    '<p>Это одноразовый логин для %user_name , он будет недействителен после %expiration_date.</p><p>Нажмите на эту кнопку для того, чтобы войти на сайт и изменить свой пароль.</p>',
  'This login can be used only once.' => '',
  'You have just used your one-time login link. It is no longer necessary '
    .'to use this link to login. Please change your password.' =>
   'Вы использовали одноразовую ссылку входа на сайт. В дальнейшем для входа '
    .'пользоваться ей не нужно. Пожалуйста, '
    .'измените свой пароль.',
  'Account information' =>  'Информация об учетной записи',
  'To change the current user password, enter the new password in both fields.'=>
    'Чтобы изменить текущий пароль, укажите новый пароль в обоих полях.',
  'Confirm password' => 'Повторите пароль',
  'Picture' => 'Фотография',
  'Upload picture' => 'Загрузить фотографию',
  'Contact settings' => 'Настройки контактной формы',
  'Personal contact form' => 'Включить контактную форму',
  'Allow other users to contact you by e-mail via <a href="@url">your '
  .'personal contact form</a>. Note that while your e-mail address is not '
  .'made public to other members of the community, privileged users such '
  .'as site administrators are able to contact you even if you choose not '
  .'to enable this feature.'=>
  'Позволяет другим пользователям '
  .'связываться с вами по электронной '
  .'почте через вашу персональную <a '
  .'href="@url">форму контактов</a>. Обратите '
  .'внимание, что ваш почтовый адрес при этом не '
  .'показывается другим пользователям',
  'Your virtual face or picture. Maximum dimensions are %dimensions and '
  .'the maximum size is %size kB.' =>
  'Ваша фотография, которая будет отображаться на персональной странице. '
  . 'Максимальные размеры %dimensions и объем %size Кб.',
  'Locale settings' => 'Настройки локали',
  'Default time zone' =>  'Часовой пояс по умолчанию',
  'Select your current local time. If in doubt, choose the timezone that is closest to your location which has the same rules for daylight saving time. Dates and times throughout this site will be displayed using this time zone.' 
     => 'Выберите ваше текущую временную зону',

  'Apply' => 'Применить',
  'Items per page' => 'На странице',
  'More information about formatting options' => '',
  'Revision information' => 'Информация о версии',
  'Log message' => 'Комментарий к исправлению',
  'An explanation of the additions or updates being made to help other authors understand your motivations.' =>
    'Если вы вносите исправление, поясните здесь, почему вы это делаете',
  'File attachments' => 'Дополнительные материалы',
  'Create content' => 'Добавить',
  'Changes made to the attachments are not permanent until you save this post. The first "listed" file will be included in RSS feeds.'
    => 'Файлы будут прикреплены только после сохранения',
  'Attach new file' => 'Прикрепить новый файл',
  'The maximum upload size is %filesize. Only files with the following extensions may be uploaded: %extensions. ' => '',
  'Attach' => 'Прикрепить',
  'View' => 'Просмотр',
  'Revisions' => 'История',
  '!name field is required.' => 'Не заполнено поле "!name"',
);


spl_autoload_register('chgk_db_autoload');

function chgk_db_autoload( $a) {
  if ( $a[0]=='D' && $a[1] == 'b' ) {
     require_once(dirname(__FILE__)."/../all/modules/chgk_db/classes/$a.class.php");
  }
}

function custom_url_rewrite_inbound( &$result, $path, $path_language ) {
  global $person;
  if (preg_match('|^person/(.*)|', $path, $matches)) {
    require_once(dirname(__FILE__)."/../../modules/user/user.module");
    $person = new DbPerson($matches[1]);

    $uid = $person->getUserId();
    if ( $uid ) {
      $result = 'user/'.$uid.'/person';
    }

  }
}
