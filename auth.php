<?php
/** Authenticate Twitter user: OAuth Workflow
 * 
 * Run from command line:
 * 	$> php auth.php (register|validate|test|tweet)
 * 
 * Errors are not really accounted for, so run:
 * 	1. Register
 * 	2. Then goto the returned url and authenticate with twitter.
 * 	3. Validate
 * 	4. Test/Tweet
 * 
 * Code and References:
 * 	http://kovshenin.com/archives/automatic-tweet-oauth/
 * 	http://kovshenin.com/archives/twitter-api-pin-based-oauth-php/
 * 	http://kovshenin.com/archives/twitter-robot-in-php-twibots-draft/
 * 	http://blog.evandavey.com/2010/02/how-to-php-oauth-twitter.html
 * 	http://github.com/abraham/twitteroauth/blob/master/redirect.php
 * 	http://github.com/abraham/twitteroauth/blob/master/callback.php
 * 	http://github.com/abraham/twitteroauth/blob/master/test.php
 * 	
 * 
 */

require_once('twitteroauth.php');

$consumer_key = 'o1WYNgwG1PMHW2Lh5ceYQ';
$consumer_key_secret = 'OUygrqtDDueidU9qRGjuvOdz3JaVyyoCFkDgRBIMerI';

$action = isset($_GET["action"]) ? $_GET["action"] : $argv[1];

if ($action == "register")
{
	echo "Registering... \n";
	
	/* Build TwitterOAuth object with client credentials. */
	$connection = new TwitterOAuth($consumer_key, $consumer_key_secret);

	$request = $connection->getRequestToken();
	
	// Retrive tokens from request
	$request_token = $request["oauth_token"];
	$request_token_secret = $request["oauth_token_secret"];
	
	// Store request tokens in array:
	//$tokens["request_token"] = $request_token;
	//$tokens["request_token_secret"] = $request_token_secret;

	// At this stage you should store the two request tokens somewhere.
	// Database or file, whatever. Just make sure it's safe and nobody can read it!
	// I'll just put them in files for now...
	file_put_contents("request_token", $request_token);
	file_put_contents("request_token_secret", $request_token_secret);
	
	// Output the request tokens for verification
	echo "Request token: $request_token \n";
	echo "Request token secret: $request_token_secret \n";
	
	// Generate a request link and output it
	$request_link = $connection->getAuthorizeURL($request);
	echo "Request here: $request_link \n";
	die();
}
elseif ($action == "validate")
{
	
	echo "Validating \n";
	
	// This is the validation part. At this point you should read the stored request
	// tokens. You'll need them to get your access tokens! 
 	//$request_token = $tokens["request_token"];
	//$request_token_secret = $tokens["request_token_secret"];
	
	//For testing, get request tokens from files:
	$request_token = file_get_contents("request_token");
	$request_token_secret = file_get_contents("request_token_secret");
	
	// Initiate a new TwitterOAuth object. Provide the request token and request token secret
	$connection = new TwitterOAuth($consumer_key, $consumer_key_secret, $request_token, $request_token_secret);
	
	// Ask Twitter for an access token (and an access token secret)
	$request = $connection->getAccessToken();
	
	/*
	 ------------------------------------------------------------------------------------------
	 *** A failed request (e.g. not authorised by user in twitter) is not handled at all  	***
	 *** and breaks the script during getAccessToken() 					***
	 ------------------------------------------------------------------------------------------
	 */
	 
	// Retrieve access token from request:
	$access_token = $request['oauth_token'];
	$access_token_secret = $request['oauth_token_secret'];
	
	// Store access tokens in array:
	//$tokens["access_token"] = $access_token;
	//$tokens["access_token_secret"] = $access_token_secret;

	echo "Access Token is: $access_token \n";
	echo "Access Token Secret is: $access_token_secret \n";
 
	// Now store the two tokens into another file (or database or whatever):
	// I'll just put them in files for now...
	file_put_contents("access_token", $access_token);
	file_put_contents("access_token_secret", $access_token_secret);
	 
	// Great! Now we've got the access tokens stored.
	// Let's verify credentials and output the username.
	// Note that this time we're passing TwitterOAuth the access tokens.
	$connection = new TwitterOAuth($consumer_key, $consumer_key_secret, 
	$access_token, $access_token_secret);
 
	// Send an API request to verify credentials
	$credentials = $connection->get("account/verify_credentials");
 
	// Parse the result (assuming you've got simplexml installed)
 
	// And finaly output some text
	echo "Access token saved. \nAuthorised as @" . $credentials->screen_name . "\n";
	die();
}
elseif ($action == "test")
{
	// Read the access tokens
	$access_token = file_get_contents("access_token");
	$access_token_secret = file_get_contents("access_token_secret");

	// Initiate TwitterOAuth using access tokens
	$connection = new TwitterOAuth($consumer_key, $consumer_key_secret, $access_token, $access_token_secret);
	
	$result = $connection->get('account/verify_credentials');
	
	if ($result->id)
	{
		echo "Connection checked OK \nAuthorised as @" . $result->screen_name . "\n";
	}
	else
	{
		echo "Not verified \n";
		print_r($result);
	}
}
elseif ($action == "tweet")
{
	// Read the access tokens
	$access_token = file_get_contents("access_token");
	$access_token_secret = file_get_contents("access_token_secret");
	
	// Initiate a TwitterOAuth using those access tokens
	$connection = new TwitterOAuth($consumer_key, $consumer_key_secret, $access_token, $access_token_secret);
	
	// Set test Message
	$tweet = "Testing TweetUpdater via #OAuth. (" . rand() . ")";
	echo "Sending:  \"$tweet\"...\n";
	
	// Post an update to Twitter via your application:
	$result = $connection->post('statuses/update', array('status' => $tweet));

	if ($result->text)
	{
		echo "Tweet reads:\n" . $result->user->screen_name . ": " . $result->text . "\n";
	}
	else
	{
		echo "Unexpected Results:\n";
		print_r($result);
	}
}
else 
{
	echo "Nothing Selected";
}

?>
