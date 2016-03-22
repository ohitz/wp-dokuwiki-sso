<?php
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * Part of the wp-dokuwiki-sso plugin.
 *
 * Copyright 2012-2016 Oliver Hitz <oliver@net-track.ch>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 */

class auth_plugin_authwpsso extends DokuWiki_Auth_Plugin {
  protected $config = array();
  
  public function __construct() {
    global $conf;
    
    $this->config = $conf["auth"]["wpsso"];

    //
    // Set defaults
    //
    if (empty($this->config["timeout"])) {
      $this->config["timeout"] = 0;
    }
    if (empty($this->config["cookie"])) {
      $this->config["cookie"] = "wp-dokuwiki-sso";
    }
    if (empty($this->config["secret"])) {
      // We need a secret key for this to work.
      $this->success = false;
    }

    $this->cando['external'] = true;
    $this->cando['logout'] = false;
  }

  function redirectLogin()
  {
    if (isset($this->config["login"])) {
      // Redirect to a login URL
      header("Location: ".$this->cnf["login"]);
      exit(0);
    }
  }
  
  function trustExternal($user, $pass, $sticky=false)
  {
    global $USERINFO;
    global $conf;

    if (isset($_COOKIE[$this->config["cookie"]])) {
      $cookie = explode("!", $_COOKIE[$this->config["cookie"]]);
      
      if (count($cookie) != 4) {
        return false;
      }

      $data = array();
      $data["digest"] = $cookie[0];
      $data["timestamp"] = $cookie[1];
      $data["username"] = $cookie[2];
      $data["tokens"] = $cookie[3];
      
      // Verify hash
      $raw = $_SERVER["REMOTE_ADDR"].
           $data["timestamp"].
           $this->config["secret"].
           $data["username"].
           $data["tokens"];
      
      if (md5($raw) != $data["digest"]) {
        $this->redirectLogin();
        return false;
      }

      // Verify if the token has expired
      if ($this->config["timeout"] > 0) {
        if ($data["timestamp"] + $this->config["timeout"] < time()) {
          $this->redirectLogin();
          return false;
        }
      }
      
      $USERINFO['name'] = $data["username"];
      $USERINFO['grps'] = explode(":", $data["tokens"]);
      $USERINFO["mail"] = "";
      $USERINFO["pass"] = "";
      
      $_SESSION[$conf['title']]['auth']['user'] = $data["username"];
      $_SESSION[$conf['title']]['auth']['info'] = $USERINFO;
      
      $_SERVER['REMOTE_USER'] = $data['username'];
      return true;
    } else {
      $this->redirectLogin();
      return false;
    }
  }
}

?>
