<?php
/*
Plugin Name: TweetUpdater
Description: WordPress plugin to update Twitter status when you create or publish a post.
Version: 3.x
Author: Patrick Fenner, Jordan Trask "@comm"
Author URI: http://def-proc.co.uk/Projects/TweetUpdater
Based on TwitterUpdater, version 1.0 by Victoria Chan: http://blog.victoriac.net/?p=87

*/

require_once('twitteroauth.php');

/* Default Values */

// TweetUpdater Consumer Keys for Twitter API. You can use your own keys instead by entering them on the admin page.
global $consumer_key, $consumer_secret;
$consumer_key = 'o1WYNgwG1PMHW2Lh5ceYQ';
$consumer_secret = 'OUygrqtDDueidU9qRGjuvOdz3JaVyyoCFkDgRBIMerI';

// Set defaults on first load (won't overwrite if the _auth and _options are already set).
	function tweet_updater_activate()
	{
		global $consumer_key, $consumer_secret;
		
		$tokens_default = array(
			'consumer_key' => $consumer_key,
			'consumer_secret' => $consumer_secret,
			'default_consumer_keys' => '1',
			'request_key' => '',
			'request_secret' => '',
			'request_link' => '',
			'access_key' => '',
			'access_secret' => '',
			'auth1_flag' => '0',
			'auth2_flag' => '0',
			'auth3_flag' => '0',
				);
		
		add_option( 'tweet_updater_auth', $tokens_default, ' ', 'no' );
		
		$options_default = array(
			'newpost-update' => '1',
			'newpost-format' => 'New blog post: #title#: #url#',
			'edited-update' => '1',
			'edited-format' => 'Updated blog post: #title#: #url#',
			'short-method' => '0',
			'url-method' => '2',
	                'bitly_username' => '',
	                'bitly_appkey' => '',
				);
		
		add_option( 'tweet_updater_options', $options_default, ' ', 'no' );
	}



/* Admin Page */

// add the admin options page
	function tweet_updater_admin_add_page() 
	{
		add_options_page( 'TweetUpdater', 'TweetUpdater Options', 'manage_options', 'TweetUpdater', 'tweet_updater_options_page' );

		// add the hook for the admin settings field
		add_action( 'admin_init', 'tweet_updater_admin_init' );
	}

// display the admin options page
	function tweet_updater_options_page() 
	{
		$tokens = get_option('tweet_updater_auth');
		$changed_tokens = get_option('tweet_updater_auth_changed');
		$tokens = tweet_updater_update_tokens( $tokens, $changed_tokens );

		$options = get_option('tweet_updater_options');
		
		//Twitter Autorisation form
	?>
		<div>
		
		<h2>Twitter Authorisation</h2>
		Setup TweetUpdater to be able to post to your twitter account<br />
		(Username and Password access is no longer supported by Twitter)
		<form action="options.php" method="post">
		<?php settings_fields('tweet_updater_auth'); 
		
		// Logic to display the correct form, depending on authorisation stage (1-4)
		if( $tokens['auth1_flag'] != '1' )
		{
			update_option('tweet_updater_auth', $tokens);
			do_settings_sections('auth_1'); 
			?><input name="Submit" type="submit" value="<?php esc_attr_e('Register'); ?>" /><?php
		} 
		elseif( $tokens['auth1_flag'] == '1' && $tokens['auth2_flag'] != '1' )
		{
			// Check if using default consumer keys
			if( $tokens['default_consumer_keys'] == "1" )
			{
				global $consumer_key, $consumer_secret; 
				$tokens['consumer_key'] = $consumer_key;
				$tokens['consumer_secret'] = $consumer_secret;
			}
			
			//do registration and generate the register link
			$tokens = tweet_updater_register($tokens);
			update_option('tweet_updater_auth', $tokens);
			
			do_settings_sections('auth_2'); 
			?><input name="Submit" type="submit" value="<?php esc_attr_e('Authorise'); ?>" /><?php
		} 
		else
		{
			if ( $tokens['auth2_flag'] == '1' && $tokens['auth3_flag'] != '1' )
			{
			//do authorisation
				$tokens = tweet_updater_authorise($tokens);
			}
			
			//do validation
			$verify = tweet_updater_verify($tokens);

			switch ($verify) 
			{
			case '1':
				echo "<p>Return message is valid</p>";
				$tokens['auth3_flag'] = '1'; //(Will only validate until reset)
				update_option('tweet_updater_auth', $tokens);
				?><input name="Submit" type="submit" value="<?php esc_attr_e('Check again'); ?>" /><?php 
				break;
			case '2':
				echo "<p>Not able to validate access to account, Twitter is currently unavailable. Try checking again in a couple of minutes.</p>";
				$tokens['auth3_flag'] = '1'; //(Will validate next time)
				update_option('tweet_updater_auth', $tokens);
				?><input name="Submit" type="submit" value="<?php esc_attr_e('Check again'); ?>" /><?php
				break;
			case '3':
				echo "<p>TweetUpdater has not been authorised to access your twitter account.</p>";
				$tokens['auth3_flag'] = '0';
				$tokens['auth2_flag'] = '0';
				update_option('tweet_updater_auth', $tokens);
				do_settings_sections('auth_2'); 
				?><input name="Submit" type="submit" value="<?php esc_attr_e('Check again'); ?>" /><?php
				break;
			default:
				echo "<p>TweetUpdater is not currently authorised to use any account. Reset and try again.</p>";
				update_option('tweet_updater_auth', $tokens);
			}
		} 
		?></form>
		
		<form action="options.php" method="post">
		<?php settings_fields('tweet_updater_auth'); ?>
		<?php do_settings_sections('auth_reset'); ?>
		<input name="Submit" type="submit" value="<?php esc_attr_e('Reset'); ?>" />
		</form>
		</div>
	
	<?php 
		/* debug code to check database values */
		echo "<p>\$tokens: <br /><pre>";
		print_r( $tokens );
		echo "</pre></p>";
		echo "<p>\$options: <br /><pre>";
		print_r( $options );
		echo "</pre></p>"; 
		/* end */
	?>
	<?php
	}

