=== The DFE News Harvester ===
Contributors: dragonflyeye
Tags: news, rss, feed
Requires at least: 2.7
Tested up to: 2.7.1
Stable tag: 0.8

Register and monitor news feeds, post article links as posts

== Description ==

Update: 0.6 version includes a new "harvest-this.php" file which works like the "Press This," except with the added meta fields for DFE News Harvester.  Note that the wp-admin folder is included in the new package, but you'll need to move the "harvest-this.php" file to the actual wp-admin folder in order for it to work.

For a complete list of functions and uses, please see the plugin support site.  

This plugin allows administrators who want to feature and link to news articles to register news feeds, select articles from those feeds and post them as items on a blog.  This is intended for link blogging, primarily.  Uses WP meta fields to create special records including a teaser title, image, image credit, link to the original article and a blurb about the article which you can then display as you like on your site.  Note that special instructions for how to use meta should come from the WordPress.org support pages on the subject.

== Installation ==

1. Upload the entire /dfe_news_harvester folder to the `/wp-content/plugins/` directory.  You can exclude the /wp-admin folder.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Begin registering feeds and setting up configuration in the plugin's menu pages.
4. Optionally, install the "harvest_this.php" file into the /wp-amin folder

<strong>Upgrading from 0.5</strong>: you'll need to actually physically copy the "harvest-this.php" file to the correct location, /wp-admin, in order for it to work.