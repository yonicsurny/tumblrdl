<?php
/**
 * SCRIPT - tumblrdl.php
 * 
 * This script enables batch download of a Tumblr blog using the official API.
 * For more information use the source, Luke!.
 * @see http://www.tumblr.com/api/
 * 
 * @version 0.2
 * @author saeros <saeros001@gmail.com>
 *
 * Copyright (c) 2012 saeros
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to 
 * deal in the Software without restriction, including without limitation the 
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or 
 * sell copies of the Software, and to permit persons to whom the Software is 
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in 
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING 
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS 
 * IN THE SOFTWARE.
 */
 
///////////////////////////////////////////////////////////////////////////////
// SETUP

//error_reporting(E_ALL);
//ini_set('display_errors', 1);
date_default_timezone_set('UTC');
define('API_KEY', 'PyezS3Q4Smivb24d9SzZGYSuhMNPQUhMsVetMC9ksuGPkK1BTt');
define('API_LIMIT', 20);


///////////////////////////////////////////////////////////////////////////////
// OPTIONS

$shortopts = "b:d::o::l::uh";
$longopts = array('blog:', 'directory::', 'offset::', 'limit::', 'unlimited', 'help');

$options = getopt($shortopts, $longopts);


///////////////////////////////////////////////////////////////////////////////
// DOWNLOADER

class TumblrDownloader 
{
    private $blog_name;
    private $download_directory;
    private $offset;
    private $limit;
    private $unlimited;
    private $counter;
    
    function __construct($arguments, $arguments_count, $options)
    {
        /* test for usage and print help */

        if (count($options) == 0 | array_key_exists("help", $options) || array_key_exists("h", $options)) 
        {
            echo "Usage: php $arguments[0] -b <blog_name>\n\n";

            echo "\t-b <blog_name> (or --blog <blogname>)\t*required* the blog name (e.g 'brainmess')\n";
            echo "\t-o=<offset> (or --offset=<offset>)\t*optional* the number of posts to skip before starting download (e.g. 100)\n";
            echo "\t-l=<limit> (or --limit=<limit>)\t\t*optional* the number of post parsed in each run - by default 20 (e.g. 50)\n";
            echo "\t-d=<path> (or --directory=<path>)\t*optional* the path to the download directory - by default script directory (e.g. /Users/username/Desktop)\n";
            echo "\t-u (or --unlimited)\t\t\t*optional* a flag to tell the script to download every photo available (might take a while ^^)\n";
            echo "\t-h (or --help)\t\t\t\t*help* print this help\n\n";

            echo "Call examples:\n\n";

            echo "\tphp $arguments[0] -b brainmess -u (download every photo available)\n";
            echo "\tphp $arguments[0] -b brainmess -l=50 (download the last 50 photos)\n";
            echo "\tphp $arguments[0] --blog brainmess --offset=100 --limit=50 --directory=/Users/username/Desktop (download 50 photos on the desktop by skipping the last 100 posts)\n";
            echo "\t...\n\n";

            echo "Notes:\n\n";

            echo "\t- short and long options can be used interchangeably\n";
            echo "\t- do not specify an option more than once (unexpected behavior might occur)\n";
            echo "\t- the 'offset' and 'limit' refers to the post count, not the photo count! as there may be more than one photo in a post.\n";
            echo "\t- download directory path must be absolute (/Users/username/Desktop instead of ~/Desktop)\n";
            echo "\t- once the script encounters an already downloaded photo (test for an existing file) it will stop.\n";
            echo "\t- if the original photo is not available, the script try an download the next available bigger size\n";
            echo "\t- photos are downloaded following this architecture path_to_download_directory/blog_name/yyyy/mm/yyyymmdd_basename.extension\n";

            exit(0);
        }


        /* test presence of required fields (blog_name) */

        if (!array_key_exists("b", $options) && !array_key_exists("blog", $options)) 
        {
            echo "ERROR: Bad usage!\n";
            echo "\tsee $arguments[0] -h (or --help) for usage\n";

            exit(1);
        }


        /* retrieve blog name from options */

        $this->blog_name = ((array_key_exists("b", $options)) ? $options['b'] : $options['blog']);


        /* setup download directory */

        if (array_key_exists('d', $options) || array_key_exists('directory', $options)) 
        {
            $this->download_directory = ((array_key_exists('d', $options)) ? $options['d'] : $options['directory']);

            if (is_null($this->download_directory) || empty($this->download_directory) || $this->download_directory == ".") 
            {
                $this->download_directory = dirname(__FILE__); //default directory
            }
        } 
        else 
        {
            $this->download_directory = dirname(__FILE__); //default directory
        }
        if (!is_dir($this->download_directory)) 
        {
            echo "ERROR: given download directory path ($this->download_directory) doesn't exist, is not a directory or is not accesible (possibly permission denied)!\n";

            exit(1);
        }
        $this->download_directory .= DIRECTORY_SEPARATOR . $this->blog_name; //append blog name

        if (!file_exists($this->download_directory)) 
        {
            mkdir($this->download_directory); //create blog directory
            echo "STEP: created blog directory!\n";
        }


        /* offset */

        $this->offset = 0;
        if (array_key_exists('o', $options) || array_key_exists('offset', $options)) 
        {
            $this->offset = ((array_key_exists('o', $options)) ? $options['o'] : $options['offset']);
        }


        /* limit */

        $this->limit = API_LIMIT;
        if (array_key_exists('l', $options) || array_key_exists('limit', $options)) 
        {
            $this->limit = ((array_key_exists('l', $options)) ? $options['l'] : $options['limit']);
        }


        /* unlimited */

        $this->unlimited = false;
        if (array_key_exists('u', $options) || array_key_exists('unlimited', $options)) 
        {
            $this->unlimited = true;
        }


        /* counter */

        $this->counter = 0;
    }

