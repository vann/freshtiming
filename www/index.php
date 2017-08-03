<?php

class Freshbooks {
	
	
	var $clientID = 'd9686b325d7ed398466500618e3e0f5da715d705031fdb14d17f390711683efb';
	var $redirectURL = 'https://freshtiming.combicombi.com';

	function encodeArrayForPost($fields) {
		$fields_string = "{";
		
		foreach ($fields as $key=>$value) { 
			$fields_string .= "\n\t" 
							  . '"' . $key . '"'
							  . ': '
							  . '"' . $value . '",'; 
		}
		$fields_string = rtrim($fields_string, ',') . "\n}";
		return $fields_string;
	}

	/*
		oAuthFreshbooks
		
		Called after a user clicks the oAuth link to my Freshbook application FreshTiming.
		
		Expects 'code' as GET param.
		
		Returns json of the form:
		
		{
		  "access_token" => "lots_of_letters_and_numbers", // lasts 12 hours
		  "token_type" => "bearer",
		  "expires_in" => 43200,
		  "refresh_token" => "same_as_the_bearer_token", //  lives forever
		  "created_at" => 1472471407
		}
		
		These tokens should be stored to reauth the user automatically... if we were actually tracking users.
	*/
		
	function oAuthFreshbooks($config, $sRefreshToken = null) {	
		$curl = curl_init();
		

		$authCode = array_key_exists('code',$_GET) ? $_GET['code'] : null;
		if ($sRefreshToken && !$authCode) 
			return json_decode(array('err' => "Did not receive auth code from Freshbooks."));
		
		$fields = array( 
			'client_secret'	=> $config['freshbooks_app_client_secret']
			, 'client_id'		=> $config['freshbooks_app_client_id']
			, 'redirect_uri'	=> $config['freshbooks_app_redirect_url']
		);

		if ($sRefreshToken ) {
			// if we're refreshing auth:
			$fields['grant_type'] = 'refresh_token';
			$fields['refresh_token'] = $sRefreshToken;
		} else {
			$fields['grant_type'] = 'authorization_code';
			$fields['code']	= $authCode;
		}
		

		//url-ify the data for the POST
		$postFields = $this->encodeArrayForPost($fields);
		 
		$aCurlOpts = array(
		  
		  CURLOPT_URL => "https://api.freshbooks.com/auth/oauth/token",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => $postFields,
		  
		  CURLOPT_HTTPHEADER => array(
		    "Api-version: alpha",
		    "Cache-Control: no-cache",
		    "Content-Type: application/json"
		  ));

		//$this->logthis('oAuthFreshbooks():aCurlOpts',$aCurlOpts);
		
		curl_setopt_array($curl, $aCurlOpts);

		
		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
			
		if ($err) {
			$jsonerr =  json_decode ( $err ,true);
			return $jsonerr;
		} 
		else {
			//$jsonResponse =  json_decode ( $response );
			//return $jsonResponse;
			return json_decode($response,true);
		}
	
	}
	

