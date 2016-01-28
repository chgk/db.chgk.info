<?php
function chgk_db_views_data() {
  $data = array();
  $data['node']['chgk_db_view'] = array(
    'title'=>'Тип вопросов',
    'filter'=>array(
      'handler' =>'chgk_db_handler_filter_view'
    )
  );
  return $data;
}

function chgk_db_views_handlers() {
    return array(
    'info' => array(
      'path' => drupal_get_path('module', 'chgk_db'),
    ),
    'handlers' => array(
      'chgk_db_handler_filter_view' => array(
        'parent' => 'views_handler_filter',
      ),
    ),
  );
}