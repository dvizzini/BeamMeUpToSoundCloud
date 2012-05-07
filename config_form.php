<?php

	function curPageURL() {
	 $pageURL = 'http';
	 if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
	 $pageURL .= "://";
	 if ($_SERVER["SERVER_PORT"] != "80") {
	  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	 } else {
	  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	 }
	 $pageUrl = preg_replace('/\?([a-zA-Z0-9_]*)/i', '', $pageUrl);
	 return $pageURL;
	}
	
	if (get_option('redirect_uri') == '') {
		set_option('redirect_uri', curPageURL());	
	}

?>

<style type="text/css">
	a {
		color: #389;
		font-weight: bold;
	}

	a:hover{
		cursor: pointer;
	}
</style>

<script type="text/javascript">

	var clientId;
	var clientSecret;
	var firstUri;
	var code;
	var redirectUri;
	
	jQuery(document).ready(function(){
			
		jQuery('input[type="submit"]').attr('disabled','disabled');
		redirectUri = '<?php echo get_option('redirect_uri') ?>';
		if ('<?php echo get_option('access_token') ?>' != '') {
			console.log('already have token');
			showDiv('loggedIn');
		} else if (getParameterByName('code') == '') {
			console.log('code is blank');		
			showDiv('stepOne');
		} else {
			console.log('code is there');
			showDiv('stepTwo');
		}
		
	});

	function getParameterByName(name) {
		name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
		var regexS = "[\\?&]" + name + "=([^&#]*)";
		var regex = new RegExp(regexS);
		var results = regex.exec(window.location.search);
		if(results == null) {				
	    	return "";
		} else {
			return decodeURIComponent(results[1].replace(/\+/g, " "));
		}
	}
	
	function showDiv(divId) {
		
		jQuery('#steps').children().hide();
		jQuery('#' + divId).show();
		
	}
	
	function getToken() {

		console.log('redirectUri: ' + redirectUri);

		var postBody = {
        	'client_id': jQuery('#clientIdTwo').val(),
        	'client_secret': jQuery('#clientSecretTwo').val(),
        	'grant_type': 'authorization_code',
        	'code': getParameterByName('code'),
        	'redirect_uri': redirectUri
        }
        
        console.log(postBody);

		jQuery.ajax('https://api.soundcloud.com/oauth2/token', {
        	type: "POST",
	        data: postBody,
	        dataType: "json",
	        error:function() {
	        	alert('Are you sure you entered the correct Client ID and Secret Key? If problems persist, please delete your SoundCloud App, uninstall this plugin, and start again.');
	        },
	        success:function(data) {
	        	console.log('Got token');	        	
	        	console.log(data);
	        	jQuery('#accessToken').val(data.access_token);
	        	showDiv('stepThree');
				jQuery('input[type="submit"]').removeAttr('disabled');;    	
	        }
	    });

	} 
	
	function getCode() {
		
		//clear query
		window.location.search = '';
		
		//redirect to SoundCloud
		firstUri = 'https://soundcloud.com/connect?client_id=' + jQuery('#clientIdOne').val() + '&response_type=code&scope=non-expiring&display=popup&redirect_uri=' + document.URL;
		console.log(firstUri);
		window.location = firstUri;
		
	}
	
</script>

<div class="field">
	
	<input type="hidden" name="accessToken" id="accessToken">


	<div class "inputs" id="steps">
		
		<div id="loggedIn">
			<h2>You have logged in. Your token is <?php echo get_option('access_token') ?></h2>
			<h3>If you would like to log in with a different SoundCloud app, you must uninstall this plugin and recreate with this different app.</h3>
		</div>
		
		<div id="stepOne">
	
			<h2>Step-by-step Setup</h2>
			<h3>Your organization should use its own SoundCloud account for this plugin. If it does not have one, it can sign up for one <a href= "http://soundcloud.com">here</a>. <b>Do not user any personal SoundCloud account</b>.</h3>
			<br/>
			<br/>
			<h3>Step 1: Go to your <a target="_blank" href="http://soundcloud.com/you/apps">SoundCloud apps page</a></h3>
			<h3>Step 2: Hit the button to register a new application</h3>
			<h3>Step 3: Name the app something sensible, like YourOrganizationNameOmeka</h3>
			<h3>Step 4: In the Return URI enter this address:</h3>
			<br/>
			<br/>
			<div><?php echo get_option('redirect_uri'); ?></div>
			<br/>			
			<br/>
			<h3>Step 5: Hit the Save App button on the SoundCloud page.</h3>
			<h3 id="setOptionField">Step 6: Enter your client id here:
				<input type="text" name="clientIdOne" id="clientIdOne" size='35'>
			</h3>
			<h3><?php echo WEB_PLUGIN ?></h3>
			<!--<h3>Step 7: Log onto SoundCloud:<a onClick="getCode()"><img src="<?php echo WEB_PLUGIN ?>/BeamMeUpToSoundCloud/libraries/btn-connect-sc-l.png" alt="Click Here" /></a></h3>-->
			<h3>Step 7: Log onto SoundCloud:<a onClick="getCode()">Click Here</a></h3>-->
			<br/>			
			<br/>			
			<h3><b>Do not worry. You can save after you follow the all the steps.</b></h3>
		</div>
		
		<div id="stepTwo">
			
			<h2>Step-by-step Setup (cont.)</h2>

			<h3>Step 8: Enter your client id again here:
				<input type="text" name="clientIdTwo" id="clientIdTwo" size='35'>
			</h3>
			<h3>Step 9: Enter your client secret here:
				<input type="text" name="clientSecretTwo" id="clientSecretTwo" size='35'>
			</h3>
			<h3>Step 10: Click <b><a onClick="getToken()">here</a></b>.</h3>
			<br/>			
			<br/>			
			<h3><b>Do not worry. You can save after you follow the all the steps.</b></h3>
						
		</div>
		
		<div id="stepThree">
			<h2>Step-by-step Setup (cont.)</h2>
			<h3>Step 11: Set options:</h3>
			<span><b>Upload to Soundcloud By Default</b></span>
			<input type="hidden" name="postToSoundCloudDefaultBool" value="0">
			<input type="checkbox" name="postToSoundCloudDefaultBool" id="postToSoundCloudDefaultBool" value="1" <?php if(get_option('post_to_soundcloud_default_bool') == '1') {echo 'checked';} ?>/>
			<div>You can change this option on a per-item basis</div>
			<br/>
			<span><b>Make Public on Soundcloud By Default</b></span>
			<input type="hidden" name="soundCloudPublicDefaultBool" value="0">
			<input type="checkbox" name="soundCloudPublicDefaultBool" id="soundCloudPublicDefaultBool" value="1" <?php if(get_option('soundcloud_public_default_bool') == '1') {echo 'checked';} ?>/>
			<div>You can change this option on a per-item basis</div>
			<br/>			
			<br/>			
			<h3>Step 12: Hit the Save Changes button below:</h3>
		</div>

	</div>
	
</div>