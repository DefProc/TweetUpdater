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
		'limit_activate' => '0',
		'limit_to_category' => '-1',
		'limit_to_custom_field_key' => '',
		'limit_to_custom_field_val' => '',
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
	
	//If bit.ly is selected, but no account information is present, show a warning
	if ( $options['url_method'] == 'bitly' && ( empty( $options['bitly_username'] ) || empty( $options['bitly_appkey'] ) ) )
	{
		echo "<div class='error'><p><strong>Bit.ly is selected, but Bit.ly account information is missing.</strong></p></div>";
	}
	
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
      <?php do_settings_sections('limit_tweets'); ?>
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
	
	// Section 3: Limit tweets to posts with certain custom field/value pair or part of a specific category
	add_settings_section('tweet_updater_limit_tweets', 'Limit tweets:', 'tweet_updater_limit_tweets' ,'limit_tweets');
		add_settings_field('tweet_updater_limit_activate', 'Limit tweeting using the rules below?', 'tweet_updater_limit_activate', 'limit_tweets', 'tweet_updater_limit_tweets');
		add_settings_field('tweet_updater_limit_to_category', 'Only tweet for posts in the selected category', 'tweet_updater_limit_to_category', 'limit_tweets', 'tweet_updater_limit_tweets');
		add_settings_field('tweet_updater_limit_to_customfield', 'Only tweet for posts with this Meta tag and value', 'tweet_updater_limit_to_customfield', 'limit_tweets', 'tweet_updater_limit_tweets');

	//Section 4: Short Url service
	add_settings_section('tweet_updater_short_url', 'Short URL Service:', 'tweet_updater_short_url', 'short_url');
		add_settings_field('tweet_updater_chose_url', 'Use a #url# from which provider?', 'tweet_updater_chose_url1', 'short_url', 'tweet_updater_short_url');

	//Section 5: Use CURL to get short_url?
	add_settings_section('tweet_updater_url_method', 'Use cURL to get external short_urls?', 'tweet_updater_url_method', 'url_method');
    add_settings_field('tweet_updater_use_curl', 'Use cURL for short URLs?', 'tweet_updater_use_curl', 'url_method', 'tweet_updater_url_method');

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
	{ echo "<input id='tweet_updater_req_key_reset' type='hidden' name='tweet_updater_auth[request_key]' value='' />"; }
function tweet_updater_req_sec_reset() 
	{ echo "<input id='tweet_updater_req_sec_reset' type='hidden' name='tweet_updater_auth[request_secret]' value='' />"; }
function tweet_updater_req_link_reset() 
	{ echo "<input id='tweet_updater_req_link_reset' type='hidden' name='tweet_updater_auth[request_link]' value='' />"; }
function tweet_updater_acc_key_reset() 
	{ echo "<input id='tweet_updater_acc_key_reset' type='hidden' name='tweet_updater_auth[access_key]' value='' />"; }
function tweet_updater_acc_sec_reset() 
	{ echo "<input id='tweet_updater_acc_sec_reset' type='hidden' name='tweet_updater_auth[access_secret]' value='' />"; }

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

  // Limit tweets to Categories and Custom Fields
function tweet_updater_limit_tweets() 
	{ echo "<p>Sending of tweets can be limited to posts of a selected category, or that have a specified Custom Field (Meta) title and/or value.</p>"; }
function tweet_updater_limit_activate()
	{ $options = get_option('tweet_updater_options'); echo "<input id='tweet_updater_limit_activate' type='checkbox' name='tweet_updater_options[limit_activate]' value='1'"; if( $options['limit_activate'] == '1' ) { echo " checked='true'"; }; echo " />"; }
function tweet_updater_limit_to_category() 
	{
	$options = get_option('tweet_updater_options');
	$args = array( 
		'name' => 'tweet_updater_options[limit_to_category]',
		'selected' => $options['limit_to_category'],
		'show_option_none' => 'Do not limit to any category'  
			);
	wp_dropdown_categories($args);
	}
