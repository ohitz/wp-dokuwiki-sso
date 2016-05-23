wp-dokuwiki-sso
===============

This plugin allows users which log in to WordPress to be logged into DokuWiki
at the same time. When a user logs in, the plugin sets a cookie which is then
checked with a DokuWiki auth plugin.

The plugin adds two capabilities `dokuwiki_admin` and `dokuwiki_edit`
as well as two user roles, `DokuWiki Administrator` and `DokuWiki
Editor` having these capabilities. The capability of a user decides
with which group he will be logged into DokuWiki: `DokuWiki
Administrator` will result in the group `administrator`, whereas
`DokuWiki Editor` will result in group `editor`. These groups can then
be used in DokuWiki ACLs.

The initial version of this plugin was created in 2012 and has been in
use internally since then with a really old DokuWiki version. After a
major DokuWiki upgrade (to DokuWiki 2015-08-10a) it had to be adapted
to the new DokuWiki auth infrastructure. At the same time it was
cleaned up somewhat and some basic documentation was added.

Installation
------------

1. Install a DokuWiki instance on the desired site.

2. Copy the "wp-dokuwiki-sso" folder into the wp-content/plugins/
   folder of your WordPress installation.

3. Configure the plugin using the "Settings / DokuWiki SSO" function
   in the backend. You need to provide the following details:

   - Admin Bar Title: The title shown in the admin title bar
     (e.g. "DokuWiki")

   - DokuWiki URL: Full URL to the DokuWiki instance
     (e.g. "http://my-website.com/dokuwiki/")

   - Shared Secret: A secret shared between the DokuWiki auth plugin
     and WordPress (e.g. "VeRy-SeCuRe-SeCrEt")

4. Copy the "authwpsso" folder into lib/plugins/ of your DokuWiki
   installation.

5. Configure the DokuWiki plugin in conf/local.php of your DokuWiki
   installation as follows:

        $conf["authtype"] = 'authwpsso;
        $conf["superuser"] = '@administrator';
        $conf["auth"]["wpsso"]["timeout"] = 1800;
        $conf["auth"]["wpsso"]["secret"] = "VeRy-SeCuRe-SeCrEt"
        $conf["auth"]["wpsso"]["login"] = "http://my-website.com/wp-login.php";

   The "timeout" setting defines how long user stays logged into
   DokuWiki by default (30 minutes * 60 seconds = 1800 seconds).

License
-------

Copyright 2012-2016 Oliver Hitz <oliver@net-track.ch>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or (at
your option) any later version.

This program is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
General Public License for more details.
