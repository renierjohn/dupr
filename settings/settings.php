<?php

// phpcs:ignoreFile
$settings['hash_salt'] = 'SkTL5ABUi5IqR5tTvFho5gc6SLYI93QzlJVOgx-zZhUvewfRIJCQx0A5MoEKb_Xi_yzjTxRwrA';
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';
 $settings['trusted_host_patterns'] = [
    '^events-ph\.com$',
    '^.+\.events-ph\.com$',
    '^192.168.1.3$',
 ];$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];
$settings['entity_update_batch_size'] = 50;
$settings['entity_update_backup'] = TRUE;
$settings['migrate_node_migrate_type_classic'] = FALSE;

#
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
$databases['default']['default'] = array (
  'database' => 'sites/default/files/.ht.sqlite',
  'prefix' => '',
  'driver' => 'sqlite',
  'namespace' => 'Drupal\\sqlite\\Driver\\Database\\sqlite',
  'autoload' => 'core/modules/sqlite/src/Driver/Database/sqlite/',
);
$settings['config_sync_directory'] = '../config/default';

$config["config_split.config_split.dev"]["status"] = TRUE;