function tweet_updater_limit_to_customfield()
	{
	$options = get_option('tweet_updater_options');
	echo "<input id='tweet_updater_limit_to_custom_field_key' type='text' size='20' maxlength='250' name='tweet_updater_options[limit_to_custom_field_key]' value='{$options['limit_to_custom_field_key']}' />";
	echo "<label for='tweet_updater_limit_to_custom_field_key'> Custom Field Title (key)</label><br />";
	echo "<input id='tweet_updater_limit_to_custom_field_val' type='text' size='20' maxlength='250' name='tweet_updater_options[limit_to_custom_field_val]' value='{$options['limit_to_custom_field_val']}' />";
	echo "<label for='tweet_updater_limit_to_custom_field_val'> Custom Field Value (leave blank to match any value)</label>";
	}



//Short Url service
function tweet_updater_short_url()
	{ echo "<p>Set the URL shortener properties. Chose from either in internal or external URL source.</p>"; }
function tweet_updater_chose_url1()
{ 	$options = get_option('tweet_updater_options'); 
	
	echo "<h4>Internal URL sources:</h4>
		<ul>";
	
	// la_petite_url plugin
	if( function_exists('get_la_petite_url_permalink') )
	{
		echo "<li><input id='tweet_updater_chose_url' type='radio' name='tweet_updater_options[url_method]' value='petite'";
		if( $options['url_method'] == 'petite' ) { echo " checked='true'"; };
		echo " /><label for='tweet_updater_chose_url'>la_petite_url plugin. <a href='options-general.php?page=le-petite-url/la-petite-url-options.php'>Settings</a></label></li>"; 
	}

	// Full length WordPress Permalink
	echo "<li><input id='tweet_updater_chose_url' type='radio' name='tweet_updater_options[url_method]' value='permalink'";
	if( $options['url_method'] == 'permalink' ) { echo " checked='true'"; };
	echo " /><label for='tweet_updater_chose_url'>WordPress Permalink (Warning: the number of characters is not checked by TweetUpdater)</label></li>";

	echo "</ul>
		<h4>External URL Shortening Service:</h4>
		<ul>";

	//Bit.ly
	echo "<li><input id='tweet_updater_chose_url' type='radio' name='tweet_updater_options[url_method]' value='bitly'";
	if( $options['url_method'] == 'bitly' ) { echo " checked='true'"; };
	echo " /><label for='tweet_updater_chose_url'>Bit.ly (set your account details below) <a href='http://bit.ly/a/your_api_key'>http://bit.ly</a></label>";
		//Bit.ly Options
		echo "	<ul style='margin-left: 3em; margin-top: 0.5em;'>
			<li><label for='tweet_updater_bitly_username'>Username: </label><input id='tweet_updater_bitly_username' type='text' size='20' name='tweet_updater_options[bitly_username]' value='{$options['bitly_username']}' /></li>
			<li><label for='tweet_updater_bitly_appkey'>API Key: </label><input id='tweet_updater_bitly_appkey' type='text' size='50' name='tweet_updater_options[bitly_appkey]' value='{$options['bitly_appkey']}' /></li>
			</ul>";
	echo "</li>";
	
	// Stwnsh
	echo "<li><input id='tweet_updater_chose_url' type='radio' name='tweet_updater_options[url_method]' value='stwnsh'";
	if( $options['url_method'] == 'stwnsh' ) { echo " checked='true'"; };
	echo " /><label for='tweet_updater_chose_url'>Stwnsh,  A Welsh language service. <a href='http://stwnsh.com/'>http://stwnsh.com</a></label></li>"; 

	// TinyURL
	echo "<li><input id='tweet_updater_chose_url' type='radio' name='tweet_updater_options[url_method]' value='tinyurl'";
	if( $options['url_method'] == 'tinyurl' ) { echo " checked='true'"; };
	echo " /><label for='tweet_updater_chose_url'>TinyURL <a href='http://tinyurl.com/'>http://tinyurl.com</a></label></li>";	

	// ZZ.GD
	echo "<li><input id='tweet_updater_chose_url' type='radio' name='tweet_updater_options[url_method]' value='zzgd'";
	if( $options['url_method'] == 'zzgd' ) { echo " checked='true'"; };
	echo " /><label for='tweet_updater_chose_url'>ZZ.GD <a href='http://zz.gd/'>http://zz.gd</a></label></li>"; 

	echo "</ul>";
}

//Alternative short url retrieval method
function tweet_updater_url_method()
	{ echo "<p>Version 2.05 added the option to use php cURL to create and retrieve external short urls instead of file_get_contents(). <br />If you'd prefer to use file_get_contents() for URL retrieval, unselect this checkbox.<br />This doesn't affect Twitter communication, which only uses cURL.</p>"; }
