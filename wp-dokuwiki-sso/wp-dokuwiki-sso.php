<?php
/*
Plugin Name: DokuWiki SSO
Plugin URI: 
Description: Single sign on into DokuWiki using Wordpress
Version: 1.0
Author: Oliver Hitz
Author URI: 

License:

Copyright 2012-2016 Oliver Hitz <oliver@net-track.ch>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
  die('This page cannot be accessed directly.');
}

class wp_dokuwiki_sso_class
{
  var $cookie = "wp-dokuwiki-sso";
  var $options;

  function install()
  {
    add_role('dokuwiki_administrator',
             'DokuWiki Administrator',
             array( 'read' => 1,
                    'dokuwiki_admin' => 1 ));
    
    add_role('dokuwiki_editor',
             'DokuWiki Editor',
             array( 'read' => 1,
                    'dokuwiki_edit' => 1 ));
    
    $role = get_role('administrator');
    $role->add_cap('dokuwiki_admin');
    
    $role = get_role('editor');
    $role->add_cap('dokuwiki_edit');
  }

  function wp_dokuwiki_sso_class()
  {
    add_filter('wp_login', array(&$this, 'login'));
    add_filter('wp_logout', array(&$this, 'logout'));

    add_action('admin_menu', array(&$this, 'setup'));

    add_action('wp_before_admin_bar_render', array(&$this, 'dokuwiki_link'));

    // i18n
    $plugin_dir = basename(dirname(__FILE__)). '/languages';
    load_plugin_textdomain('wp-dokuwiki-sso', WP_PLUGIN_DIR.'/'.$plugin_dir, $plugin_dir);
  }

  function load_options()
  {
    // Check if already loaded.
    if (isset($this->options)) {
      return;
    }

    // Load options.
    $o = get_option('wp-dokuwiki-sso');
    if (!is_array($o)) {
      $o = array();
    }
    $this->options = array_merge(array('url' => '',
                                       'title' => 'DokuWiki',
                                       'secret' => 'SHARED_SECRET'),
                                 $o);
  }
  
  function setup()
  {
    add_action('settings_page_dokuwiki-sso', array(&$this, 'save_settings'), 0);
    
    // Add Settings page.
    add_options_page('DokuWiki SSO',
                     'DokuWiki SSO',
                     'manage_options',
                     'dokuwiki-sso',
                     array(&$this, 'edit_settings'));
  }
  
  function dokuwiki_link()
  {
    global $wp_admin_bar;
    
    if (current_user_can('dokuwiki_edit') || current_user_can('dokuwiki_admin')) {
      $this->load_options();
      
      $wp_admin_bar->add_menu(array('title' => $this->options['title'],
                                    'href' => $this->options['url'],
                                    'id' => 'dokuwiki-link',
                                    'meta' => array('target' => '_blank')));
    }
  }
  
  function save_settings()
  {
    if (!$_POST || !current_user_can('manage_options')) {
      return;
    }
    
    $this->load_options();
    
    check_admin_referer('wp-dokuwiki-sso');
    
    if (isset($_POST['dokuwiki_title'])) {
      $this->options['title'] = esc_attr($_POST['dokuwiki_title']);
    }
    if (isset($_POST['dokuwiki_url'])) {
      $this->options['url'] = esc_attr($_POST['dokuwiki_url']);
    }
    if (isset($_POST['dokuwiki_secret'])) {
      $this->options['secret'] = esc_attr($_POST['dokuwiki_secret']);
    }
    
    update_option('wp-dokuwiki-sso', $this->options);
    
    echo '<div class="updated fade">'.
                                     '<p>'.
                                     '<strong>'.__('Settings saved.', 'wp-dokuwiki-sso').'</strong>'.
                                     '</p>'.
                                     '</div>'."\n";
  }
  
  function edit_settings()
  {
    $this->load_options();
    
    echo '<div class="wrap">'."\n";
    echo '<form method="post" action="">'."\n";
    
    wp_nonce_field('wp-dokuwiki-sso');
    
    screen_icon();
    
    echo '<h2>DokuWiki SSO Settings</h2>'."\n";
    
    echo '<table class="form-table">'."\n";
    
    echo '<tr>'.
               '<th><label for="dokuwiki_title">'.__('Widget title:', 'wp-dokuwiki-sso').'</label></th>'.
               '<td><input type="text" id="dokuwiki_title" name="dokuwiki_title" value="'.esc_attr($this->options['title']).'"/></td>'.
               '</tr>'."\n";
    
    echo '<tr>'.
               '<th><label for="dokuwiki_url">'.__('DokuWiki URL:', 'wp-dokuwiki-url').'</label></th>'.
               '<td><input type="text" id="dokuwiki_url" name="dokuwiki_url" value="'.esc_attr($this->options['url']).'"/></td>'.
               '</tr>'."\n";
    
    echo '<tr>'.
               '<th><label for="dokuwiki_secret">'.__('Shared Secret:', 'wp-dokuwiki-secret').'</label></th>'.
               '<td><input type="text" id="dokuwiki_secret" name="dokuwiki_secret" value="'.esc_attr($this->options['secret']).'"/></td>'.
               '</tr>'."\n";
    
    echo '</table>'."\n";
    
    echo '<p class="submit">'.
                             '<input type="submit" value="'.esc_attr(__('Save Changes', 'dokuwiki-sso')).'"/>'.
                             '</p>'."\n";
    
    echo '</form>'."\n";
    echo '</div>'."\n";
  }
  
  function login($user_name)
  {
    $user = get_user_by('login', $user_name);
    
    if (user_can($user, 'dokuwiki_admin')) {
      $roles[] = "administrator";
    }
    if (user_can($user, 'dokuwiki_edit')) {
      $roles[] = "editor";
    }
    
    if (count($roles) != 0) {
      $this->load_options();
      
      $timestamp = time();
      $tokens = join(":", $roles);
      $digest = md5($_SERVER["REMOTE_ADDR"].$timestamp.$this->options['secret'].$user_name.$tokens);
      setcookie($this->cookie,
                "$digest!$timestamp!$user_name!$tokens");
    } else {
      setcookie($this->cookie, '');
    }
    return false;
  }
  
  function logout()
  {
    setcookie($this->cookie);
  }
}

register_activation_hook(__FILE__, array('wp_dokuwiki_sso_class', 'install'));

$sign &= new wp_dokuwiki_sso_class();

?>
