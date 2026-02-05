<?php

// phpcs:ignoreFile
$settings['hash_salt'] = 'SkTL5ABUi5IqR5tTvFho5gc6SLYI93QzlJVOgx-zZhUvewfRIJCQx0A5MoEKb_Xi_yzjTxRwrA';
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';
 $settings['trusted_host_patterns'] = [
    '^events-ph\.com$',
    '^.+\.events-ph\.com$',
    '^192.168.1.3$',
 ];

$settings['config_sync_directory'] = '../config/default';
$config["config_split.config_split.dev"]["status"] = TRUE;
