=== Back To The Theme ===
Contributors: simison, migueluy, oskosk
Tags: development, testing, debug, themes
Requires at least: 4.6
Tested up to: 5.1
Stable tag: trunk
Requires PHP: 5.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

See a page with different themes all at once, just like that!

== Description ==

A tool to observe how a page loads in different themes simultaneously.
Useful for debugging plugins or Gutenberg blocks.

How to Use:

1. Install several themes you'd like to check out.
2. Create a new page.
3. Navigate to _Tools_ → _Back To The Theme_
4. Choose if you want to demo editor- or view side.
5. Select the themes you'd like to check out.
6. Choose the page you just created. This page will be previewed with all the themes you've selected.
7. Click _Do it!_.
8. Scroll to see the page rendered with all the themes you selected.

You'll see your page load with different themes in a bunch of iframes for handy preview and debugging.

A nice list of popular themes to test:

```
wp theme install \
  astra \
  colormag \
  customizr \
  generatepress \
  hestia \
  hueman \
  oceanwp \
  shapely \
  storefront \
  sydney \
  twentyeleven \
  twentyfifteen \
  twentyfourteen \
  twentynineteen \
  twentyseventeen \
  twentysixteen \
  twentyten \
  twentythirteen \
  twentytwelve \
  vantage
```

See docs for [wp theme install](https://developer.wordpress.org/cli/commands/theme/install/).

[Plugin's source code on GitHub](https://github.com/simison/back-to-the-theme).

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. You can find the plugin under "Tools" menu.

== Changelog ==

= 1.2.0 =

Test in the editor, too!

= 1.1.0 =

First version!
