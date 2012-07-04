#TumblrDownloader
##Overview
**TumblrDownloader** enables batch download of a [Tumblr](http://www.tumblr.com/) blog using the [official API](http://www.tumblr.com/api/
).

For more information use the source, Luke!.

##Usage
###Basic
Run the following command in a terminal:

	php tumblrdl.php -b blog_name

Replace blog_name by the desired blog name (e.g. brainmess).
	
###Advanced
Several options or available, for detailed help and usage examples run the following command:

	php tumblrdl.php -h (or --help)
	
Output:

	Usage: php tumblrdl.php -b <blog_name>

		-b <blog_name> (or --blog <blogname>)
			*required* the blog name (e.g 'brainmess')
		-o=<offset> (or --offset=<offset>)
			*optional* the number of posts to skip before starting download (e.g. 100)
		-l=<limit> (or --limit=<limit>)
			*optional* the number of post parsed in each run - by default 20 (e.g. 50)
		-d=<path> (or --directory=<path>)
			*optional* the path to the download directory - by default script directory (e.g. /Users/username/Desktop)
		-u (or --unlimited)
			*optional* a flag to tell the script to download every photo available (might take a while ^^)
		-h (or --help)
			*help* print this help

	Call examples:

		php tumblrdl.php -b brainmess -u 
			(download every photo available)
		php tumblrdl.php -b brainmess -l=50 
			(download the last 50 photos)
		php tumblrdl.php --blog brainmess --offset=100 --limit=50 --directory=/Users/username/Desktop 
			(download 50 photos on the desktop by skipping the last 100 posts)
		...

	Notes:

		- short and long options can be used interchangeably
		- do not specify an option more than once (unexpected behavior might occur)
		- the 'offset' and 'limit' refers to the post count, not the photo count! as there may be more than one photo in a post.
		- download directory path must be absolute (/Users/username/Desktop instead of ~/Desktop)
		- once the script encounters an already downloaded photo (test for an existing file) it will stop.
		- if the original photo is not available, the script try an download the next available bigger size
		- photos are downloaded following this architecture path_to_download_directory/blog_name/yyyy/mm/yyyymmdd_basename.extension

##License MIT

Copyright (c) 2012 saeros
 
Permission is hereby granted, free of charge, to any person obtaining a copy<br />
of this software and associated documentation files (the "Software"), to<br />
deal in the Software without restriction, including without limitation the<br />
rights to use, copy, modify, merge, publish, distribute, sublicense, and/or<br />
sell copies of the Software, and to permit persons to whom the Software is<br />
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in<br />
all copies or substantial portions of the Software.
 
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR<br />
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,<br />
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE<br />
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER<br/>
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING<br />
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS<br />
IN THE SOFTWARE.

###Important note

If you have your own API key change it in the script define (the one provided is from api examples).

##Screenshots
![console](http://saeros.be/images/tumblrdl/tumblrdl-console.png)
![finder](http://saeros.be/images/tumblrdl/tumblrdl-finder.png)

Follow [@saeros01](http://twitter.com/saeros01) on Twitter for the latest news.

###ENJOY :-)
