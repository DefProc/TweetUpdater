<?php
/*
Plugin Name: TweetUpdater
Description: WordPress plugin to update Twitter status when you create or publish a post.
Version: 3.x
Author: Patrick Fenner, Jordan Trask "@comm"
Author URI: http://def-proc.co.uk/Projects/TweetUpdater
Based on Version 1.0 by Victoria Chan: http://blog.victoriac.net/?p=87

*/

/* Hooks */

// Action for when a post is published/edited (but not just saved)
//	add_action('publish_post', 'vc_twit_current',1,1);
// Action for when a future post is published
//	add_action('future_to_publish', 'vc_twit_future',1,1);
// add the admin options page
	add_action('admin_menu', 'tweet_updater_admin_add_page');


/* Admin Page */

// add the admin options page
	function tweet_updater_admin_add_page() 
	{
		add_options_page('TweetUpdater', 'TweetUpdater Options', 'manage_options', 'TweetUpdater', 'tweet_updater_options_page');
	}

// display the admin options page
	function tweet_updater_options_page() 
	{
		//Twitter Autorisation form
	?>
		<div>
		<h2>Twitter Authorisation</h2>
		Setup TweetUpdater to be able to post to your twitter account
		(Username and Password access is no longer supported by Twitter)
		<form action="options.php" method="post">
		<?php settings_fields('tweet_updater_auth'); 
	//Need to add logic to display the correct form, depending on authorisation stage (1-4)
			echo "<p>This plugin is not yet authenicated with Twitter</p>";
			do_settings_sections('auth_1'); ?>

			<input name="Submit" type="submit" value="<?php esc_attr_e('Register'); ?>" />
		</form></div>
	
	<?php}

// add the fields for the plugin settings
	add_action('admin_init', 'tweet_updater_admin_init');
	function tweet_updater_admin_init()
	{
	// Settings for OAuth procedure with Twitter
		register_setting( 'tweet_updater_auth', 'tweet_updater_auth' ); // can add 3rd option for validation. eg. 'tweet_updater_auth_validate'
		//First Step: Consumer Keys
			add_settings_section('tweet_updater_consumer_keys', 'Consumer Keys', 'tweet_updater_auth_1', 'auth_1');
			add_settings_field('tweet_updater_consumer_key', 'Consumer Key', 'tweet_updater_consumer_key', 'auth_1', 'tweet_updater_consumer_keys');
			add_settings_field('tweet_updater_consumer_secret', 'Consumer Secret', 'tweet_updater_consumer_secret', 'auth_1', 'tweet_updater_consumer_keys');
			add_settings_field('tweet_updater_consumer_default', 'Use Default Consumer Keys?', 'tweet_updater_consumer_default', 'auth_1', 'tweet_updater_consumer_keys');
		//Second: Get Reg. keys (no options)
		//Third: Get Auth Keys (no options)
		//Forth: Check Validation (no Options)
	// Reset Button: (no options)
		
	// Settings for TweetUpdater
//		register_setting( 'tweet_updater_options', 'tweet_updater_options' );
		//Section 1: New Post published
			//Checkbox: Tweet when published
			//Text: Tweet Format
		//Section 2: Updated Post
			//Checkbox: Tweet when updated
			//Text: Tweet Format
		//Section 3: Alt short url retieval method
			//Checkbox: yes/no
		//Section 4: Short Url service
			//Radio box: ZZ.GD/TinyURL/Bit.ly/le_petite_url/WP_permalink
	}

// Consumer Keys form components
	function tweet_updater_auth_1() { echo '<p>Set your Twitter API Consumer Keys here. If you prefer not to use the default TweetUpdater API keys, you can use your own keys here instead. You can get twitter API keys by registering a new application at <a href="http://twitter.com/apps">http://twitter.com/apps</a></p>'; }
	function tweet_updater_consumer_key() { echo "<input id='tweet_updater_consumer_key' type="text" name='tweet_updater_auth[consumer_key]' value='{$options['consumer_key']}' />"; }
	function tweet_updater_consumer_secret() { echo "<input id='tweet_updater_consumer_secret' type="text" name='tweet_updater_auth[consumer_secret]' value='{$options['consumer_secret']}' />"; }
	function tweet_updater_consumer_default() { echo "<input id='tweet_updater_consumer_default' type="checkbox" name'tweet_updater_auth[default_consumer_keys]' value="1" />"; }




?>

