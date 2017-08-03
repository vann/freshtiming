<?php

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	   	$file_name = basename($_FILES["fileToUpload"]["name"]);
	   	$target_dir = "uploads/";
		$target_file = $target_dir . $file_name;
		
		if (strlen($file_name) === 0){
		    $msg = "No file selected.";
		    $http_code = 400;
		}
		else if (file_exists($target_file)) {
		    $msg = "Sorry, file already exists: '$target_file'";
		    $http_code = 400;
		}
		else if ($_FILES["fileToUpload"]["size"] > 1024*500) {
		    $msg = "Sorry, your file is > 500k";
		    $http_code = 413;
		}
		else if(pathinfo($target_file,PATHINFO_EXTENSION) != "csv" ) {
		    $msg = "Sorry, only CSV files are allowed.";
		    $http_code = 415;
		}
		else  {
    		// if everything is ok, try to upload file
		    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
		        $msg = "'". basename( $_FILES["fileToUpload"]["name"]). "' uploaded";
		        $http_code = 201;
		    } else {
		        $msg = "Sorry, there was an error uploading your file.";
		        $http_code = 500;
		    }
		}
	} else {
	    $msg = "Usage: HTTP POST (multipart\form-data) a .CSV export of tasks from TimingApp (v2.2 or greater). Returns an appropriate HTTP response code and JSON body.";    
        $http_code = 405;
	}
	
    $resp =  $msg ? json_encode(array('message' => $msg)) : '{}';
    $len = strlen($resp);
        
	if (!headers_sent()) {
    	if (!http_response_code($http_code))
    	    error_log("freshtiming:upload.php: couldn't set http response code to $http_code");
    	header("Content-Length: $len");
        header("Content-Type: text/json");
	}
	else {
	    error_log("freshtiming:upload.php: headers already sent?");
	}
    echo $resp;
?>