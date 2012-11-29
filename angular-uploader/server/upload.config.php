<?php

// if (!isset($_GET['fileChecksum']))
// $_GET['fileChecksum'] = -1;
// 
// if (!isset($_FILES['Filedata']))
// $_FILES['Filedata'] = 0;
// 
// $working_dir = dirname(__FILE__);
// $working_upload = dirname(__FILE__).'\uploads';
// 
// define('UPLOAD_MAX_SIZE', 1000 * 1024 * 1024);
// 
// define('DESTINATION_DIR', $working_upload);  


if (!isset($_GET['fileChecksum']))
$_GET['fileChecksum'] = -1;

if (!isset($_FILES['Filedata']))
$_FILES['Filedata'] = 0;

$working_dir = dirname(__FILE__);
$working_upload = dirname(__FILE__).'/uploads';
//$working_upload = "../github/poly-GIS/lib-external/upload/upload/uploads/";

// Try to get the filename from the url or the forms' file-input
if (isset($_GET['fileName'])) {
	$_GET['fileName'] = cleanForShortURL(urldecode($_GET['fileName']));
}
// If there is no fileName passed in the querystring then take the fileName from the file-input's name
if (isset($_FILES["Filedata"]["name"])) {
  $_GET['fileName'] = cleanForShortURL(urldecode($_FILES["Filedata"]["name"]));
}

// If there is a filePath param (as a hidden field or in the querystring) then modify the $working_upload directory so that the file will be uploaded in this dir
if (isset($_POST["filePath"])) {
  $working_upload .= "/" . $_POST["filePath"];
}
else if (isset($_GET["filePath"])) {
  $working_upload .= "/" . $_GET["filePath"];
}

define('UPLOAD_MAX_SIZE', 1 * 1024 * 1024 * 1024);

define('DESTINATION_DIR', $working_upload);

?>