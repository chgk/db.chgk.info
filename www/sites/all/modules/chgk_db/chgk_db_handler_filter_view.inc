<?php

class chgk_db_handler_filter_view extends views_handler_filter {
  function admin_summary() { }
  function operator_form() { }

  function value_form(&$form, &$form_state) {
    $values = array();
    $default_value = implode(', ', $values);
    $form['value'] = array(
        '#type' => 'checkboxes',
        '#title' => 'Тип вопросов',
        '#size' => 7,
        '#default_value' => array(1),
        '#options' => array(
            1 =>'Что? Где? Когда?', 
            2 =>'Брейн-ринг',
            3 =>'Интернет',
            4 =>'Бескрылка',
            5 =>'Своя игра',
            6=>'Эрудитка'
        ),
        '#multiple' => TRUE
      );


    if (!empty($form_state['exposed']) && !isset($form_state['input'][$this->options['expose']['identifier']])) {
      $form_state['input'][$this->options['expose']['identifier']] = array(1);
    }
  }

  function query() {
    $table = $this->ensure_my_table();
    $join = new views_join();
    $join->construct('Questions', $this->table_alias, 'title', 'TextId');
    $this->query->ensure_table('Questions', $this->relationship, $join);
    $values = implode(',',array_values($this->value));
    $this->query->add_where(
      $this->options['group'], "Questions.TypeNum IN ($values)");
  }
}