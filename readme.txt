=== WP-MoniTee ===
Contributors: SamRay1024
Donate link: nono
Tags: teeworlds, monitoring, server
Requires at least: 2.7
Tested up to: 2.7.1
Stable tag: 1.0.1

Provides you a widget which can display informations about your(s) Teeworlds server(s).

== Description ==

WP-MoniTee allows you to monitor a Teewordls server (one or more). The plugins provides you a widget
to add in your sidebar. If your theme supports dynamic sidebar, you can add it in the 
Appearance > Widgets menu. If not, you can use the PHP tag `<?php wp_monitee(); ?>` inside a
template PHP file.

For each server, the widget show :

1. Name of the server
1. Current map
1. Current game type (DM, TDM or CTF)
1. Number of players in game
1. Number of players allowed on the server

You can configure the widget directly from the widget options or from the dedicated options page.

The available options are :

1. **Widget title**
1. **Servers list** to monitor
1. **Minimum time in secondes between two requests to the Teeworlds server** : the server informations
are stored in database. For each page served, the plugin checks if the informations are out-of-date.
There are considered as is if the difference between the stored time and the current time is greater
than the number of seconds you'll set (15 per default).

== Installation ==

The installation is really simple. Either you install it automatically from your administration
panel or you install it manually :

1. Upload all the files (except the licence and screen shots files) to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Place the widget in your sidebar or put `<?php wp_monitee(); ?>` in your templates.

== Frequently Asked Questions ==

= Question ? =

Answer.

== Screenshots ==

1. The widget in the sidebar of the site
2. Configure via the Widget...
3. ...or via the options page.

== Changelog ==

**09/04/2009 - 1.0.1**

- Fixed : style compatibility problem of the widget (onWidgetEcho).