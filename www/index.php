<?PHP

include('../include/base.php');
require __DIR__ . '/vendor/autoload.php';

$mime_map = array('img' => array(), 'other' => array());
$mime_map['img']['image/png'] = 'png';
$mime_map['img']['image/jpeg'] = 'jpeg';
$mime_map['img']['image/gif'] = 'gif';
$mime_map['img']['image/psd'] = 'psd';
$mime_map['img']['image/x-ms-bmp'] = 'bmp';
$mime_map['other']['video/webm'] = 'webm';

$n = 1;

if(isset($_GET['n']))
	if(intval($_GET['n']) != 0 and intval($_GET['n'] < 50))
		$n = intval($_GET['n']);
if(!isset($_FILES['images']) and !isset($_POST['crossload_urls']))
{

?>

<html>
<body>
Upload an image.<br /><br />
<form action="" method="post" enctype="multipart/form-data">
<?
for($i = 0; $i < $n; $i++)
{
	echo '<input type="file" name="images['.$i.']" /><br />'."\n";
}
?>
<input type="submit" name="submit" value="Upload" />
</form>
<br /><br />

Or enter a URL:<br /><br />
<form action="" method="post" enctype="multipart/form-data">
<?
for($i = 0; $i < $n; $i++)
{
	echo '<input type="text" name="crossload_urls['.$i.']" /><br />'."\n";
}
?>
<input type="submit" name="submit" value="Crossload" />
</form>

</body>
</html>

<?

}

if(isset($_POST['crossload_urls']))
{
	foreach($_POST['crossload_urls'] as $url)
	{
		if($url == '')
		{
			echo 'Empty URL<br />';
			continue;
		}

                $data = file_get_contents($url);
		
		$result = write_insert_image($data);

		if($result['error'])
			echo $result['error'].'<br />';
		else
			echo $base_url.$result['filename'].'<br />';
	}
}


if(isset($_FILES['images']))
{
	for($i = 0; $i < sizeof($_FILES['images']['name']); $i++)
	{
		if($_FILES['images']['tmp_name'] == '')
		{
			echo 'Image '.($i+1).' could not be uploaded properly.';
			continue;
		}

		if($_FILES['images']['error'][$i] > 0)
		{
			echo 'Error in file '.($i+1).': '.file_upload_error_message($_FILES['images']['error'][$i]);
			continue;
		}

		$fp = fopen($_FILES['images']['tmp_name'][$i], 'rb');
		$data = fread($fp, filesize($_FILES['images']['tmp_name'][$i]));
		fclose($fp);
		unlink($_FILES['images']['tmp_name'][$i]);

		$result = write_insert_image($data);

                if($result['error'])
                        echo $result['error'].'<br />';
                else
                        echo $base_url.$result['filename'].'<br />';
	}
}


function write_insert_image(&$data)
{
	global $base_path, $mime_map, $img_max_filesize, $db;

	if(strlen($data) > $img_max_filesize*1024*1024)
	{
		return array('error' =>'Image was too big. Maximum permitted size: '.$img_max_filesize.'MB');
	}


	$hash = sha1($data);

	// check for duplicate
	$results = $db->query('SELECT filename FROM uploads WHERE hash = "'.$hash.'";');
	if($db->num_rows($results) != 0)
	{
		$row = $db->fetch_assoc($results);
		return array('error' => NULL, 'filename' => $row['filename']);
	}

	// Generate a unique filename.
	do {
		$filename = substr(md5(time() . rand()),0,6);
		$filepath = $base_path.$filename;
	} while ( file_exists($filepath));
	// Generate a unique filename.

	$fp = fopen($filepath, 'wb');
	fwrite($fp, $data);
	fclose($fp);

	$mime = mime_content_type($filepath);

	$ext = '';
	$width = 0;
	$height = 0;
	$bits = 0;
	$codec = '';
	$duration = 0;

	if(array_key_exists($mime, $mime_map['img']))
	{
		$image_info = getimagesize($filepath);

		$ext = $mime_map['img'][$mime];
		$width = $image_info[0];
		$height = $image_info[1];
		$bits = $image_info['bits'];
	}

	else if(array_key_exists($mime, $mime_map['other']))
	{
		$ffprobe = \FFMpeg\FFProbe::create(array(
			'ffmpeg.binaries' => '/usr/bin/ffmpeg',
			'ffprobe.binaries' => '/usr/bin/ffprobe',
		));

		$ext = $mime_map['other'][$mime];

		$video_info =$ffprobe->streams($filepath)->videos()->first();
		$width = $video_info->get('width');
		$height = $video_info->get('height');
		$codec = $video_info->get('codec_name');

		$duration = intval($ffprobe->format($filepath)->get('duration'));
	}

	else
	{
		unlink($filepath);
		return array('error' => 'Invalid file '.($i+1).' - not an image! ('.$mime.')<br />');
	}

	$oldfilepath = $filepath;
	do {
		$filename = substr(md5(time() . rand()),0,6).'.'.$ext;
		$filepath = $base_path.$filename;
	} while ( file_exists($filepath));

	rename($oldfilepath, $filepath);

	$db->query('INSERT INTO uploads(uploaded, ip, filename, hash, size, width, height, bits, duration, codec, mime) VALUES (
				unix_timestamp(),
				"'.$_SERVER['REMOTE_ADDR'].'",
				"'.$filename.'",
				"'.$hash.'",
				"'.filesize($filepath).'",
				"'.$width.'",
				"'.$height.'",
				"'.$bits.'",
				"'.$duration.'",
				"'.mysql_escape_string($codec).'",
				"'.$mime.'"
				);');

	// Return the image URI.
	return array('error' => NULL, 'filename' => $filename);
}


function file_upload_error_message($error_code) {
    switch ($error_code) { 
        case UPLOAD_ERR_INI_SIZE: 
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini'; 
        case UPLOAD_ERR_FORM_SIZE: 
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'; 
        case UPLOAD_ERR_PARTIAL: 
            return 'The uploaded file was only partially uploaded'; 
        case UPLOAD_ERR_NO_FILE: 
            return 'No file was uploaded'; 
        case UPLOAD_ERR_NO_TMP_DIR: 
            return 'Missing a temporary folder'; 
        case UPLOAD_ERR_CANT_WRITE: 
            return 'Failed to write file to disk'; 
        case UPLOAD_ERR_EXTENSION: 
            return 'File upload stopped by extension'; 
        default: 
            return 'Unknown upload error'; 
    } 
} 

?>