// add the fields for the plugin settings
	function tweet_updater_admin_init()
	{
	// Settings for OAuth procedure with Twitter
		register_setting( 'tweet_updater_auth', 'tweet_updater_auth_changed'); //, 'tweet_updater_auth_validate' );

		//First Step: Consumer Keys
			add_settings_section('tweet_updater_consumer_keys', 'Consumer Keys', 'tweet_updater_auth_1', 'auth_1');
			add_settings_field('tweet_updater_consumer_key', 'Consumer Key', 'tweet_updater_consumer_key', 'auth_1', 'tweet_updater_consumer_keys');
			add_settings_field('tweet_updater_consumer_secret', 'Consumer Secret', 'tweet_updater_consumer_secret', 'auth_1', 'tweet_updater_consumer_keys');
			add_settings_field('tweet_updater_consumer_default', 'Use Default Consumer Keys?', 'tweet_updater_consumer_default', 'auth_1', 'tweet_updater_consumer_keys');
			add_settings_field('tweet_updater_auth1_flag', '', 'tweet_updater_auth1_flag', 'auth_1', 'tweet_updater_consumer_keys');

		//Second: Get Reg. keys (no options)
			add_settings_section('tweet_updater_register_keys', 'Consumer Keys', 'tweet_updater_auth_2', 'auth_2');
			add_settings_field('tweet_updater_auth2_flag', '', 'tweet_updater_auth2_flag', 'auth_2', 'tweet_updater_register_keys');

	// Reset Button: (no options, all hidden)
			add_settings_section('tweet_updater_reset', 'Consumer Keys', 'tweet_updater_reset', 'auth_reset');
			add_settings_field('tweet_updater_auth1_reset', '', 'tweet_updater_auth1_reset', 'auth_reset', 'tweet_updater_reset');
			add_settings_field('tweet_updater_auth2_reset', '', 'tweet_updater_auth2_reset', 'auth_reset', 'tweet_updater_reset');
			add_settings_field('tweet_updater_auth3_reset', '', 'tweet_updater_auth3_reset', 'auth_reset', 'tweet_updater_reset');
			add_settings_field('tweet_updater_req_key_reset', '', 'tweet_updater_req_key_reset', 'auth_reset', 'tweet_updater_reset');
			add_settings_field('tweet_updater_req_sec_reset', '', 'tweet_updater_req_sec_reset', 'auth_reset', 'tweet_updater_reset');
			add_settings_field('tweet_updater_acc_key_reset', '', 'tweet_updater_acc_key_reset', 'auth_reset', 'tweet_updater_reset');
			add_settings_field('tweet_updater_acc_sec_reset', '', 'tweet_updater_acc_sec_reset', 'auth_reset', 'tweet_updater_reset');

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
	function tweet_updater_auth_1() 
	{ 
		echo '<p>Set your Twitter API Consumer Keys here. If you prefer not to use the default TweetUpdater API keys, you can use your own keys here instead. You can get twitter API keys by registering a new application at <a href="http://twitter.com/apps">http://twitter.com/apps</a></p><p>This plugin is not yet authenicated with Twitter</p>'; 
	}
	
	function tweet_updater_consumer_key() 
	{ 
		$tokens = get_option('tweet_updater_auth'); 
		echo "<input id='tweet_updater_consumer_key' type='text' name='tweet_updater_auth_changed[consumer_key]' value='{$tokens['consumer_key']}' />"; 
	}
	
	function tweet_updater_consumer_secret() 
	{ 
		$tokens = get_option('tweet_updater_auth'); 
		echo "<input id='tweet_updater_consumer_secret' type='text' name='tweet_updater_auth_changed[consumer_secret]' value='{$tokens['consumer_secret']}' />"; 
	}

	function tweet_updater_consumer_default() 
	{ 
		$tokens = get_option('tweet_updater_auth'); 
		echo "<input id='tweet_updater_consumer_default' type='checkbox' name='tweet_updater_auth_changed[default_consumer_keys]' value='1' checked='{$tokens['default_consumer_keys']}' />"; 
	}

	function tweet_updater_auth1_flag() 
	{ 
		echo "<input id='tweet_updater_auth1_flag' type='hidden' name='tweet_updater_auth_changed[auth1_flag]' value='1' />"; 
	}

// Request link form
	function tweet_updater_auth_2() 
	{ 
		$tokens = get_option('tweet_updater_auth'); 
		echo "<p>Now you need to tell twitter you want to allow TweetUpdater to be able to post to your account. Follow the instructions at <a href='{$tokens['request_link']}'>{$tokens['request_link']}</a> and come back to this page to complete the process.</p>"; 
	}
	
	function tweet_updater_auth2_flag() 
	{ 
		echo "<input id='tweet_updater_auth2_flag' type='hidden' name='tweet_updater_auth_changed[auth2_flag]' value='1' />"; 
	}

// Hidden status' for OAuth reset button
	function tweet_updater_reset() 
	{ 
		echo 'Or reset the authentication process:';
	}
	
	function tweet_updater_auth1_reset() 
	{ 
		echo "<input id='tweet_updater_auth1_reset' type='hidden' name='tweet_updater_auth_changed[auth1_flag]' value='0' />"; 
	}

	function tweet_updater_auth2_reset() 
	{ 
		echo "<input id='tweet_updater_auth2_reset' type='hidden' name='tweet_updater_auth_changed[auth2_flag]' value='0' />"; 
	}

	function tweet_updater_auth3_reset() 
	{ 
		echo "<input id='tweet_updater_auth3_reset' type='hidden' name='tweet_updater_auth_changed[auth3_flag]' value='0' />"; 
	}

	function tweet_updater_req_key_reset() 
	{ 
		echo "<input id='tweet_updater_req_key_reset' type='hidden' name='tweet_updater_auth_changed[request_key]' value='' />"; 
	}

	function tweet_updater_req_sec_reset() 
	{ 
		echo "<input id='tweet_updater_req_sec_reset' type='hidden' name='tweet_updater_auth_changed[request_secret]' value='' />"; 
	}

	function tweet_updater_acc_key_reset() 
	{ 
		echo "<input id='tweet_updater_acc_key_reset' type='hidden' name='tweet_updater_auth_changed[access_key]' value='' />"; 
	}

	function tweet_updater_acc_sec_reset() 
	{ 
		echo "<input id='tweet_updater_acc_sec_reset' type='hidden' name='tweet_updater_auth_changed[access_secret]' value='' />"; 
	}

// Multi-screen authorisation needs to change the $tokens array, not just save the fields that change (doh!)
	function tweet_updater_update_tokens( $tokens, $changed_tokens )
	{
		if( $changed_tokens['consumer_key'] != NULL ) { $tokens['consumer_key'] = $changed_tokens['consumer_key']; }
		if( $changed_tokens['consumer_secret'] != NULL ) { $tokens['consumer_secret'] = $changed_tokens['consumer_secret']; }
		if( $changed_tokens['default_consumer_keys'] != NULL ) { $tokens['default_consumer_keys'] = $changed_tokens['default_consumer_keys']; }
		if( $changed_tokens['request_key'] != NULL ) { $tokens['request_key'] = $changed_tokens['request_key']; }
		if( $changed_tokens['request_secret'] != NULL ) { $tokens['request_secret'] = $changed_tokens['request_secret']; }
		if( $changed_tokens['request_link'] != NULL ) { $tokens['request_link'] = $changed_tokens['request_link']; }
		if( $changed_tokens['access_key'] != NULL ) { $tokens['access_key'] = $changed_tokens['access_key']; }
		if( $changed_tokens['access_secret'] != NULL ) { $tokens['access_secret'] = $changed_tokens['access_secret']; }
		if( $changed_tokens['auth1_flag'] != NULL ) { $tokens['auth1_flag'] = $changed_tokens['auth1_flag']; }
		if( $changed_tokens['auth2_flag'] != NULL ) { $tokens['auth2_flag'] = $changed_tokens['auth2_flag']; }
		if( $changed_tokens['auth3_flag'] != NULL ) { $tokens['auth3_flag'] = $changed_tokens['auth3_flag']; }

		return $tokens;
	}
	function tweet_updater_auth_validate($input) 
	{
		$options = get_option('tweet_updater_auth');

		if( $input['consumer_key'] != NULL ) { $options['consumer_key'] = $input['consumer_key']; }
		if( $input['consumer_secret'] != NULL ) { $options['consumer_secret'] = $input['consumer_secret']; }
		if( $input['default_consumer_keys'] != NULL ) { $options['default_consumer_keys'] = $input['default_consumer_keys']; }
		if( $input['request_key'] != NULL ) { $options['request_key'] = $input['request_key']; }
		if( $input['request_secret'] != NULL ) { $options['request_secret'] = $input['request_secret']; }
		if( $input['request_link'] != NULL ) { $options['request_link'] = $input['request_link']; }
		if( $input['access_key'] != NULL ) { $options['access_key'] = $input['access_key']; }
		if( $input['access_secret'] != NULL ) { $options['access_secret'] = $input['access_secret']; }
		if( $input['auth1_flag'] != NULL ) { $options['auth1_flag'] = $input['auth1_flag']; }
		if( $input['auth2_flag'] != NULL ) { $options['auth2_flag'] = $input['auth2_flag']; }
		if( $input['auth3_flag'] != NULL ) { $options['auth3_flag'] = $input['auth3_flag']; }

		return $options;
	}


/* Twitter OAuth Functions */

function tweet_updater_register($tokens)
{
	// Build TwitterOAuth object with client credentials. 
	$connection = new TwitterOAuth($tokens['consumer_key'], $tokens['consumer_secret']);

	$request = $connection->getRequestToken();
	
	// Retrive tokens from request and store in array
	$tokens['request_key'] = $request["oauth_token"];
	$request_secret = $request["oauth_token_secret"];
	
	// Generate a request link and output it
	$tokens['request_link'] = $connection->getAuthorizeURL($request);
	
	return $tokens;
}

function tweet_updater_authorise($tokens)
{	
	// Initiate a new TwitterOAuth object. Provide the request token and request token secret
	$connection = new TwitterOAuth($tokens['consumer_key'], $tokens['consumer_secret'], $tokens['request_token'], $tokens['request_secret']);
	
	// Ask Twitter for an access token (and an access token secret)
	$request = $connection->getAccessToken();
	
	/*
	 ------------------------------------------------------------------------------------------
	 *** A failed request (e.g. not authorised by user in twitter) is not handled at all  	***
	 *** and breaks the script during getAccessToken() 					***
	 ------------------------------------------------------------------------------------------
	 */
	 
	// Retrieve access token from request:
	$tokens['access_key'] = $request['oauth_token'];
	$tokens['access_secret'] = $request['oauth_token_secret'];

	return $tokens;
}


function tweet_updater_verify($tokens)
{
	// Initiate TwitterOAuth using access tokens
	$connection = new TwitterOAuth($tokens['consumer_key'], $tokens['consumer_secret'], $tokens['access_key'], $tokens['access_secret']);
	
	$result = $connection->get('account/verify_credentials');
	
	if ($result->id)
	{
		$verify = "1";
	}
	else
	{
		$verify = "0";
	}

	return $verify;
}



/* Hooks */

// Action for when a post is published/edited (but not just saved)
//	add_action( 'publish_post', 'vc_twit_current', 1, 1 );
// Action for when a future post is published
//	add_action( 'future_to_publish', 'vc_twit_future', 1, 1 );
// add the admin options page
	add_action( 'admin_menu', 'tweet_updater_admin_add_page' );
// Intialise on first activation
	register_activation_hook( __FILE__, 'tweet_updater_activate' );

?>