    /** process
    * 
    * @since 0.2
    * @description public interface to launch the download process
    */
    public function process() 
    {
        $this->loop_through_posts();

        echo "\nDONE!\n";

        exit(0);
    }

    /** download_photo
    * 
    * @since 0.2
    * @description download a photo from given url to given path
    * @param string $photo_url
    * @param string $download_path
    */
    private function download_photo($photo_url, $download_path) 
    {
        $output = @fopen($download_path, "wb");
        if ($output == FALSE) 
        {
            echo "-- could not open file at path: " . $download_path . "!\n";

            return;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $photo_url);
        curl_setopt($ch, CURLOPT_FILE, $output);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);

        $error = curl_error($ch);
        if (!empty($error)) 
        {
            echo "-- an error occured: " . $error . "\n";
        }
        else 
        {
            $this->counter++;
            echo "-- downloaded ($this->counter)\n";
        }

        curl_close($ch);
        fclose($output);
    }

    /** loop_through_posts
    * 
    * @since 0.2
    * description loops through the post and download the photos
    */
    private function loop_through_posts() 
    {
        do 
        {
            $posts = $this->get_blog_posts();

            if (empty($posts)) 
            {
                return; // no more posts - end of loop
            }

            foreach ($posts as $post) 
            {
                /* loop through posts */

                $photos = $post->photos;

                if (count($photos) == 0) 
                {
                    /* no photo in this post - continue */

                    continue;
                }

                $photo_date = strtotime($post->date);
                $year = date("Y", $photo_date);
                $month = date("m", $photo_date);
                $day = date("d", $photo_date);

                $current_directory = $this->download_directory.DIRECTORY_SEPARATOR.$year.DIRECTORY_SEPARATOR.$month;// path_to_download_directory/blog_name/yyyy/mm
                if (!is_dir($current_directory)) 
                {
                    mkdir($current_directory, 0777, true); // photo directory doesn't exist - create it
                }

                for ($i = 0; $i < count($photos); $i++) 
                {
                    /* loop through post photos */

                    $photo = $photos[$i];
                    
                    $photo_url = $photo->original_size->url;//retrieve original size - preferred size
                    $pathinfo = pathinfo($photo_url);// retrieve path info (basename, extension, ...)
                    
                    if (empty($pathinfo['extension'])) 
                    {
                        /* if no extension (invalid photo) - proceed with alternative sizes */

                        foreach ($photo->alt_sizes as $alt_photo) 
                        {
                            $photo_url = $alt_photo->url;
                            $pathinfo = pathinfo($photo_url);

                            if (!empty($pathinfo['extension'])) 
                            {
                                /* extension is not empty - we have a winner */

                                break;
                            }
                        }
                    } 
                    if (empty($pathinfo['extension'])) 
                    {
                        /* no winner found - skipping photo */

                        echo "-- skipping photo - no valid url found!\n";

                        continue;
                    }

                    $file_name = $year.$month.$day."_".$pathinfo['basename'];// yyyymmdd_basename.extension
                    echo "filename: $file_name\n";
                    $download_path = $current_directory.DIRECTORY_SEPARATOR.$file_name;// concat download_directory with file_name
                    if (is_file($download_path)) 
                    {
                        /* file already exist - abort and exit */

                        echo "-- already exists!\n";

                        return;
                    }

                    $this->download_photo($photo_url, $download_path);
                }
            }

            $this->offset += $this->limit;

        } while ($this->unlimited);
    }
    
    /** get_blog_posts
    * 
    * @since 0.2
    * @description compose url and retrieve blog posts (json)
    * @return array
    */
    private function get_blog_posts() 
    {
        $blog_url = "http://api.tumblr.com/v2/blog/" 
        . $this->blog_name 
        . ".tumblr.com/posts/photo?api_key=" . API_KEY 
        . "&limit=" . $this->limit 
        . "&offset=" . $this->offset;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $blog_url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $output = curl_exec($ch);
        curl_close($ch);

        $json_output = json_decode($output);

        if (is_null($json_output) || $json_output->meta->status == 404) 
        {
            echo "ERROR: Could not find blog named '$this->blog_name'!\n";

            exit(1);
        }

        return $json_output->response->posts;
    }
}

$downloader = new TumblrDownloader($argv, $argc, $options);
$downloader->process();

?>