function tweet_updater_use_curl()
	{ $options = get_option('tweet_updater_options'); echo "<input id='tweet_updater_use_curl' type='checkbox' name='tweet_updater_options[use_curl]' value='1' "; if( $options['use_curl'] == '1' ) { echo " checked='true'"; }; echo " /><label>Some web hosts disable the get_page_contents() function. In this case, you must use cURL."; }




/* Form validaton functions */

function tweet_updater_auth_validate($input) //n.b. else statements required for checkboxes
{
	$tokens = get_option('tweet_updater_auth');
	
	// The WordPress Settings API will overwrite arrays in the database with only the fields used in the form
	// To retain all the fields, the use the changed items to update the original array.
	if( !empty( $input['consumer_key'] ) ) 		{ $tokens['consumer_key'] = 	$input['consumer_key']; }
	if( !empty( $input['consumer_secret'] ) ) 	{ $tokens['consumer_secret'] = 	$input['consumer_secret']; }
	if( !empty( $input['default_consumer_keys'] ) ) { $tokens['default_consumer_keys'] = $input['default_consumer_keys']; } else { $tokens['default_consumer_keys'] = '0'; }
	if( !empty( $input['request_key'] ) ) 		{ $tokens['request_key'] = 	$input['request_key']; }
	if( !empty( $input['request_secret'] ) ) 	{ $tokens['request_secret'] = 	$input['request_secret']; }
	if( !empty( $input['request_link'] ) ) 		{ $tokens['request_link'] = 	$input['request_link']; }
	if( !empty( $input['access_key'] ) ) 		{ $tokens['access_key'] = 	$input['access_key']; }
	if( !empty( $input['access_secret'] ) ) 	{ $tokens['access_secret'] = 	$input['access_secret']; }
	if( !empty( $input['auth1_flag'] ) ) 		{ $tokens['auth1_flag'] = 	$input['auth1_flag']; }
	if( !empty( $input['auth2_flag'] ) ) 		{ $tokens['auth2_flag'] = 	$input['auth2_flag']; }
	if( !empty( $input['auth3_flag'] ) ) 		{ $tokens['auth3_flag'] = 	$input['auth3_flag']; }
	
	return $tokens;
}

function tweet_updater_options_validate($input) 
{
	$options = get_option('tweet_updater_options');
	
	// The WordPress Settings API will overwrite arrays in the database with only the fields used in the form
	// To retain all the fields, the use the changed items to update the original array.
	if( !empty( $input['newpost_update'] ) ) 	{ $options['newpost_update'] = 	$input['newpost_update']; } else { $options['newpost_update'] = '0'; }
	if( !empty( $input['newpost_format'] ) ) 	{ $options['newpost_format'] = 	$input['newpost_format']; }
	if( !empty( $input['edited_update'] ) ) 	{ $options['edited_update'] = 	$input['edited_update']; } else { $options['edited_update'] = '0'; }
	if( !empty( $input['edited_format'] ) ) 	{ $options['edited_format'] = 	$input['edited_format']; }
	if( !empty( $input['limit_activate'] ) ) 	{ $options['limit_activate'] = 	$input['limit_activate']; }  else { $options['limit_activate'] = '0'; }
	if( !empty( $input['limit_to_category'] ) ) 	{ $options['limit_to_category'] = $input['limit_to_category']; }
	if( !empty( $input['limit_to_custom_field_key'] ) ) { $options['limit_to_custom_field_key'] = $input['limit_to_custom_field_key']; }
	if( !empty( $input['limit_to_custom_field_val'] ) ) { $options['limit_to_custom_field_val'] = $input['limit_to_custom_field_val']; }
	if( !empty( $input['use_curl'] ) ) 		{ $options['use_curl'] = 	$input['use_curl']; } else { $options['use_curl'] = '0'; }
	if( !empty( $input['url_method'] ) ) 		{ $options['url_method'] = 	$input['url_method']; }
	if( !empty( $input['bitly_username'] ) ) 	{ $options['bitly_username'] = 	$input['bitly_username']; }
	if( !empty( $input['bitly_appkey'] ) ) 		{ $options['bitly_appkey'] = 	$input['bitly_appkey']; }

	return $options;
}

?>
