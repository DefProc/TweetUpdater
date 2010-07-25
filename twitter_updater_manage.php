<?php 
	//update_option('twitterInitialised', '0');
	//SETS DEFAULT OPTIONS
	if(get_option('twitterInitialised') != '1'){
		update_option('newpost-published-update', '1');
		update_option('newpost-published-text', 'Published a new post: #title# @#url#');

		update_option('oldpost-edited-update', '1');
		update_option('oldpost-edited-text', 'Fiddling with my blog post: #title# @#url#');
		update_option('short-method', '0');
		update_option('url-method', '2');
                update_option('tu_bitly_username', '');
                update_option('tu_bitly_appkey', '');
		update_option('twitterInitialised', '1');
	}
	

	if($_POST['submit-type'] == 'options'){
		//UPDATE OPTIONS
		update_option('newpost-published-update', $_POST['newpost-published-update']);
		update_option('newpost-published-text', $_POST['newpost-published-text']);
		update_option('newpost-published-showlink', $_POST['newpost-published-showlink']);

		update_option('oldpost-edited-update', $_POST['oldpost-edited-update']);
		update_option('oldpost-edited-text', $_POST['oldpost-edited-text']);
		update_option('oldpost-edited-showlink', $_POST['oldpost-edited-showlink']);
		update_option('short-method', $_POST['short-method']);
		update_option('url-method', $_POST['url-method']);
		update_option('tu_bitly_username', $_POST['tu_bitly_username']);
		update_option('tu_bitly_appkey', $_POST['tu_bitly_appkey']);
		                                

	}else if ($_POST['submit-type'] == 'login'){
		//UPDATE LOGIN
		if(($_POST['twitterlogin'] != '') AND ($_POST['twitterpw'] != '')){
			update_option('twitterlogin', $_POST['twitterlogin']);
			update_option('twitterlogin_encrypted', base64_encode($_POST['twitterlogin'].':'.$_POST['twitterpw']));

		}else{
			echo("<div style='border:1px solid red; padding:20px; margin:20px; color:red;'>You need to provide your twitter login and password!</div>");
		}
	}

	// FUNCTION to see if checkboxes should be checked
	function vc_checkCheckbox($theFieldname){
		if( get_option($theFieldname) == '1'){
			echo('checked="true"');
		}
	}
	
        // FUNCTION to see if radio buttons are selected
        function vc_checkRadio($theFieldname,$value){
		if( get_option($theFieldname) == $value){
                        echo('checked="true"');
                }
        }

?>
<?php $vc_placeholders = "<br />
			Placeholders:
			<ul>
			<li>#title# - Replaced by page title</li>
			<li>#url# - Replaced by URL as selected below</li>
			</ul>"; 
?>
<style type="text/css">
	fieldset{margin:20px 0; 
	border:1px solid #cecece;
	padding:15px;
	}
</style>
<div class="wrap">
	<h2>Your Twitter update options</h2>

	<form method="post">
	<div>
		<fieldset>
			<legend>New post published</legend>
			<p>
				<input type="checkbox" name="newpost-published-update" id="newpost-published-update" value="1" <?php vc_checkCheckbox('newpost-published-update')?> />
				<label for="newpost-published-update">Update Twitter when the new post is published</label>
			</p>
			<p>
				<label for="newpost-published-text">Text for this Twitter update</label><br />
				<input type="text" name="newpost-published-text" id="newpost-published-text" size="60" maxlength="146" value="<?php echo(get_option('newpost-published-text')) ?>" />
			<?php echo $vc_placeholders; ?>
		</fieldset>
		
		<fieldset>
			<legend>Existing posts</legend>
			<p>
				<input type="checkbox" name="oldpost-edited-update" id="oldpost-edited-update" value="1" <?php vc_checkCheckbox('oldpost-edited-update')?> />
				<label for="oldpost-edited-update">Update Twitter when the an old post has been edited</label>
			</p>
			<p>
				<label for="oldpost-edited-text">Text for this Twitter update</label><br />
				<input type="text" name="oldpost-edited-text" id="oldpost-edited-text" size="60" maxlength="146" value="<?php echo(get_option('oldpost-edited-text')) ?>" />
			<?php echo $vc_placeholders; ?>
			</p>
		</fieldset>

		<fieldset>
			<legend>Alternative method to get shorturl (CURL)</legend>
			<p>
				<input type="checkbox" name="short-method" id="short-method" value="1" <?php vc_checkCheckbox('short-method')?> />
				<label for="short-method"><strong>Attention!</strong>: only check if the shorturl-generation throws 
				errors!<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;If there's nothing wrong, don't check this checkbox!!!</label>
			</p>
		</fieldset>
		
                <fieldset>
                        <legend>Choose your url service.</legend>
                        <p>
                                <input type="radio" name="url-method" id="url-method" value="1" <?php vc_checkRadio('url-method','1')?> />
				<label for="url-method">ZZ.GD</label>
			</p>
			<p>
				<input type="radio" name="url-method" id="url-method" value="2" <?php vc_checkRadio('url-method','2')?> />
				<label for="url-method">TinyURL</label>
                        </p>
                        <p>
                        	<input type="radio" name="url-method" id="url-method" value="3" <?php vc_checkRadio('url-method','3')?> />
                        	<label for="url-method">Bit.ly</label><br>
                        	<label for="tu_bitly_username">Username</label>
                        	<input type="text" name="tu_bitly_username" id="tu_bitly_username" value="<?php echo get_option('tu_bitly_username')?>" />
                                <label for="tu_bitly_appkey">App Key</label>
                                <input type="text" name="tu_bitly_appkey" id="tu_bitly_appkey" value="<?php echo get_option('tu_bitly_appkey')?>" />                        	
                        </p>
                        <?php if(function_exists('get_la_petite_url_permalink')) { // If activated, allow selection of la_petite_url plugin as the shortener ?>
			<p>
                                <input type="radio" name="url-method" id="url-method" value="4" <?php vc_checkRadio('url-method','4') ?> />
				<label for="url-method">le_petite_url</label>
			</p>
                  <?php } ?>
			<p>
                                <input type="radio" name="url-method" id="url-method" value="5" <?php vc_checkRadio('url-method','5') ?> />
				<label for="url-method">Full WordPress permalink (Warning: length is not checked)</label>
			</p>

                </fieldset>


		<input type="hidden" name="submit-type" value="options">
		<input type="submit" name="submit" value="save options" />
	</div>
	</form>
</div>

<div class="wrap">
	<h2>Your Twitter account details</h2>
	
	<form method="post" >
	<div>
		<p>
		<label for="twitterlogin">Your email address registered at Twitter:</label>
		<input type="text" name="twitterlogin" id="twitterlogin" value="<?php echo(get_option('twitterlogin')) ?>" />
		</p>
		<p>
		<label for="twitterpw">Your Twitter password:</label>
		<input type="password" name="twitterpw" id="twitterpw" value="" />
		</p>
		<input type="hidden" name="submit-type" value="login">
		<p><input type="submit" name="submit" value="save login" />
		&nbsp; ( <strong>Don't have a Twitter account? <a href="http://www.twitter.com">Get one for free here</a></strong> )</p>
	</div>
	</form>
	
</div>

<div class="wrap">
	<h2>Need help?</h2>
	<p>Visit the <a href="http://www.twitterupdater.com/">plugin 
page</a>.</p>
</div>
