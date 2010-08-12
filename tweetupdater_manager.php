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
		'use_curl' => '0',
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
	add_options_page( 'TweetUpdater', 'TweetUpdater Options', 'manage_options', 'TweetUpdater', 'tweet_updater_options_page' );

	// add the hook for the admin settings field
	add_action( 'admin_init', 'tweet_updater_admin_init' );
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
		div.ok		{ margin: 20px 0; border: 1px solid green; padding: 15px; color: green; }
		div.warning	{ margin: 20px; border: 1px solid red; padding: 20px; color: red; }
		fieldset	{ margin: 20px 0; border: 1px solid #cecece; padding: 15px; }
	</style>
<?php	
	//Twitter Authorisation form
?>
	<h1>TweetUpdater</h1>
	<p>TweetUpdater can send tweets to a linked twitter account, when a post is published or updated.</p>

	<div>

	<h2>Twitter Authorisation</h2>
	<fieldset>
		TweetUpdater uses OAuth authentication to connect to Twitter. <br/>
		Basic Authentication (username and password) access for applicationss was discontinued by Twitter in August 2010.<br />
		Follow the authentication process to allow tweets to be sent by your account.<br />
		<form action="options.php" method="post">
<?php 		settings_fields('tweet_updater_auth'); 
		
		// Logic to display the correct form, depending on authorisation stage
		if( $tokens['auth1_flag'] != '1' )
		{
			update_option('tweet_updater_auth', $tokens);
			do_settings_sections('auth_1'); 
?>			<input name="Submit" type="submit" value="<?php esc_attr_e('Register'); ?>" />
			<div class="warning">TweetUpdater does not have access to a twitter account yet.</div>
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
?>			<input name="Submit" type="submit" value="<?php esc_attr_e('Authorise'); ?>" />
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
				echo "<div class='ok'><strong>Connection checked OK<br />TweetUpdater is authorised to use <a href='http://twitter.com/{$verify['user_name']}'>@{$verify['user_name']}</a></strong></div>";
				$tokens['auth3_flag'] = '1'; //Will only validate until reset
				update_option('tweet_updater_auth', $tokens);
?>				<input name="Submit" type="submit" value="<?php esc_attr_e('Check again'); ?>" />
<?php 				break;
			case '2':
				echo "<div class='warning'>Not able to validate access to account, Twitter is currently unavailable. Try checking again in a couple of minutes.</div>";
				$tokens['auth3_flag'] = '1'; //Will validate next time
				update_option('tweet_updater_auth', $tokens);
?>				<input name="Submit" type="submit" value="<?php esc_attr_e('Check again'); ?>" />
<?php				break;
			case '3':
				echo "<div class='warning'>TweetUpdater does not have access to a twitter account yet.</div>";
				$tokens['auth3_flag'] = '0';
				update_option('tweet_updater_auth', $tokens);
				do_settings_sections('auth_2'); 
?>				<input name="Submit" type="submit" value="<?php esc_attr_e('Authorise'); ?>" />
<?php				break;
			default:
				echo "<div class='warning'>TweetUpdater is not currently authorised to use any account. Please reset and try again.</div>";
				update_option('tweet_updater_auth', $tokens);
			}
		} 
?>
		</form>
	
<?php	// Button to reset OAuth process ?>
		<form action="options.php" method="post">
		<?php settings_fields('tweet_updater_auth'); ?>
		<h3>Or restart the authorisation procedure:</h3>
			<div class="hid">	
				<?php do_settings_sections('auth_reset'); // the hidden fields populate a padded table that has to be hidden by css. Feels like bit of a hack really.?>
			</div>
			<input name="Submit" type="submit" value="<?php esc_attr_e('Reset'); ?>" />
		</form>
		</div>
	</fieldset>

<?php	// TweetUpdater Options form ?>
	<div>
	<h2>TweetUpdater Options</h2>
	<form action="options.php" method="post">
		<fieldset>
			<?php settings_fields('tweet_updater_options'); ?>
			<fieldset><?php do_settings_sections('new_post'); ?></fieldset>
			<fieldset><?php do_settings_sections('edited_post'); ?></fieldset>
			<fieldset><?php do_settings_sections('short_url'); ?></fieldset>
			<fieldset><?php do_settings_sections('url_method'); ?></fieldset>
			<input name="Submit" type="submit" value="<?php esc_attr_e('Save Options'); ?>" />
		</fieldset>
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

/* Set the Allowed Form Fields */

