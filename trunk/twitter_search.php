<?php

/*
Plugin Name: Twitter Search
Version: 1.0.1
Plugin URI: http://upthemes.com/plugins/twitter-search/
Description: Displays tweets in a handy dandy, customizable widget via the Twitter Search API. There are no options for the overall plugin. Simply visit your Widgets section and drop it into a widget area.
Author: Rogie King for UpThemes
Author URI: http://upthemes.com


Copyright (c) 2011 UpThemes, http://upthemes.com

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

require_once(ABSPATH . "wp-includes/class-snoopy.php");

/**
 * Add function to widgets_init that'll load our widget.
 * @since 0.1
 */
add_action( 'widgets_init', 'twitter_search_load_widgets' );

/**
 * Register our widget.
 * 'Example_Widget' is the widget class used below.
 *
 * @since 0.1
 */
function twitter_search_load_widgets() {
	register_widget( 'Twitter_Search_Widget' );
}

function setup_twitter_search_languages(){
	load_plugin_textdomain( 'twitsearch', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

function add_js_to_widget_page(){
	wp_enqueue_script( 'widget_admin', trailingslashit( get_bloginfo('url') ) . PLUGINDIR . '/' . dirname(plugin_basename(__FILE__)) . '/js/widget_admin.js', array('jquery') );

}

add_action('admin_print_scripts-widgets.php','add_js_to_widget_page');

function add_css_to_widget_page(){
	wp_enqueue_style( 'twitter_search_admin', trailingslashit( get_bloginfo('url') ) . PLUGINDIR . '/' . dirname(plugin_basename(__FILE__)) . '/style/twitter_search_admin.css', false );

}

add_action('admin_print_styles-widgets.php','add_css_to_widget_page');

/**
 * Twitter Search Widget class.
 *
 * @since 0.1
 */
class Twitter_Search_Widget extends WP_Widget {
	
	public $json = NULL;

	public $group = "twitter-search";
	
	public $tweetsToShow = 10;
	
	public $cacheMinutes = 3;
	
	public $showReplies = true;
	
	public $url = "http://search.twitter.com/search.json";		
	
	public $q = "";
	
	public $currentUrl = '';
	
	public $tpl = array(
							'tweet' => '<li class="tweet tweet_{index}"><p><span class="who"><img src="{from_user_avatar}" alt="{from_user}\'s avatar"/></span><a href="http://twitter.com/{from_user}" class="{from_user}">{from_user}</a> {tweet_html}</p><small class="time">{since} ago</small> <small class="reply"><a title="Reply to {from_user}" href="http://twitter.com/?status=@{from_user}  &in_reply_to_status_id={tweet_id}&in_reply_to={from_user}">Reply</a></small></li>' , 
							'before' => '<ul>', 
							'after' => '</ul>'
						);

	/**
	 * Widget setup.
	 */
	function Twitter_Search_Widget() {

		/* Widget settings. */
		$widget_ops = array( 'classname' => 'twit-search-feed', 'description' => __('A widget that displays a twitter search feed.','twitsearch') );

		/* Widget control settings. */
		$control_ops = array( 'width' => 300, 'id_base' => 'twit-search-feed' );

		/* Create the widget. */
		$this->WP_Widget( 'twit-search-feed', __('Twitter Search Widget','twitsearch'), $widget_ops, $control_ops );

	}

	/**
	 * How to display the widget on the screen.
	 */
	function widget( $args, $instance ) {
		extract( $args );

		/* Our variables from the widget settings. */
		$title = apply_filters('widget_title', $instance['title'] );
		$sQuery = $instance['twitter_search_query'];
		$iShow = $instance['twitter_search_show'];
		$sTemplate = $instance['twitter_search_template'];
		$before = $instance['twitter_before'];
		$after = $instance['twitter_after'];
		$useCustomCSS = $instance['twitter_use_custom_css'];
				
		$this->tweetsToShow = intval( $iShow );
		$this->cacheMinutes = $iCacheExpires;
		
		/* Before widget (defined by themes). */
		echo $before_widget;
		echo $before_title . $title . $after_title;
		
		if( $useCustomCSS == 'no' )
			echo '<link href="' . plugin_dir_url( __FILE__ ) . '/style/default.css' . '" rel="stylesheet" type="text/css" />';
		
		$this->search( $sQuery );
		$this->setTemplate($sTemplate,$before,$after);
		$this->render();
		
		/* After widget (defined by themes). */
		echo $after_widget;
	}

	/**
	 * Update the widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['twitter_search_query'] = $new_instance['twitter_search_query'];
		$instance['twitter_search_show'] = $new_instance['twitter_search_show'];
		$instance['twitter_search_template'] = $new_instance['twitter_search_template'];
		$instance['twitter_before'] = $new_instance['twitter_before'];
		$instance['twitter_after'] = $new_instance['twitter_after'];
		$instance['twitter_use_custom_css'] = $new_instance['twitter_use_custom_css'];
		
		return $instance;
	}

	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'title' => __('Latest Tweets', 'twitsearch'), 'twitter_search_query' => '@upthemes', 'twitter_search_show' => $this->tweetsToShow, 'twitter_search_template' => $this->tpl['tweet'], 'twitter_before' => $this->tpl['before'], 'twitter_after' => $this->tpl['after'], 'twitter_use_custom_css' => 'no' );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		
		<!-- Widget Title -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e("Widget Title","twitsearch"); ?></label>
			<input type="text" size="28" value="<?php echo esc_attr($instance['title']); ?>" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" />
		</p>

		<!-- Twitter Search Query -->
		<p>
			<label for="<?php echo $this->get_field_id( 'twitter_search_query' ); ?>"><?php _e("Twitter Search Query","twitsearch"); ?></label>
			<input type="text" size="30" value="<?php echo $instance['twitter_search_query']; ?>" id="<?php echo $this->get_field_id( 'twitter_search_query' ); ?>" name="<?php echo $this->get_field_name( 'twitter_search_query' ); ?>" />
			
			<p><?php _e("Enter a Twitter search query.",'twitsearch'); ?> <a class="search-query-help" href="#"><?php _e("view examples","twitsearch"); ?></a></p>

			<div class="search-query-help-div helper">
			
				<ul>
					<li><code>Hiphopapotamus</code>: <?php _e('Any tweet matching "Hiphopapotamus" from anyone.',"twitsearch"); ?></li>
					<li><code>from:upthemes</code>: <?php _e('All tweets from upthemes.',"twitsearch"); ?></li>
					<li><code>to:upthemes</code>: <?php _e('All tweets to upthemes.',"twitsearch"); ?></li>
					<li><code>from:upthemes to:command_tab</code>: <?php _e("All tweets from upthemes to command_tab (upthemes's conversation to command_tab)","twitsearch"); ?></li>
					<li><code>from:upthemes to:command_tab OR from:command_tab to:upthemes</code>: <?php _e('Tweets between upthemes and command_tab.',"twitsearch"); ?></li>
					<li><code>#iphone</code>: <?php _e('Tweets about the iPhone (hashtags)',"twitsearch"); ?></li>
					<li><?php _e('More search options at <a href="http://search.twitter.com/advanced">search.twitter.com</a>',"twitsearch"); ?></li> 
				</ul>

			</div>
			
			<div class="helperclear"></div>
			
			<p><em><?php _e('These searches return different results: "<strong>upthemes</strong> OR <strong>@upthemes</strong> OR <strong>upthemes.com</strong>"','twitsearch'); ?></em></p>
			
		</p>
		
		<!-- Number of Tweets to Display -->
		<p>
			<label for="<?php echo $this->get_field_id( 'twitter_search_show' ); ?>"><?php _e("Number of Tweets to Display","twitsearch"); ?></label>
			<input type="text" size="4" value="<?php echo $instance['twitter_search_show']; ?>" id="<?php echo $this->get_field_id( 'twitter_search_show' ); ?>" name="<?php echo $this->get_field_name( 'twitter_search_show' ); ?>" />
		</p>
		
		<!-- Use Custom CSS -->
		<p>
			<label for="<?php echo $this->get_field_name( 'twitter_use_custom_css' ); ?>"><?php _e("Use Custom CSS","twitsearch"); ?></label>
			<select id="<?php echo $this->get_field_id( 'twitter_use_custom_css' ); ?>" name="<?php echo $this->get_field_name( 'twitter_use_custom_css' ); ?>">
				<option value="no"<?php if( $instance['twitter_use_custom_css'] == 'no' ) echo " selected"; ?>><?php _e("No","twitsearch"); ?></option>
				<option value="yes"<?php if( $instance['twitter_use_custom_css'] == 'yes' ) echo " selected"; ?>><?php _e("Yes","twitsearch"); ?></option>
			</select>
			<p>
				<kbd><?php _e('If you decide to use your own custom CSS, you will simply need to add the styles to your theme\'s CSS file and use the HTML IDs and classes defined below in your tweet template.','twitsearch'); ?></kbd>
			</p>
		</p>

		<!-- HTML Before Tweets -->
		<p>
			<label for="<?php echo $this->get_field_id( 'twitter_before' ); ?>"><?php _e("HTML Before Tweets","twitsearch"); ?></label>
			<input type="text" size="4" value="<?php echo $instance['twitter_before']; ?>" id="<?php echo $this->get_field_id( 'twitter_before' ); ?>" name="<?php echo $this->get_field_name( 'twitter_before' ); ?>" />
		</p>
			
		<!-- HTML After Tweets -->
		<p>
			<label for="<?php echo $this->get_field_id( 'twitter_after' ); ?>"><?php _e("HTML After Tweets","twitsearch"); ?></label>
			<input type="text" size="4" value="<?php echo $instance['twitter_after']; ?>" id="<?php echo $this->get_field_id( 'twitter_after' ); ?>" name="<?php echo $this->get_field_name( 'twitter_after' ); ?>" />
		</p>
			
		<!-- Tweet Display Format -->
		<p>
			<label for="<?php echo $this->get_field_id( 'twitter_search_template' ); ?>"><?php _e("Display Format Template","twitsearch"); ?></label>
			
			<textarea class="code tweet_format" id="<?php echo $this->get_field_id( 'twitter_search_template' ); ?>" rows="14" name="<?php echo $this->get_field_name( 'twitter_search_template' ); ?>"><?php echo $instance['twitter_search_template']; ?></textarea>

			<p><a class="search-template-help" href="#"><?php _e("Help with Template Tags","twitsearch"); ?></a></p>

			<div class="search-template-help-div helper">
			
				<p><?php _e('The Twitter Search tags below can be used with standard <abbr title="HyperText Markup Language">HTML</abbr> to customize the album display format. Tags can be used more than once, or completely left out, depending on your preferences.',"twitsearch"); ?></p>

				<ul class="tweet_templates">
					<li><code>{tweet_html}</code> <?php _e('The html of the full tweet (including links to @replied users, #hashtags and websites)',"twitsearch"); ?></li>
					<li><code>{tweet_text}</code> <?php _e('The raw tweet text (NO links to @replied users, #hashtags and websites)','twitsearch'); ?></li>
					<li><code>{tweet_id}</code> <?php _e('The tweet id. i.e. 5892024713 (You can use this to build permalinks and reply to links if you are clever enough ;)','twitsearch'); ?></li>
					<li><code>{from_user}</code> <?php _e('The tweet author name. i.e "upthemes"','twitsearch'); ?></li>
					<li><code>{from_user_id}</code> <?php _e('The tweet author id. i.e. "73938"','twitsearch'); ?></li>
					<li><code>{date_created}</code> <?php _e('The date this tweet was created, in ugly format. i.e. "Fri, 20 Nov 2009 16:00:04 +0000"','twitsearch'); ?></li>
					<li><code>{since}</code> <?php _e('The time since this tweet was created. i.e. "12 minutes ago"','twitsearch'); ?></li>
					<li><code>{date}</code> <?php _e('The simple formatted date this tweet was created. i.e. "Nov 20"','twitsearch'); ?></li>
					
					<li><code>{index}</code> <?php _e('The position of the tweet in the list (1<sup>st</sup> tweet number = 1, etc).','twitsearch'); ?></li>
	
				</ul>
			
			</div>

			<div class="helperclear"></div>
				
		</p>

	<?php
	
	}
	
	function search( $query = '@upthemes' ){
		
		$this->q = $query;
		
		$cachedJSON = get_transient($this->q);
				
		if( $cachedJSON == false ){

			$this->currentUrl = $this->url . "?rpp=" . $this->tweetsToShow . "&q=" . urlencode($this->q);
			
			$snoopy = new Snoopy();
			$got = $snoopy->fetch($this->currentUrl);		
			
			if( $got ){
			
			   $this->json = json_decode( $snoopy->results, true );
			
            if( array_key_exists('error', $this->json) ){
   			   
   			   echo($this->json['error']);
   			   
   			   return null;
   			};	 
   			
   			$results = array();
   			
   			if( $this->showReplies == false ){
   				foreach( $this->json['results'] as $index => $tweet ){					
   					
   					if( !is_numeric($tweet['to_user_id']) ){
   						$results[] = $tweet;

   					}
   				}
   				$this->json['results'] = $results;
   			}
   			   			
   			set_transient( $this->q,$this->json, 60*5 );
			}
			
		}else{
		
			$this->json = $cachedJSON;
			
		}

		return $this->json;	
	}

	function render(){
	  
	  echo $this->tpl['before'];
	  	  
		foreach( $this->json['results'] as $index => $tweet ){					
			if( $index >= $this->tweetsToShow ){
			   break;
			}else{	
			   $tweet['index'] = $index;
			   echo( $this->applyTemplate( $tweet, $this->tpl['tweet'] ) );
			}
		}
		
	  echo $this->tpl['after'];
			
	}
	
	function setTemplate( $tweet, $before = '<ul>', $after = '</ul>'){
		
		$this->tpl['tweet'] 	= $tweet;
		$this->tpl['before'] 	= $before;
		$this->tpl['after'] 	= $after;

	}
	
	function tweet2HTML( $sText = '' ){
	
		$sHTML = $sText;
		
		//make web links
		$sHTML = eregi_replace('(((f|ht){1}tp://)[-a-zA-Z0-9@:%_\+.~#?&//=]+)', '<a href="\\1">\\1</a>', $sHTML);
		$sHTML = eregi_replace('([[:space:]()[{}])(www.[-a-zA-Z0-9@:%_\+.~#?&//=]+)', '\\1<a href="http://\\2">\\2</a>', $sHTML);		
		
		//replace @replies
		$sHTML = preg_replace('/@(\w*)/', '<a href="http://twitter.com/\\1">@\\1</a>', $sHTML);
		
		//replace #hashtags
		$sHTML = preg_replace('/#(\w*)/', '<a href="http://search.twitter.com/search?q=%23\\1">#\\1</a>', $sHTML);
		
		return $sHTML;
		
	}
	
	function applyTemplate( $aTweet, $sTemplate ){

		// Dump out the blob of HTML with data embedded
		$aKeys = array(
			'/\{tweet_text}/',
			'/\{tweet_html}/',
			'/\{tweet_id}/',

			'/\{from_user_avatar}/',
			'/\{from_user_id}/',
			'/\{from_user}/',

			'/\{to_user_id}/',
			'/\{to_user}/',
			
			'/\{source_link}/',
			'/\{index}/',
			
			'/\{date_created}/',
			'/\{since}/',
			'/\{date}/'			
		);
		
		$aData = array(
			$aTweet['text'],
			$this->tweet2HTML($aTweet['text']),
			$aTweet['id'],

			$aTweet['profile_image_url'],
			$aTweet['from_user_id'],
			$aTweet['from_user'],

			$aTweet['to_user_id'],
			$aTweet['to_user'],

			$aTweet['source'],
			$aTweet['index'],
			
			$aTweet['created_at'],
			human_time_diff( strtotime($aTweet['created_at']) 
			)
			
		);

		// Merge $aTags and $aData
		return preg_replace($aKeys, $aData, $sTemplate);
	
	}

}

function twitsearch_widget($atts) {
    
    global $wp_widget_factory;

    extract(shortcode_atts(array(
        'widget_name' => FALSE,
        'title' => FALSE,
        'query' => FALSE,
        'show' => 3,
        'custom_css' => FALSE,
        'twitter_search_template' => '<li class="tweet tweet_{index}"><p><span class="who"><img src="{from_user_avatar}" alt="{from_user}\'s avatar"/></span><a href="http://twitter.com/{from_user}" class="{from_user}">{from_user}</a> {tweet_html}</p><small class="time">{since} ago</small> <small class="reply"><a title="Reply to {from_user}" href="http://twitter.com/?status=@{from_user}  &in_reply_to_status_id={tweet_id}&in_reply_to={from_user}">Reply</a></small></li>',
        'twitter_before' => '<ul>',
        'twitter_after' => '</ul>'
    ), $atts));
    
    $widget_name = wp_specialchars('Twitter_Search_Widget');
    
    if (!is_a($wp_widget_factory->widgets[$widget_name], 'WP_Widget')):
        $wp_class = 'WP_Widget_'.ucwords(strtolower($class));
        
        if (!is_a($wp_widget_factory->widgets[$wp_class], 'WP_Widget')):
            return '<p>'.sprintf(__("%s: Widget class not found. Make sure this widget exists and the class name is correct"),'<strong>'.$class.'</strong>').'</p>';
        else:
            $class = $wp_class;
        endif;
    endif;
    
	/* Strip tags for title and name to remove HTML (important for text inputs). */
	$instance['title'] = strip_tags( $title );
	$instance['twitter_search_query'] = strip_tags( $query );
	$instance['twitter_search_show'] = strip_tags( $show );
	$instance['twitter_use_custom_css'] = $custom_css;
	$instance['twitter_search_template'] = $twitter_search_template;
	$instance['twitter_before'] = $twitter_before;
	$instance['twitter_after'] = $twitter_after;
	
    ob_start();
    the_widget($widget_name, $instance, array('widget_id'=>'arbitrary-instance-'.$id) );
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
    
}
add_shortcode('twitter_search','twitsearch_widget');