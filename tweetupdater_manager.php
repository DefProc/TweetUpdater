<?php

	/*** Default Values ***/


/* TweetUpdater Consumer Keys for Twitter API. You can use your own keys instead by entering them on the admin page. */

global $tweet_updater_default_consumer_key, $tweet_updater_default_consumer_secret, $tweet_updater_placeholders;
$tweet_updater_default_consumer_key = 'o1WYNgwG1PMHW2Lh5ceYQ';
$tweet_updater_default_consumer_secret = 'OUygrqtDDueidU9qRGjuvOdz3JaVyyoCFkDgRBIMerI';
$tweet_updater_placeholders = "<br />
			Placeholders:
			<ul>
			<li>#title# - Replaced by page title</li>
			<li>#url# - Replaced by URL as selected below</li>
			</ul>"; 


/* Set defaults on first load. */

function tweet_updater_activate()
{
	// This won't overwrite the enries if the tokens and options are already set.
	global $tweet_updater_default_consumer_key, $tweet_updater_default_consumer_secret;
	
	$tokens_default = array(
		'consumer_key' => $tweet_updater_default_consumer_key,
		'consumer_secret' => $tweet_updater_default_consumer_secret,
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
	
	add_option( 'tweet_updater_auth', $tokens_default, '', 'no' );
	
	$options_default = array(
		'newpost_update' => '1',
		'newpost_format' => 'New blog post: #title#: #url#',
		'edited_update' => '1',
		'edited_format' => 'Updated blog post: #title#: #url#',
		'use_curl' => '1',
		'url_method' => 'tinyurl',
                'bitly_username' => '',
                'bitly_appkey' => '',
				);
	
	add_option( 'tweet_updater_options', $options_default, '', 'no' );
}




	/*** Admin Page ***/



/* Add the Admin Options Page to the Settings Menu */

function tweet_updater_admin_add_page() 
{
	add_options_page( 'TweetUpdater', 'TweetUpdater', 'manage_options', 'TweetUpdater', 'tweet_updater_options_page' );

	// add the hook for the admin settings field
	add_action( 'admin_init', 'tweet_updater_admin_init' );

}

/* Add settings link on plugins admin page */

function tweet_updater_add_settings_link( $links ) 
{ 
	$settings_link = '<a href="options-general.php?page=TweetUpdater">Settings</a>'; 
	array_unshift( $links, $settings_link ); 
	
	return $links; 
}
 


/* Display the Admin Options Page */

function tweet_updater_options_page() 
{
	$tokens = get_option('tweet_updater_auth');
	$options = get_option('tweet_updater_options');

	//style settings for form:
?>
	<style type="text/css">
		div.hid		{ visibility: hidden; overflow: hidden; height: 1px; }
		fieldset	{ margin: 20px 0; border: 1px solid #cecece; padding: 15px; }
	</style>
<?php	
	//Twitter Authorisation form
?>
	<div class="wrap">
	<div id="icon-options-general" class="icon32"><br></div>
	<h2>TweetUpdater</h2>
	<div class="clear"></div>
		<hr>
		<h2>Twitter Account</h2>
 
		TweetUpdater uses OAuth authentication to connect to Twitter (basic authenitication is denied for applications).<br/>
		Follow the authentication process below to authorise TweetUpdater access on your Twitter account.<br />
		<form action="options.php" method="post">
<?php 		settings_fields('tweet_updater_auth'); 
		
		// Logic to display the correct form, depending on authorisation stage
		if( $tokens['auth1_flag'] != '1' )
		{
			update_option('tweet_updater_auth', $tokens);
			do_settings_sections('auth_1'); 
?>			<div class="error"><p><strong>TweetUpdater does not have access to a Twitter account yet.</strong></p></div>
			<p class="submit" ><input name="Submit" class="button-primary"  type="submit" value="<?php esc_attr_e('Register'); ?>" /></p>
<?php		} 
		elseif( $tokens['auth1_flag'] == '1' && $tokens['auth2_flag'] != '1' )
		{
			// Check if using default consumer keys
			if( $tokens['default_consumer_keys'] == "1" )
			{
				global $tweet_updater_default_consumer_key, $tweet_updater_default_consumer_secret; 
				$tokens['consumer_key'] = $tweet_updater_default_consumer_key;
				$tokens['consumer_secret'] = $tweet_updater_default_consumer_secret;
			}
			
			//do registration and generate the register link
			$tokens = tweet_updater_register($tokens);
			update_option('tweet_updater_auth', $tokens);
		
			do_settings_sections('auth_2'); 
?>			<p class="submit" ><input name="Submit" class="button-primary"  type="submit" value="<?php esc_attr_e('Authorise'); ?>" /></p>
<?php		} 
		else
		{
			if ( $tokens['auth2_flag'] == '1' && $tokens['auth3_flag'] != '1' )
			{
			//do authorisation
				$tokens = tweet_updater_authorise($tokens);
			}
			
			//do validation
			$verify = tweet_updater_verify($tokens);
			switch ($verify['exit_code']) 
			{
			case '1':
				echo "<div class='message updated'><p><strong>Connection checked OK. TweetUpdater can post to <a href='http://twitter.com/{$verify['user_name']}'>@{$verify['user_name']}</a></strong></p></div>";
				$tokens['auth3_flag'] = '1'; //Will only validate until reset
				update_option('tweet_updater_auth', $tokens);
?>				<p class="submit" ><input name="Refresh" class="button-primary"  type="button" value="<?php esc_attr_e('Check again'); ?>" onClick="history.go(0)" /></p>
<?php 				break;
			case '2':
				echo "<div class='error'><p><strong>Not able to validate access to account, Twitter is currently unavailable. Try checking again in a couple of minutes.</strong></p></div>";
				$tokens['auth3_flag'] = '1'; //Will validate next time
				update_option('tweet_updater_auth', $tokens);
?>				<p class="submit" ><input name="Refresh" class="button-primary"  type="button" value="<?php esc_attr_e('Check again'); ?>" onClick="history.go(0)" /></p>
<?php				break;
			case '3':
				echo "<div class='error'><p><strong>TweetUpdater does not have access to a Twitter account yet.</strong></p></div>";
				$tokens['auth3_flag'] = '0';
				update_option('tweet_updater_auth', $tokens);
				do_settings_sections('auth_2'); 
?>				<p class="submit" ><input name="Submit" class="button-primary"  type="submit" value="<?php esc_attr_e('Authorise'); ?>" /></p>
<?php				break;
			default:
				echo "<div class='warning'>TweetUpdater is not currently authorised to use any account. Please reset and try again.</strong></p></div>";
				update_option('tweet_updater_auth', $tokens);
			}
		} 
?>
		</form>
<?php	// Button to reset OAuth process ?>
		<form action="options.php" method="post">
		<?php settings_fields('tweet_updater_auth'); ?>
		<p><strong>Or restart the authorisation procedure: </strong> <span class="_submit" ><input name="Submit" class="button-secondary"  type="submit" value="<?php esc_attr_e('Reset'); ?>" /></span></p>
			<div class="hid">	
				<?php do_settings_sections('auth_reset'); // the hidden fields populate a padded table that has to be hidden by css. Feels like bit of a hack really.?>
			</div>
			
		</form>
		<hr>
<?php	// TweetUpdater Options form ?>
		<h2>Options</h2>
		<form action="options.php" method="post">
			<?php settings_fields('tweet_updater_options'); ?>
			<?php do_settings_sections('new_post'); ?> 
			<?php do_settings_sections('edited_post'); ?> 
			<?php do_settings_sections('short_url'); ?> 
			<fieldset><?php do_settings_sections('url_method'); ?></fieldset>
			<p class="submit" ><input name="Submit" class="button-primary"  type="submit" value="<?php esc_attr_e('Save Options'); ?>" />
		</form>
	</div>

<?php
}

/* Set the Allowed Form Fields */

function tweet_updater_admin_init()
{
// Settings for OAuth procedure with Twitter
register_setting( 'tweet_updater_auth', 'tweet_updater_auth', 'tweet_updater_auth_validate' );

	// Consumer Key fields
	add_settings_section('tweet_updater_consumer_keys', 'Consumer Keys:', 'tweet_updater_auth_1', 'auth_1');
		add_settings_field('tweet_updater_consumer_key', 'Consumer Key', 'tweet_updater_consumer_key', 'auth_1', 'tweet_updater_consumer_keys');
		add_settings_field('tweet_updater_consumer_secret', 'Consumer Secret', 'tweet_updater_consumer_secret', 'auth_1', 'tweet_updater_consumer_keys');
		add_settings_field('tweet_updater_consumer_default', 'Use Default Consumer Keys?', 'tweet_updater_consumer_default', 'auth_1', 'tweet_updater_consumer_keys');
		add_settings_field('tweet_updater_auth1_flag', '', 'tweet_updater_auth1_flag', 'auth_1', 'tweet_updater_consumer_keys');

	// Register Keys switch
	add_settings_section('tweet_updater_register_keys', 'Register with Twitter:', 'tweet_updater_auth_2', 'auth_2');
		add_settings_field('tweet_updater_auth2_flag', '', 'tweet_updater_auth2_flag', 'auth_2', 'tweet_updater_register_keys');

	// Reset button fields
	add_settings_section('tweet_updater_reset', 'Reset OAuth:', 'tweet_updater_reset', 'auth_reset');
		add_settings_field('tweet_updater_auth1_reset', '', 'tweet_updater_auth1_reset', 'auth_reset', 'tweet_updater_reset');
		add_settings_field('tweet_updater_auth2_reset', '', 'tweet_updater_auth2_reset', 'auth_reset', 'tweet_updater_reset');
		add_settings_field('tweet_updater_auth3_reset', '', 'tweet_updater_auth3_reset', 'auth_reset', 'tweet_updater_reset');
		add_settings_field('tweet_updater_req_key_reset', '', 'tweet_updater_req_key_reset', 'auth_reset', 'tweet_updater_reset');
		add_settings_field('tweet_updater_req_sec_reset', '', 'tweet_updater_req_sec_reset', 'auth_reset', 'tweet_updater_reset');
		add_settings_field('tweet_updater_req_link_reset', '', 'tweet_updater_req_link_reset', 'auth_reset', 'tweet_updater_reset');
		add_settings_field('tweet_updater_acc_key_reset', '', 'tweet_updater_acc_key_reset', 'auth_reset', 'tweet_updater_reset');
		add_settings_field('tweet_updater_acc_sec_reset', '', 'tweet_updater_acc_sec_reset', 'auth_reset', 'tweet_updater_reset');

// Settings for TweetUpdater
register_setting( 'tweet_updater_options', 'tweet_updater_options', 'tweet_updater_options_validate' );
		
	//Section 1: New Post published
	add_settings_section('tweet_updater_new_post', 'Newly Published Post:', 'tweet_updater_new_post', 'new_post');
		add_settings_field('tweet_updater_newpost_update', 'Update Twitter when a new post is published?', 'tweet_updater_newpost_update', 'new_post', 'tweet_updater_new_post');
		add_settings_field('tweet_updater_newpost_format', 'Tweet format for a new post:', 'tweet_updater_newpost_format', 'new_post', 'tweet_updater_new_post');
			
	//Section 2: Updated Post
	add_settings_section('tweet_updater_edited_post', 'Published Post Updated:', 'tweet_updater_edited_post', 'edited_post');
		add_settings_field('tweet_updater_edited_update', 'Update Twitter when a published post is updated?', 'tweet_updater_edited_update', 'edited_post', 'tweet_updater_edited_post');
		add_settings_field('tweet_updater_edited_format', 'Tweet format for an updated post:', 'tweet_updater_edited_format', 'edited_post', 'tweet_updater_edited_post');

	//Section 3: Short Url service
	add_settings_section('tweet_updater_short_url', 'Short URL Service:', 'tweet_updater_short_url', 'short_url');
		add_settings_field('tweet_updater_chose_url', 'Use a #url# from which provider?', 'tweet_updater_chose_url1', 'short_url', 'tweet_updater_short_url');
		add_settings_field('tweet_updater_bitly_username', 'Bit.ly Username', 'tweet_updater_bitly_username', 'short_url', 'tweet_updater_short_url');
		add_settings_field('tweet_updater_bitly_appkey', 'Bit.ly Appkey', 'tweet_updater_bitly_appkey', 'short_url', 'tweet_updater_short_url');

	//Section 4: Use CURL to get short_url?
	add_settings_section('tweet_updater_url_method', 'Use cURL to get external short_urls?', 'tweet_updater_url_method', 'url_method');
    add_settings_field('tweet_updater_use_curl', 'Use cURL for short URLs?', 'tweet_updater_use_curl', 'url_method', 'tweet_updater_url_method');

  // Section 5: Limit tweets to posts with certain custom field/value pair or part of a specific category
  add_settings_section('tweet_updater_limit_tweets', 'Limit tweets on updates and new posts to a certain category or customfield key value pair', 'tweet_updater_limi_tweets' ,'limit_tweets');
  add_settings_field('tweet_updater_limit_by_category', 'Only tweet about new/updated posts in the selected category', 'tweet_updater_limit_by_category', 'limit_tweets' );
	}

/* Return Form components for the Allowed Form Fields */

// Consumer Keys form
function tweet_updater_auth_1() 
	{ echo '<p>Set your Twitter API Consumer Keys here. <br />If you prefer not to use the default TweetUpdater API keys, you can add your own keys here instead. <br />Get consumer keys by registering a new application at <a href="http://twitter.com/apps">http://twitter.com/apps</a>.</p>'; }
function tweet_updater_consumer_key() 
	{ $tokens = get_option('tweet_updater_auth'); echo "<input id='tweet_updater_consumer_key' type='text' size='60' name='tweet_updater_auth[consumer_key]' value='{$tokens['consumer_key']}' />"; }
function tweet_updater_consumer_secret() 
	{ $tokens = get_option('tweet_updater_auth'); echo "<input id='tweet_updater_consumer_secret' type='text' size='60' name='tweet_updater_auth[consumer_secret]' value='{$tokens['consumer_secret']}' />"; }
function tweet_updater_consumer_default() 
	{ $tokens = get_option('tweet_updater_auth'); echo "<input id='tweet_updater_consumer_default' type='checkbox' name='tweet_updater_auth[default_consumer_keys]' value='1' checked='true' />"; }
function tweet_updater_auth1_flag() 
	{ echo "<input id='tweet_updater_auth1_flag' type='hidden' name='tweet_updater_auth[auth1_flag]' value='1' />"; }

// Request link form
function tweet_updater_auth_2() 
	{ $tokens = get_option('tweet_updater_auth'); echo "<p>Now you need to tell Twitter you want to allow TweetUpdater to be able to post using your account. <ol><li>Go to: <a href='{$tokens['request_link']}'>{$tokens['request_link']}</a></li><li>Follow the instructions at page to Allow access for TweetUpdater</li><li>Return to this page to complete the process.</li></ol></p>"; }
function tweet_updater_auth2_flag() 
		{ echo "<input id='tweet_updater_auth2_flag' type='hidden' name='tweet_updater_auth[auth2_flag]' value='1' />"; }

// Hidden status' for OAuth reset button
function tweet_updater_reset() 
	{ echo 'Or reset the authentication process:'; }
function tweet_updater_auth1_reset() 
	{ echo "<input id='tweet_updater_auth1_reset' type='hidden' name='tweet_updater_auth[auth1_flag]' value='0' />"; }
function tweet_updater_auth2_reset() 
	{ echo "<input id='tweet_updater_auth2_reset' type='hidden' name='tweet_updater_auth[auth2_flag]' value='0' />"; }
function tweet_updater_auth3_reset() 
	{ echo "<input id='tweet_updater_auth3_reset' type='hidden' name='tweet_updater_auth[auth3_flag]' value='0' />"; }
function tweet_updater_req_key_reset() 
	{ echo "<input id='tweet_updater_req_key_reset' type='hidden' name='tweet_updater_auth[request_key]' value='NULL' />"; }
function tweet_updater_req_sec_reset() 
	{ echo "<input id='tweet_updater_req_sec_reset' type='hidden' name='tweet_updater_auth[request_secret]' value='NULL' />"; }
function tweet_updater_req_link_reset() 
	{ echo "<input id='tweet_updater_req_link_reset' type='hidden' name='tweet_updater_auth[request_link]' value='NULL' />"; }
function tweet_updater_acc_key_reset() 
	{ echo "<input id='tweet_updater_acc_key_reset' type='hidden' name='tweet_updater_auth[access_key]' value='NULL' />"; }
function tweet_updater_acc_sec_reset() 
	{ echo "<input id='tweet_updater_acc_sec_reset' type='hidden' name='tweet_updater_auth[access_secret]' value='NULL' />"; }

//New Post published
function tweet_updater_new_post()
	{ echo "<p>Set the plugin behaviour for when a new post is published.</p>"; }
function tweet_updater_newpost_update()
	{ $options = get_option('tweet_updater_options'); echo "<input id='tweet_updater_newpost_update' type='checkbox' name='tweet_updater_options[newpost_update]' value='1'"; if( $options['newpost_update'] == '1' ) { echo " checked='true'"; }; echo " />"; }
function tweet_updater_newpost_format()
	{ global $tweet_updater_placeholders; $options = get_option('tweet_updater_options'); echo "<input id='tweet_updater_newpost_format' type='text' size='60' maxlength='146' name='tweet_updater_options[newpost_format]' value='{$options['newpost_format']}' />" . $tweet_updater_placeholders; }

//Updated Post
function tweet_updater_edited_post()
	{ echo "<p>Set the plugin behaviour for when a previously published post is updated and saved.</p>"; }
function tweet_updater_edited_update()
	{ $options = get_option('tweet_updater_options'); echo "<input id='tweet_updater_edited_update' type='checkbox' name='tweet_updater_options[edited_update]' value='1'"; if( $options['edited_update'] == '1' ) { echo " checked='true'"; }; echo " />"; }
function tweet_updater_edited_format()
	{ global $tweet_updater_placeholders; $options = get_option('tweet_updater_options'); echo "<input id='tweet_updater_edited_format' type='text' size='60' maxlength='146' name='tweet_updater_options[edited_format]' value='{$options['edited_format']}' />" . $tweet_updater_placeholders; }


//Short Url service
function tweet_updater_short_url()
	{ echo "<p>Set the url shortener properties. </p>"; }
function tweet_updater_chose_url1()
{ 	$options = get_option('tweet_updater_options'); 
	
	echo "<ul>";
	
	// ZZ.GD
	echo "<li><input id='tweet_updater_chose_url' type='radio' name='tweet_updater_options[url_method]' value='zzgd'";
	if( $options['url_method'] == 'zzgd' ) { echo " checked='true'"; };
	echo " /><label for='tweet_updater_chose_url'>ZZ.GD</label></li>"; 

	// TinyURL
	echo "<li><input id='tweet_updater_chose_url' type='radio' name='tweet_updater_options[url_method]' value='tinyurl'";
	if( $options['url_method'] == 'tinyurl' ) { echo " checked='true'"; };
	echo " /><label for='tweet_updater_chose_url'>TinyURL</label></li>";	

	//Bit.ly
	echo "<li><input id='tweet_updater_chose_url' type='radio' name='tweet_updater_options[url_method]' value='bitly'";
	if( $options['url_method'] == 'bitly' ) { echo " checked='true'"; };
	echo " /><label for='tweet_updater_chose_url'>Bit.ly (set account details below)</label></li>";

	// la_petite_url plugin
	if( function_exists('get_la_petite_url_permalink') )
	{
		echo "<li><input id='tweet_updater_chose_url' type='radio' name='tweet_updater_options[url_method]' value='petite'";
		if( $options['url_method'] == 'petite' ) { echo " checked='true'"; };
		echo " /><label for='tweet_updater_chose_url'>la_petite_url plugin</label></li>"; 
	}

	// Full length WordPress Permalink
	echo "<li><input id='tweet_updater_chose_url' type='radio' name='tweet_updater_options[url_method]' value='permalink'";
	if( $options['url_method'] == 'permalink' ) { echo " checked='true'"; };
	echo " /><label for='tweet_updater_chose_url'>WordPress Permalink (Warning: the number of characters is not checked by TweetUpdater)</label></li>";

	echo "</ul>";
}
function tweet_updater_bitly_username()
	{ $options = get_option('tweet_updater_options'); echo "<input id='tweet_updater_bitly_username' type='text' size='30' name='tweet_updater_options[bitly_username]' value='{$options['bitly_username']}' />"; }
function tweet_updater_bitly_appkey()
	{ $options = get_option('tweet_updater_options'); echo "<input id='tweet_updater_bitly_appkey' type='text' size='30' name='tweet_updater_options[bitly_appkey]' value='{$options['bitly_appkey']}' />"; }


//Alternative short url retrieval method
function tweet_updater_url_method()
	{ echo "<p>Version 2.05 added the option to use php cURL to create and retrieve external short urls instead of file_get_contents(). <br />If you'd prefer to use file_get_contents() for URL retrieval, unselect this checkbox<br />This doesn't affect Twitter communication, which only uses cURL</p>"; }
function tweet_updater_use_curl()
	{ $options = get_option('tweet_updater_options'); echo "<input id='tweet_updater_use_curl' type='checkbox' name='tweet_updater_options[use_curl]' value='1' "; if( $options['use_curl'] == '1' ) { echo " checked='true'"; }; echo " /><label>Some web hosts disable the get_page_contents() function. In this case, you must use cURL."; }

// Limit tweets on new or published posts
function tweet_updater_limit_tweets() 
{ echo "<p>Limit tweets on new or updated posts by a customfield key/value pair or category. This prevents posts not part of this category or lacking the customfield key/value pair from
being tweeted.</p>"; }



/* Form validaton functions */

function tweet_updater_auth_validate($input) //n.b. else statements required for checkboxes
{
	$tokens = get_option('tweet_updater_auth');
	
	// The WordPress Settings API will overwrite arrays in the database with only the fields used in the form
	// To retain all the fields, the use the changed items to update the original array.
	if( $input['consumer_key'] != NULL ) { $tokens['consumer_key'] = $input['consumer_key']; }
	if( $input['consumer_secret'] != NULL ) { $tokens['consumer_secret'] = $input['consumer_secret']; }
	if( $input['default_consumer_keys'] != NULL ) { $tokens['default_consumer_keys'] = $input['default_consumer_keys']; } else { $tokens['default_consumer_keys'] = '0'; }
	if( $input['request_key'] != NULL ) { $tokens['request_key'] = $input['request_key']; }
	if( $input['request_secret'] != NULL ) { $tokens['request_secret'] = $input['request_secret']; }
	if( $input['request_link'] != NULL ) { $tokens['request_link'] = $input['request_link']; }
	if( $input['access_key'] != NULL ) { $tokens['access_key'] = $input['access_key']; }
	if( $input['access_secret'] != NULL ) { $tokens['access_secret'] = $input['access_secret']; }
	if( $input['auth1_flag'] != NULL ) { $tokens['auth1_flag'] = $input['auth1_flag']; }
	if( $input['auth2_flag'] != NULL ) { $tokens['auth2_flag'] = $input['auth2_flag']; }
	if( $input['auth3_flag'] != NULL ) { $tokens['auth3_flag'] = $input['auth3_flag']; }
	
	return $tokens;
}

function tweet_updater_options_validate($input) 
{
	$options = get_option('tweet_updater_options');
	
	// The WordPress Settings API will overwrite arrays in the database with only the fields used in the form
	// To retain all the fields, the use the changed items to update the original array.
	if( $input['newpost_update'] != NULL ) { $options['newpost_update'] = $input['newpost_update']; } else { $options['newpost_update'] = '0'; }
	if( $input['newpost_format'] != NULL ) { $options['newpost_format'] = $input['newpost_format']; }
	if( $input['edited_update'] != NULL ) { $options['edited_update'] = $input['edited_update']; } else { $options['edited_update'] = '0'; }
	if( $input['edited_format'] != NULL ) { $options['edited_format'] = $input['edited_format']; }
	if( $input['use_curl'] != NULL ) { $options['use_curl'] = $input['use_curl']; } else { $options['use_curl'] = '0'; }
	if( $input['url_method'] != NULL ) { $options['url_method'] = $input['url_method']; }
	if( isset( $input['bitly_username'] ) ) { $options['bitly_username'] = $input['bitly_username']; }
	if( isset( $input['bitly_appkey'] ) ) { $options['bitly_appkey'] = $input['bitly_appkey']; }
	
	return $options;
}

?>