function tweet_updater_admin_init()
{
// Settings for OAuth procedure with Twitter
register_setting( 'tweet_updater_auth', 'tweet_updater_auth', 'tweet_updater_auth_validate' );

	// Consumer Key fields
	add_settings_section('tweet_updater_consumer_keys', 'Consumer Keys', 'tweet_updater_auth_1', 'auth_1');
		add_settings_field('tweet_updater_consumer_key', 'Consumer Key', 'tweet_updater_consumer_key', 'auth_1', 'tweet_updater_consumer_keys');
		add_settings_field('tweet_updater_consumer_secret', 'Consumer Secret', 'tweet_updater_consumer_secret', 'auth_1', 'tweet_updater_consumer_keys');
		add_settings_field('tweet_updater_consumer_default', 'Use Default Consumer Keys?', 'tweet_updater_consumer_default', 'auth_1', 'tweet_updater_consumer_keys');
		add_settings_field('tweet_updater_auth1_flag', '', 'tweet_updater_auth1_flag', 'auth_1', 'tweet_updater_consumer_keys');

	// Register Keys switch
	add_settings_section('tweet_updater_register_keys', 'Register with Twitter', 'tweet_updater_auth_2', 'auth_2');
		add_settings_field('tweet_updater_auth2_flag', '', 'tweet_updater_auth2_flag', 'auth_2', 'tweet_updater_register_keys');

	// Reset button fields
	add_settings_section('tweet_updater_reset', 'Reset OAuth', 'tweet_updater_reset', 'auth_reset');
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
	add_settings_section('tweet_updater_new_post', 'Newly Published Post', 'tweet_updater_new_post', 'new_post');
		add_settings_field('tweet_updater_newpost_update', 'Update Twitter when a new post is published?', 'tweet_updater_newpost_update', 'new_post', 'tweet_updater_new_post');
		add_settings_field('tweet_updater_newpost_format', 'Tweet format for a new post:', 'tweet_updater_newpost_format', 'new_post', 'tweet_updater_new_post');
			
	//Section 2: Updated Post
	add_settings_section('tweet_updater_edited_post', 'Published Post Updated', 'tweet_updater_edited_post', 'edited_post');
		add_settings_field('tweet_updater_edited_update', 'Update Twitter when a published post is updated?', 'tweet_updater_edited_update', 'edited_post', 'tweet_updater_edited_post');
		add_settings_field('tweet_updater_edited_format', 'Tweet format for an updated post:', 'tweet_updater_edited_format', 'edited_post', 'tweet_updater_edited_post');

	//Section 3: Short Url service
	add_settings_section('tweet_updater_short_url', 'Short URL Service', 'tweet_updater_short_url', 'short_url');
		add_settings_field('tweet_updater_chose_url', 'Use a #url# from which provider?', 'tweet_updater_chose_url1', 'short_url', 'tweet_updater_short_url');
		add_settings_field('tweet_updater_bitly_username', 'Bit.ly Username', 'tweet_updater_bitly_username', 'short_url', 'tweet_updater_short_url');
		add_settings_field('tweet_updater_bitly_appkey', 'Bit.ly Appkey', 'tweet_updater_bitly_appkey', 'short_url', 'tweet_updater_short_url');

	//Section 4: Use CURL to get short_url?
	add_settings_section('tweet_updater_url_method', 'Use CURL to get external short_urls?', 'tweet_updater_url_method', 'url_method');
		add_settings_field('tweet_updater_use_curl', 'Check to use CURL instead of get_file_contents()', 'tweet_updater_use_curl', 'url_method', 'tweet_updater_url_method');
	}

/* Return Form components for the Allowed Form Fields */

// Consumer Keys form
function tweet_updater_auth_1() 
	{ echo '<p>Set your Twitter API Consumer Keys here. <br />If you prefer not to use the default TweetUpdater API keys, you can add your own keys here instead. You can get twitter API keys by registering a new application at <a href="http://twitter.com/apps">http://twitter.com/apps</a><br />This plugin is not yet authenticated with Twitter</p>'; }
function tweet_updater_consumer_key() 
	{ $tokens = get_option('tweet_updater_auth'); echo "<input id='tweet_updater_consumer_key' type='text' name='tweet_updater_auth[consumer_key]' value='{$tokens['consumer_key']}' />"; }
function tweet_updater_consumer_secret() 
	{ $tokens = get_option('tweet_updater_auth'); echo "<input id='tweet_updater_consumer_secret' type='text' name='tweet_updater_auth[consumer_secret]' value='{$tokens['consumer_secret']}' />"; }
function tweet_updater_consumer_default() 
	{ $tokens = get_option('tweet_updater_auth'); echo "<input id='tweet_updater_consumer_default' type='checkbox' name='tweet_updater_auth[default_consumer_keys]' value='1' checked='{$tokens['default_consumer_keys']}' />"; }
function tweet_updater_auth1_flag() 
	{ echo "<input id='tweet_updater_auth1_flag' type='hidden' name='tweet_updater_auth[auth1_flag]' value='1' />"; }

// Request link form
function tweet_updater_auth_2() 
	{ $tokens = get_option('tweet_updater_auth'); echo "<p>Now you need to tell twitter you want to allow TweetUpdater to be able to post to your account. <br />Follow the instructions at <a href='{$tokens['request_link']}'>{$tokens['request_link']}</a> and come back to this page to complete the process.</p>"; }
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
	echo " /><label for='tweet_updater_chose_url'>WordPress Permalink [Warning: Number of characters is not checked by TweetUpdater]</label></li>";

	echo "</ul>";
}
function tweet_updater_bitly_username()
	{ $options = get_option('tweet_updater_options'); echo "<input id='tweet_updater_bitly_username' type='text' size='30' name='tweet_updater_options[bitly_username]' value='{$options['bitly_username']}' />"; }
function tweet_updater_bitly_appkey()
	{ $options = get_option('tweet_updater_options'); echo "<input id='tweet_updater_bitly_appkey' type='text' size='30' name='tweet_updater_options[bitly_appkey]' value='{$options['bitly_appkey']}' />"; }


//Alternative short url retrieval method
function tweet_updater_url_method()
	{ echo "<p>Version 2.05 added an option to use CURL to create and retrieve external short urls instead of file_get_contents(). <br />Curl is required for the TwitterOAuth library, but the previous version of TwitterUpdater recommended _not_ using it's curl function. <br />I've left the option for testing. [DefProc]</p>"; }
function tweet_updater_use_curl()
	{ $options = get_option('tweet_updater_options'); echo "<input id='tweet_updater_use_curl' type='checkbox' name='tweet_updater_options[use_curl]' value='1' "; if( $options['use_curl'] == '1' ) { echo " checked='true'"; }; echo " />"; }


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
	if( $input['bitly_username'] != NULL ) { $options['bitly_username'] = $input['bitly_username']; }
	if( $input['bitly_appkey'] != NULL ) { $options['bitly_appkey'] = $input['bitly_appkey']; }
	
	return $options;
}

?>