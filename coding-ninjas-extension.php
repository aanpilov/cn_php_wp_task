<?php
/*
Plugin Name: Coding Ninjas Tasks Extension
Description: Test plugin extension
Author: Sasha Anpilov <sasha.anpilov@gmail.com>
Author URI: https://5am.site/
Plugin URI: https://5am.site/
Version: 1.0
Text Domain: cn
*/

defined('ABSPATH') || die();

// Check if parent plugin active
add_action('init', function() {
  include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
  if (is_plugin_active('coding-ninjas/coding-ninjas.php')) {
    // Load plugin
    require_once "app/App.php";
    \cntesttask\Test::init(__FILE__);
  } else {
    // Show admin notice
    add_action('admin_notices', function() {
      ?>
      <div class="notice notice-error is-dismissible">
        <p><?php _e("Plugin '<strong>Coding Ninjas Tasks Extension</strong>' deactivated. Please make sure '<strong>Coding Ninjas Tasks</strong>' plugin is installed and activated first", 'cn'); ?></p>
      </div>
      <?php
    });
    // Deactivate plugin
    deactivate_plugins(plugin_basename(__FILE__));
    if (isset($_GET['activate'])) {
      unset($_GET['activate']);
    }
  }
}, 9);