	function doFreshbooksScript($resp) {
		
		if (!$resp["token_type"] || !$resp["access_token"]) { 
			echo 'missing token_type or access_token from resp.';
			return false;
		}
	 	
	 	echo "<h3>Successfully oAuth'd into Freshbooks app FreshTiming</h3>";
	 		
 		/*echo '<h3>Attempting  OAuth2 Token Refresh</h3>';
		echo '<p>Using refresh_token: ' . $resp["refresh_token"] . '</p>';				
		$resp2 = oAuthFreshbooks($resp["refresh_token"]);
		echo print_r($resp2,true);
		*/
		
		$aIdentity = $this->placeFreshbooksCall($resp["access_token"],"auth/api/v1/users/me");
		//echo "<h4>identity</h4><pre>" . print_r($aIdentity,true) . "</pre>";
		
		$email = $aIdentity['response']['email'];
		$account_id = $aIdentity['response']['roles'][0]['id'];
		$biz_id = $aIdentity['response']['business_memberships'][0]['business']['id'];
		echo "<p>Freshbooks info for $email</p><p>Account id: $account_id, Biz id: $biz_id</p>";
		
		//$aSystem = placeFreshbooksCall($resp["access_token"],"accounting/account/$account_id/systems/systems/");
		//echo "<h4>system</h4>" . print_r($aSystem,true);
		
		// get projects
		$aProjects = $this->placeFreshbooksCall($resp["access_token"],"timetracking/business/$biz_id/projects");
		echo "<h4>projects</h4>";// . print_r($aProjects,true);
		$aProjectList  = $aProjects['projects'];
		echo "<ul>\n";
		for ($i = 0; $i < count($aProjectList); $i++) {
			$p = $aProjectList[$i];
			echo "<li>" . $p['title'] . "</li>\n";
		}
		echo "</ul>\n";
	}
	
	
	/*
	
		See Freshbooks API docs here for paths: https://www.freshbooks.com/api/start
		
		Currently only does GETs
	*/
	function placeFreshbooksCall($bearerToken, $path) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  
		  CURLOPT_URL => "https://api.freshbooks.com/" . $path,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  
		  CURLOPT_HTTPHEADER => array(
		    "Api-version: alpha",
		    "Authorization: Bearer $bearerToken",
		    "Content-Type: application/json"
		  ),
		));
		
		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
			
		if ($err) {
			$jsonerr = json_decode ( $err,true );
			return $jsonerr;
		} 
		else {
			return json_decode($response,true);
		}
	}
	
	
	function logthis($s, $obj = '') {
		
		$msg = $s;
		if (is_array($obj)) {
			$obj = print_r($obj,true);
		}
		
		$msg .=  $obj ? ': ' . $obj : '';
		echo "<pre>$msg</pre>\n";
	}
		
	function getConfig() {
	
		$config = json_decode(file_get_contents("../config.json"), true);
		
		// vzm: should check against a settable config array:
		if (!array_key_exists('freshbooks_app_client_secret',$config)) {
			logthis('err',"Must set freshbooks_app_client_secret (from Freshbooks TimingApp dev page) in config.json");
			return null;
		}

		return $config;
	}
	

	
	public function entryPoint() {
	
		$config = $this->getConfig();
		
		$link = "<a href='https://my.freshbooks.com/service/auth/oauth/authorize?";
		$link .= "client_id=" . $config['freshbooks_app_client_id'];
		$link .=  "&response_type=code&";
		$link .= "redirect_uri=" . $config['freshbooks_app_redirect_url'];
		$link .= "'>oAuth FreshTiming App</a>";
		
		echo $link;
		
		$bAuthed = false;

		if ($_GET) {
			if (!$config) return false;

			$response = $this->oAuthFreshbooks($config);
			
			//$this->logthis ('entryPoint():response', $response);
				
			if (array_key_exists('error',$response)) {
				$this->logthis ('entryPoint():Got Freshbooks Auth error. $response', $response);
				return false;
			}
			else {
				$bAuthed = true;
			}
			$this->doFreshbooksScript($response);
		} 
		
		if ($bAuthed) {
		?>	
		<h3>Upload TimingApp CSV export of tasks</h3>
		<form enctype="multipart/form-data" id='uploadform'  method="post">
			<input name="fileToUpload" type="file" />
			<input type="button" value="Upload" />
		</form>
		
		<progress value = 0 max = 100 style = "width:20em;height:2em;"><strong>Progress bar not supported in your browser</strong></progress>
		<div id = 'uploadResult'></div>
		
		<?php
		}
		
	}
}

?>
<!DOCTYPE html> 
<html>	
	<head>
		<title>FreshTiming</title>
		<script data-main="app" src="lib/requirejs/require.js"></script>
		<script src="lib/jquery/dist/jquery.min.js"></script>
		<style>
			
		</style>
	</head>
	<body>

		<h2>FreshTiming</h2>
		<p>Tool to take an export of TimingApp.com tasks and import them into Freshbooks Cloud Accounting. </p>
		<p>You can manually export tasks to CSV in Timing, or use this <a href = "https://timingapp.com/help/javascript_tasks">task export script</a> (only for TimingApp on macOS, versions > 2.2)</a></p>

		<?php 
			$fb = new Freshbooks();
			
			$fb->entryPoint();
		?>
		
		<script>
			/* global $ */
			$(':file').on('change', function(e) {
				var file = $(this)[0].files[0];
				//console.log("files is: " + JSON.stringify(file));
				if (file.size > 100*1024) {
					alert('max upload size is 100k')
				}
				if (file.type != 'text/csv') {
					alert('file type must be csv')
				}
				// Also see .name, .type
			});
			
			$(':button').on('click', function(e) {
			//	console.log('got click.');
			    $.ajax({
			        // Your server script to process the upload
			        url: 'upload.php',
			        type: 'POST',
			
			        // Form data
			        data: new FormData($('#uploadform')[0]),
			        dataType: "json",
			
			        // Tell jQuery not to process data or worry about content-type
			        // You *must* include these options!
			        cache: false,
			        contentType: false,
			        processData: false,
			        
			        complete: function(jqXHR, textStatus) {
			        	var json = JSON.parse(jqXHR.responseText);
			        	//console.log('Upload result: ' + json);
			        	if ("message" in json)
			        		$('#uploadResult').html(json.message);
			        	if (jqXHR.statusCode() == 201) {
			        		//importTasks('');
			        		// could make javascript calls to freshbooks? will freshbooks care the IP changed?  
			        	}
			        },
			
			        // Custom XMLHttpRequest
			        xhr: function() {
			        //	console.log('in xhr');
			            var myXhr = $.ajaxSettings.xhr();
			            if (myXhr.upload) {
			                // For handling the progress of the upload
			                myXhr.upload.addEventListener('progress', function(e) {
			                    console.log('in progress listener');
			                    if (e.lengthComputable) {
			                        $('progress').attr({
			                            value: e.loaded,
			                            max: e.total,
			                        });
			                        $('progress strong').html( (e.loaded/e.total) + "%");
			                    }
			                } , false);
			            }
			            return myXhr;
			        },
			    });
			});
		
		</script>
	</body>
</html>