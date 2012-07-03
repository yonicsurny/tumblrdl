SCRIPT - tumblrdl.php

This script enables batch download of a Tumblr blog using the official API.
For more information use the source, Luke!.

see http://www.tumblr.com/api/

version 0.2

author saeros <saeros001@gmail.com>

Copyright (c) 2012 saeros
 
Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to 
deal in the Software without restriction, including without limitation the 
rights to use, copy, modify, merge, publish, distribute, sublicense, and/or 
sell copies of the Software, and to permit persons to whom the Software is 
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in 
all copies or substantial portions of the Software.
 
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING 
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS 
IN THE SOFTWARE.

Important note!

You need to change the API_KEY in the script. You can get one on the official site.

Usage: php tumblrdl.php -b blog_name

	-b <blog_name> (or --blog <blogname>)	*required* the blog name (e.g 'brainmess')
	-o=<offset> (or --offset=<offset>)		*optional* the number of posts to skip before starting download (e.g. 100)
	-l=<limit> (or --limit=<limit>)			*optional* the number of post parsed in each run - by default 20 (e.g. 50)
	-d=<path> (or --directory=<path>)		*optional* the path to the download directory - by default script directory (e.g. /Users/username/Desktop)
	-u (or --unlimited)						*optional* a flag to tell the script to download every photo available (might take a while ^^)
	-h (or --help)							*help* print this help

Call examples:

	php tumblrdl.php -b brainmess -u (download every photo available)
	php tumblrdl.php -b brainmess -l=50 (download the last 50 photos)
	php tumblrdl.php --blog brainmess --offset=100 --limit=50 --directory=/Users/username/Desktop (download 50 photos on the desktop by skipping the last 100 posts)
	...

Notes:

	- short and long options can be used interchangeably
	- do not specify an option more than once (unexpected behavior might occur)
	- the 'offset' and 'limit' refers to the post count, not the photo count! as there may be more than one photo in a post.
	- download directory path must be absolute (/Users/username/Desktop instead of ~/Desktop)
	- once the script encounters an already downloaded photo (test for an existing file) it will stop.
	- if the original photo is not available, the script try an download the next available bigger size
	- photos are downloaded following this architecture path_to_download_directory/blog_name/yyyy/mm/yyyymmdd_basename.extension

ENJOY