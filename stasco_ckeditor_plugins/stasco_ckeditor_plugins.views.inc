<?php

/**
 * @file
 * Stasco_ckeditor_plugins.views.inc.
 */

/**
 * Implements hook_views_data_alter().
 */
function stasco_ckeditor_plugins_views_data_alter(&$data) {
  $data['file_managed']['stasco_file_file_name_field'] = [
    'title' => t('Stasco file file name field'),
    'field' => [
      'title' => t('Stasco file file name field'),
      'id' => 'stasco_file_file_name_field',
    ],
  ];
  $data['file_managed']['stasco_file_file_field'] = [
    'title' => t('Stasco file file field'),
    'field' => [
      'title' => t('Stasco file file field'),
      'id' => 'stasco_file_file_field',
    ],
  ];
  $data['file_managed']['stasco_file_copies_field'] = [
    'title' => t('Stasco file copies field'),
    'field' => [
      'title' => t('Stasco file copies field'),
      'id' => 'stasco_file_copies_field',
    ],
  ];
  $data['file_managed']['stasco_file_retention_field'] = [
    'title' => t('Stasco file retention field'),
    'field' => [
      'title' => t('Stasco file retention field'),
      'id' => 'stasco_file_retention_field',
    ],
  ];
  $data['file_managed']['stasco_file_version_field'] = [
    'title' => t('Stasco file version field'),
    'field' => [
      'title' => t('Stasco file version field'),
      'id' => 'stasco_file_version_field',
    ],
  ];
  $data['file_managed']['stasco_file_actions_buttons'] = [
    'title' => t('Stasco file actions buttons'),
    'field' => [
      'title' => t('Stasco file actions buttons'),
      'id' => 'stasco_file_actions_buttons',
    ],
  ];
}
