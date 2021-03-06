<?php
// $Id: vk_share.admin.inc,v 1.3 2010/10/06 19:35:51 romka Exp $

function vk_share_admin_settings() {

  $form['vk_share_node_types'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Node types to share in vkontakte.ru'),
    '#options' => node_get_types('names'),
    '#description' => t('Check node types witch you want to share.'),
    '#default_value' => variable_get('vk_share_node_types', array('page' => 'page')),
  );

  $form['vk_share_display_page'] = array(
    '#type' => 'checkbox',
    '#title' => t('Display VK share button on full node pages'),
    '#default_value' => variable_get('vk_share_display_page', 1),
  );

  $form['vk_share_display_teaser'] = array(
    '#type' => 'checkbox',
    '#title' => t('Display VK share button on node teasers'),
    '#default_value' => variable_get('vk_share_display_teaser', 1),
  );

  $form['vk_share_button_style'] = array(
    '#type' => 'select',
    '#title' => t('Style of share button'),
    '#default_value' => variable_get('vk_share_button_style','button_count'),
    '#options' => array(
      'button_count' => t('VK button with counts'),
      'button' => t('Vk button without counts'),
      'link' => t('VK link'),
    ),
    '#description' => t('Button styles <a href="!url">description</a>.', array('!url' => 'http://vkontakte.ru/developers.php?o=-1&p=Share')),
  );

  return system_settings_form($form);

}
