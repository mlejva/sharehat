<?php
/* creates a compressed zip file */
if (!function_exists('parseDate')) {
	  function create_zip($files = array(), $file_names = array(), $destination = '', $overwrite = false) {
		// if the zip file already exists and overwrite is false, return false
		if (file_exists($destination) && !$overwrite)
			return false;
		// vars
		$valid_files = array();
		// if files were passed in...
		if (is_array($files)) {
			// cycle through each file
			foreach ($files as $file) {
				// make sure the file exists
				if (file_exists($file)) {
					$valid_files[] = $file;
				}
			}
		}
		// if we have good files...
		if (count($valid_files)) {
			// create the archive
			$zip = new ZipArchive();
			if ($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
				return false;
			}
			// add the files
			for ($i = 0; $i < count($valid_files); $i++) {
				$zip->addFile($valid_files[$i], $file_names[$i]);
			}

			// debug
			//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;

			// close the zip -- done!
			$zip->close();

			// check to make sure the file exists
			return file_exists($destination);
		}
		else
			return false;
	}
}



// Upload file size variables set in ".user.php" - required by Heroku

require 'vendor/autoload.php';

$s3 = new Aws\S3\S3Client([
    'version' => 'latest',
    'region'  => 'us-east-1'
]);
$bucket = getenv('S3_BUCKET') ?: die('No "S3_BUCKET" config var in found in env!');


$name_array = array(); // help array for renaming files with same name
$new_name = '';


//if (!empty($_FILES)) {

    try {

    	// initialization of '$name_array'
    	foreach ($_FILES['file']['tmp_name'] as $index => $tmpName) {
    		$name = $_FILES['file']['name'][$index];
    		$name_array[$name] = 0;
    	}
    	// rename files with same name
    	foreach ($_FILES['file']['tmp_name'] as $index => $tmpName) {
			$name = $_FILES['file']['name'][$index];

			if ($name_array[$name] == 0) {
				$name_array[$name]++;
				$new_name = $name;
			}
			else {
				$new_name = (string)$name_array[$name] . $name;
				$name_array[$name]++;
			}

			$_FILES['file']['name'][$index] = $new_name;
		}

    	$randomFoldername = explode(".", uniqid(rand(), true), 2)[0];
    	$zipName = $randomFoldername . ".zip";
    	$zipPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $zipName;
		create_zip($_FILES['file']['tmp_name'], $_FILES['file']['name'], $zipPath);

		chmod($_SERVER['DOCUMENT_ROOT'], 0777);
		chmod($zipName, 0777);

		file_put_contents("php://stderr", "---------" . "Original name: " . $_FILES['file']['name'][0] . "\n");

		file_put_contents("php://stderr", "---------" . "Did create zip file: " . $didCreateZipFile . "\n");
		file_put_contents("php://stderr", "---------" . "File name: " . $zipName . "\n");
		file_put_contents("php://stderr", "---------" . "File size: " . filesize($zipName) . "\n");
		file_put_contents("php://stderr", "---------" . "File exists: " . file_exists($zipName) . "\n");
		file_put_contents("php://stderr", "---------" . "File readable: " . is_readable($zipName) . "\n");
		file_put_contents("php://stderr", "---------" . "File writable: " . is_writable($zipName) . "\n");
		$fp = fopen($zipName, 'a+');
		if (!$fp) {
			file_put_contents("php://stderr", "---------" . "Cannot open file.\n");
		}
		else {
		  file_put_contents("php://stderr", "---------" . "Can open file.\n");
		}


		// PROC SE VYTVARI DALSI .ZIP FILE? Protoze pri deploy se spusti vsechny soubory a uploadne se jeden 0B file


		$upload = $s3->upload($bucket, $zipName, fopen($zipName, 'r'), 'public-read');
		$GLOBALS['linkURL'] = $upload->get('ObjectURL');
		echo $GLOBALS['linkURL'];

		/*
		$upload = $s3->putObject(array(
    		'Bucket' => $bucket,
		    'Key' => $zipName,
		    'Body' => fopen($zipName, 'r'),
		    //'SourceFile' => $zipName,
		    'ACL' => 'public-read',
		    'ContentType' => 'application/zip'
		));
		*/

		//$linkURL = $s3->getObjectUrl($bucket, $zipName);
		//$GLOBALS['linkURL'] = $linkURL;
		//$GLOBALS['linkURL'] = $upload->get('ObjectURL');
		//array_push($link_arr, $upload->get('ObjectURL'));



/*
    	// Upload kazdeho filu
	    foreach ($_FILES['file']['tmp_name'] as $index => $tmpName) {
			$name = $_FILES['file']['name'][$index];

			if ($name_array[$name] == 0) {
				$name_array[$name]++;
				$new_name = $name;
			}
			else {
				$new_name = (string)$name_array[$name] . $name;
				$name_array[$name]++;
			}

	        $fullPath = $randomFoldername . "/" . $new_name;

	        $result = $s3->upload($bucket, $fullPath, fopen($_FILES['file']['tmp_name'][$index], 'rb'), 'public-read');
	    }
*/
    }

    catch (Exception $e) {
    	$GLOBALS['link-text'] = 'Exception: ' . $e->getMessage();
			file_put_contents("php://stderr", "---------" . "ERROR: " . $e->getMessage() . "\n");
    }
//}

//}
?>
