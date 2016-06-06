# imgmagnet

imgmagnet is a lightweight image host designed for lighttpd. It allows images to be served dynamically using lighttpd's mod_magnet module.

## Features

Supports bmp, gif, jpeg, png psd, webm.

Currently configured to record view counts in a MySQL database, though modification for other tasks, such as per-user watermarking, should be straightforward.

## Setup

The script assumes it will be located in /imghost/. Track.lua will need some reconfiguration if it is placed elsewhere.

Import the schema into a MySQL database. Edit config.php and fill in the required details.
Edit track.lua and also specify the MySQL configuration.

`cd /imghost/www` and `composer install` in order to add ffmpeg support to PHP, required for .webm files

Edit your lighttpd configuration to include a config block similar to below:

	$HTTP["host"] == "imghost.domain.com" {
	
        	$HTTP["url"] =~ ".jpeg" {
        	        magnet.attract-physical-path-to = ("/imghost/track.lua")
        	}
        	$HTTP["url"] =~ ".jpg" {
        	        magnet.attract-physical-path-to = ("/imghost/track.lua")
        	}
        	$HTTP["url"] =~ ".png" {
                	magnet.attract-physical-path-to = ("/imghost/track.lua")
        	}
        	$HTTP["url"] =~ ".gif" {
        	        magnet.attract-physical-path-to = ("/imghost/track.lua")
        	}
        	$HTTP["url"] =~ ".webm" {
        	        magnet.attract-physical-path-to = ("/imghost/track.lua")
        	}
	}

Ensure mod_magnet is enabled in the lighttpd config, and reload the server.

## License

Released under GNU GPLv3.

