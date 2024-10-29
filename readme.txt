=== Affiliate Manager ===
Contributors: GeekLad
Donate link: http://geeklad.com/projects/wordpress-plugins
Tags: Market Leverage, banner, rotate banners, affiliate, affiliates, make money
Requires at least: 2.0
Tested up to: 2.7.1
Stable tag: 0.5

Easily import, display, and rotate affiliate banners.

== Description ==

The Affiliate Manager will easily import Market Leverage ad banner campaigns, display them, and rotate them.  Provide keywords for products related to your blog, and the plugin will automatically load the banners into WordPress.  You can display banners by modifying your template code, disiplaying them in widgets, or both.  The plugin requires [cURL](http://www.php.net/curl) to work properly.

== Installation ==

= Installation of the Plugin =

1. Unzip the contents of the plugin into the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

= Displaying Banners with the Template =
To display banners, use the appropriate function to display the banners as you wish.  There are three functions for displaying banners.


	affiliate_manager_random_banner(size)
	
This function will display a banner for any keyword of the appropriate size.  Just replace *size* with the size you wish to display in quotes.  Example:

	affiliate_manager_random_banner("120x600");
	

	affiliate_manager_by_keyword(keyword, size) 
	
This function will display a banner for a specified keyword of the appropriate size.  Replace *keyword* with the keyword for the banner, and replace *size* with the size you wish to display.  Both parameters should be in quotes.  Example:

	affiliate_manager_by_keyword("mortgage", "120x600");


	affiliate_manager_displayAd(id) 

This function will display a specific banner, according to the ad ID.  Replace *id* with the id of the banner.  This parameter *SHOULD NOT* be in quotes.  Example:

	affiliate_manager_displayAd(15);

= Displaying Banners in Widgets =

1. Go to *Appearance* > *Widgets* in WordPress.
2. Select the section where you want the widget to appear and click *Show*.
3. Click the *Add* button next to the *Affiliate Manager Widget* widget.
4. Select the keyword for the banners displayed in the widget.
5. Select the size of the banners to be displayed in the widget.
6. Click *Done* in the widget options.
7. Click *Save Changes* to add the widget to your pages.

== Frequently Asked Questions ==

= Why don't you include affiliate *Brand X* in the plugin? =

Please feel free to suggest any affiliates you would like me to include.  I recently joined Market Leverage and thought I would put together a better way of importing and rotating ad campaigns in WordPress.  I probably will include other affiliates as well.

== Screenshots ==

1. Adding an ad as a widget
2. List of keywords in the options screen

== Changelog ==

= 0.5 =
* Changed advertisement links to be nofollow
