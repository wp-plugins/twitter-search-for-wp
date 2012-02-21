=== Twitter Search ===
Contributors: chriswallace, misternifty
Tags: twitter,search,query,tweets,social,media
Requires at least: 2.5
Tested up to: 2.6.2
Stable tag: 1.1.1

Twitter Search displays tweets based on a search query pulled from search.twitter.com.

== Description ==

Twitter Search pulls tweets based on a custom search query on any search string that search.twitter.com uses, caches it for a custom amount of time and displays the number of tweets you desire.

Features:

*   Display Unlimited Twitter Search Widgets on your blog
*   Adjust feeds based on search parameters from search.twitter.com
*   Add custom CSS to add custom widget styles
*   Twitter Search results caching using the Transient API

Requirements:

*	WordPress 2.5 or greater
*	PHP 5 or greater

== Installation ==

Installation of Twitter Search is rather straightforward.

1. Upload the `twitter-search-for-wp` directory to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Hop over to the Widgets tab and add a Twitter Search Widget.
4. Customize the options for the widget.

== Using Twitter Search in Pages or Posts with Shortcodes ==

To use Twitter Search as a shortcode, drop the following into your page or post and customize the variables.

[twitter_search query="upthemes" show="40" custom_css="yes"]

"query" is where you should enter the exact query you want to be run as a Twitter Search.
"show" is where you enter how many tweets to show.
"custom_css" is where you can tell it you want to use custom CSS or the default CSS that comes with the plugin.

When embedding the shortcode version of Twitter Search, you should keep in mind that if you are using another one in the sidebar with the default CSS applied, that same CSS will be applied to your shortcoded version as well.

== Frequently Asked Questions ==

= How does Twitter Search work? =

Twitter Search pulls the latest tweets from search.twitter.com according to the settings from each Twitter Search widget you've created.  Tweets get pulled from search.twitter.com when a visitor comes to your site, and are then cached for future visits.

= How many tweets can I display? =

Tweets can be limited in the widget settings.  Between 1 and 15 is recommended.

== Removal ==

Should you need to remove Twitter Search:

1. Go to Plugins > Twitter Search > Deactivate.
2. Once the page is refreshed, there will be a Delete link where there was a Deactivate link.

== Screenshots ==

1. Twitter Search settings interface in WordPress 2.6.
1. One of many possible display options. You are free to configure Twitter Search how you prefer!