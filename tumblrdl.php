#!/usr/bin/env php
<?php
/**
 * SCRIPT - tumblrdl.php
 * 
 * This script enables batch download of a Tumblr blog using the official API.
 * For more information use the source, Luke!.
 * @see http://www.tumblr.com/api/
 * 
 * @version 0.6
 * @author saeros <yonic.surny@gmail.com>
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

// Uncomment the next 2 lines for debugging
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

date_default_timezone_set('Europe/Brussels');
define('API_KEY',               'REPLACE_ME');
define('MIN_PAD',               10);
define('API_LIMIT',             20);
define('MAX_RATIO',             1.8);
define('MIN_RATIO',             1.3);
define('MIN_WIDTH',             800);
define('TERM_RESET',            "\033[0m");
define('TERM_UNDERLINE',        "\033[4m");
define('TERM_COLOR_RED',        "\033[31m");
define('TERM_COLOR_BLUE',       "\033[34m");
define('TERM_COLOR_GREEN',      "\033[32m");
define('TERM_COLOR_YELLOW',     "\033[33m");
define('TERM_SAVE_POSITION',    "\0337");
define('TERM_RESTORE_POSITION', "\0338");


///////////////////////////////////////////////////////////////////////////////
// OPTIONS

$shortopts = "b:d::o::l::uch";
$longopts = array('blog:', 'directory::', 'offset::', 'limit::', 'unlimited', 'continue', 'help', 'wallfilter', 'blacklist::', 'whitelist::');

$options = getopt($shortopts, $longopts);


///////////////////////////////////////////////////////////////////////////////
// DOWNLOADER

class TumblrDownloader {

    // properties
    private $blog_name;
    private $download_directory;
    private $offset;
    private $limit;
    private $unlimited;
    private $continue;
    private $counter_processed;
    private $counter_downloaded;
    private $wallfilter;
    private $whitelist;
    private $blacklist;
    private $file_name;
    
    function __construct($arguments, $arguments_count, $options) {
        // test for usage and print help
        if (array_key_exists("help", $options) || array_key_exists("h", $options)) {
            fwrite(STDOUT, TERM_UNDERLINE . "Usage:" . TERM_RESET . " php $arguments[0] -b <blog_name>" . PHP_EOL . PHP_EOL);

            fwrite(STDOUT, "\t-b <blog_name> (or --blog <blogname>)\t" . TERM_COLOR_YELLOW . "*required*" . TERM_RESET . " the blog name (e.g 'brainmess')" . PHP_EOL);
            fwrite(STDOUT, "\t-o=<offset> (or --offset=<offset>)\t" . TERM_COLOR_BLUE . "*optional*" . TERM_RESET . " the number of posts to skip before starting download (e.g. 100)" . PHP_EOL);
            fwrite(STDOUT, "\t-l=<limit> (or --limit=<limit>)\t\t" . TERM_COLOR_BLUE . "*optional*" . TERM_RESET . " the number of post parsed in each run - by default 20 (e.g. 50)" . PHP_EOL);
            fwrite(STDOUT, "\t-d=<path> (or --directory=<path>)\t" . TERM_COLOR_BLUE . "*optional*" . TERM_RESET . " the path to the download directory - by default script directory (e.g. /Users/username/Desktop)" . PHP_EOL);
            fwrite(STDOUT, "\t-u (or --unlimited)\t\t\t" . TERM_COLOR_BLUE . "*optional*" . TERM_RESET . " a flag to tell the script to download every photo available (might take a while ^^)" . PHP_EOL);
            fwrite(STDOUT, "\t-c (or --continue)\t\t\t" . TERM_COLOR_BLUE . "*optional*" . TERM_RESET . " a flag to tell the script to continue even if it encounters existing files" . PHP_EOL);
            fwrite(STDOUT, "\t--wallfilter\t\t\t\t" . TERM_COLOR_BLUE . "*optional*" . TERM_RESET . " apply filter for only downloading photos that might be used as wallpapers (checks: landscape, ratio, dimensions)" . PHP_EOL);
            fwrite(STDOUT, "\t--blacklist=<extensions>\t\t" . TERM_COLOR_BLUE . "*optional*" . TERM_RESET . " extensions of files " . TERM_UNDERLINE . "NOT" . TERM_RESET . " to download (separated by commas (,) with no space)" . PHP_EOL);
            fwrite(STDOUT, "\t--whitelist=<extensions>\t\t" . TERM_COLOR_BLUE . "*optional*" . TERM_RESET . " extensions of files to download (separated by commas (,) with no space)" . PHP_EOL);
            fwrite(STDOUT, "\t-h (or --help)\t\t\t\tprints this help" . PHP_EOL . PHP_EOL);

            fwrite(STDOUT, TERM_UNDERLINE . "Examples:" . TERM_RESET . PHP_EOL . PHP_EOL);

            fwrite(STDOUT, "\tphp $arguments[0] -b brainmess -u (download every photo available)" . PHP_EOL);
            fwrite(STDOUT, "\tphp $arguments[0] -b brainmess -l=50 (download the last 50 photos)" . PHP_EOL);
            fwrite(STDOUT, "\tphp $arguments[0] -b brainmess --wallfilter --whitelist=jpg,png (download photos with extension jpg or png that are fit for being wallpapers)" . PHP_EOL);
            fwrite(STDOUT, "\tphp $arguments[0] --blog brainmess --offset=100 --limit=50 --directory=/Users/username/Desktop (download 50 photos on the desktop by skipping the last 100 posts)" . PHP_EOL);
            fwrite(STDOUT, "\t..." . PHP_EOL . PHP_EOL);

            fwrite(STDOUT, TERM_UNDERLINE . "Notes:" . TERM_RESET . PHP_EOL . PHP_EOL);

            fwrite(STDOUT, "\t- short and long options can be used interchangeably (if available)" . PHP_EOL);
            fwrite(STDOUT, "\t- do not specify an option more than once (unexpected behavior might occur)" . PHP_EOL);
            fwrite(STDOUT, "\t- the 'offset' and 'limit' refers to the post count, not the photo count (!) as there may be more than one photo in a post." . PHP_EOL);
            fwrite(STDOUT, "\t- download directory path must be absolute (/Users/username/Desktop instead of ~/Desktop)" . PHP_EOL);
            fwrite(STDOUT, "\t- once the script encounters an already downloaded photo (test for an existing file) it will stop (except when -c or --continue option is used)" . PHP_EOL);
            fwrite(STDOUT, "\t- if the original photo is not available, the script try an download the next available bigger size." . PHP_EOL);
            fwrite(STDOUT, "\t- photos are downloaded following this architecture path_to_download_directory/blog_name/yyyy/mm/yyyymmdd_basename.extension" . PHP_EOL);
            fwrite(STDOUT, "\t- if you use filters (wallfilter, black/whitelist) you may end up with empty folders as they are created before the download (room for improvement)." . PHP_EOL . PHP_EOL);
            fwrite(STDOUT, "\t- the script checks first if the file's extension is in the blacklist and then in the whitelist therefore if you both allow and deny an extension it will be denied." . PHP_EOL);
            fwrite(STDOUT, "\t- the script converts extension to lowercase so you don't have to worray wheter it is JPG or jpg..." . PHP_EOL);

            die(0);
        }


        // test presence of required fields (blog_name)
        if (!array_key_exists("b", $options) && !array_key_exists("blog", $options)) {
            fwrite(STDERR, TERM_UNDERLINE . TERM_COLOR_RED . "ERROR:" . TERM_RESET . " Bad usage!" . PHP_EOL);
            fwrite(STDERR, "\tsee php $arguments[0] -h (or --help) for usage" . PHP_EOL);

            die(1);
        }


        // retrieve blog name from options
        $this->blog_name = ((array_key_exists("b", $options)) ? $options['b'] : $options['blog']);


        // setup download directory
        if (array_key_exists('d', $options) || array_key_exists('directory', $options)) {
            $this->download_directory = ((array_key_exists('d', $options)) ? $options['d'] : $options['directory']);

            if (is_null($this->download_directory) || empty($this->download_directory) || $this->download_directory == ".") {
                $this->download_directory = dirname(__FILE__); // default directory
            }
        } 
        else {
            $this->download_directory = dirname(__FILE__); // default directory
        }
        if (!is_dir($this->download_directory)) {
            $date_string = $this->date_now_string();
            fwrite(STDERR, "[$date_string] $this->blog_name > " . TERM_UNDERLINE . TERM_COLOR_RED . "ERROR:" . TERM_RESET . " given download directory path ($this->download_directory) doesn't exist, is not a directory or is not accesible (possibly permission denied)!" . PHP_EOL);

            die(1);
        }
        $this->download_directory .= DIRECTORY_SEPARATOR . $this->blog_name; // append blog name

        if (!file_exists($this->download_directory)) {
            mkdir($this->download_directory); // create blog directory

            $date_string = $this->date_now_string();
            fwrite(STDOUT, "[$date_string] $this->blog_name > created blog directory!" . PHP_EOL);
        }


        // offset
        $this->offset = 0;
        if (array_key_exists('o', $options) || array_key_exists('offset', $options)) {
            $this->offset = ((array_key_exists('o', $options)) ? $options['o'] : $options['offset']);
        }


        // limit
        $this->limit = API_LIMIT;
        if (array_key_exists('l', $options) || array_key_exists('limit', $options)) {
            $this->limit = ((array_key_exists('l', $options)) ? $options['l'] : $options['limit']);
        }


        // unlimited 
        $this->unlimited = false;
        if (array_key_exists('u', $options) || array_key_exists('unlimited', $options)) {
            $this->unlimited = true;
        }


        // continue
        $this->continue = false;
        if (array_key_exists('c', $options) || array_key_exists('continue', $options)) {
            $this->continue = true;
        }


        // wallfilter
        $this->wallfilter = false;
        if (array_key_exists('wallfilter', $options)) {
            $this->wallfilter = true;
        }


        // blacklist
        $this->blacklist = array();
        if (array_key_exists('blacklist', $options)) {
            $exploded = explode(',', $options['blacklist']);
            foreach ($exploded as $extension) {
                $this->blacklist[] = strtolower($extension);
            }
        }


        // whitelist
        $this->whitelist = array();
        if (array_key_exists('whitelist', $options)) {
            $exploded = explode(',', $options['whitelist']);
            foreach ($exploded as $extension) {
                $this->whitelist[] = strtolower($extension);
            }
        }


        // counters
        $this->counter_processed = 0;
        $this->counter_downloaded = 0;
    }

    /** process
     * 
     * @since 0.2
     * @description public interface to launch the download process
     */
    public function process() {
        $date_string = $this->date_now_string();
        fwrite(STDOUT, "[$date_string] Started processing '$this->blog_name'" . PHP_EOL);

        $this->loop_through_posts();

        $date_string = $this->date_now_string();
        fwrite(STDOUT, "[$date_string] Done processing '$this->blog_name': $this->counter_downloaded/$this->counter_processed photo(s) downloaded." . PHP_EOL);

        die(0);
    }

    /** date_now_string
     * 
     * @since 0.3
     * @description provide a formated string of the current date
     * @return string
     */
    private function date_now_string() {
        return date("Y-m-d H:i:s");
    }

    /** pad_string
     * 
     * @since 0.6
     * @descritpion get a string composed of dots to pad output to the width of the terminal
     * @param string $head the string to print before
     * @param string $tail the string to print after
     * @return string
     */
    private function pad_string($head, $tail) {
        $term_cols = intval(`tput cols`);
        $length = $term_cols - strlen($head) - strlen($tail);
        $length = ($length > MIN_PAD ? $length : MIN_PAD);
        return str_repeat('.', $length);
    }

    /** download_photo
     * 
     * @since 0.2
     * @description download a photo from given url to given path
     * @param string $photo_url
     * @param string $download_path
     */
    private function download_photo($photo_url, $download_path) {
        $date_string = $this->date_now_string();
        $head = "[$date_string] $this->blog_name > $this->file_name";
        $output = @fopen($download_path, "wb");
        if ($output == FALSE) {
            $tail = "error: could not open file at path: $download_path!";
            $pad = $this->pad_string($head, $tail);
            fwrite(STDERR, TERM_RESTORE_POSITION . $head . $pad . TERM_COLOR_RED . $tail . TERM_RESET . PHP_EOL);

            return;
        }

        $tail = "downloading";
        $pad = $this->pad_string($head, $tail);
        fwrite(STDOUT, TERM_RESTORE_POSITION . $head . $pad . $tail);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $photo_url);
        curl_setopt($ch, CURLOPT_FILE, $output);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);

        $error = curl_error($ch);
        if (!empty($error)) {
            $tail = "error: $error";
            $pad = $this->pad_string($head, $tail);
            fwrite(STDERR, TERM_RESTORE_POSITION . $head . $pad . TERM_COLOR_RED . $tail . TERM_RESET . PHP_EOL);
            curl_close($ch);
            fclose($output);
            return;
        }

        curl_close($ch);
        fclose($output);

        // apply wallfiler if required
        if ($this->wallfilter) {
            $result = $this->filter_wallpapers($download_path);
            if ($result) {
                return;
            }
        }

        $this->counter_downloaded++;
        $counter_string = sprintf("%03d", $this->counter_downloaded);
        $tail = "downloaded [$counter_string]";
        $pad = $this->pad_string($head, $tail);
        fwrite(STDOUT, TERM_RESTORE_POSITION . $head . $pad . TERM_COLOR_GREEN . $tail . TERM_RESET . PHP_EOL);
    }

    /** filter_wallpapers
     *
     * @since 0.5
     * @description deletes the file at the given path if it is not a suitable candidate for a wallpaper:
     * conditions:
     * - it must be an image
     * - the image must be landscape (width > height)
     * - it must have a ratio between MIN_RATIO and MAX_RATIO
     * - it must be larger than MIN_WIDTH
     * @param string $download_path
     * @param boolean whether the file was deleted
     */
    private function filter_wallpapers($download_path) {
        $deletion_flag = false;
        $deletion_reason = 'unknown';
        $image_data = @getimagesize($download_path);
        if ($image_data) {
            $width = $image_data[0];
            $height = $image_data[1];
            $ratio = round($width/$height, 2);

            if ($width < $height) {
                $deletion_flag = true;
                $deletion_reason = 'portrait';
            } 
            else if ($width < MIN_WIDTH) {
                $deletion_flag = true;
                $deletion_reason = "width too small: $width < " . MIN_WIDTH;
            } 
            else if ($ratio < MIN_RATIO) {
                $deletion_flag = true;
                $deletion_reason = "ratio too small: $ratio < " . MIN_RATIO;
            } 
            else if ($ratio > MAX_RATIO) {
                $deletion_flag = true;
                $deletion_reason = "ratio too big: $ratio > " . MAX_RATIO;
            }
        }
        else {
            // file is not an image
            $deletion_flag = true;
            $deletion_reason = 'not an image';
        }

        if ($deletion_flag) {
            // file marked for deletion - delete it
            $date_string = $this->date_now_string();
            $head = "[$date_string] $this->blog_name > $this->file_name";
            $tail = "skipped: $deletion_reason!";
            $pad = $this->pad_string($head, $tail);
            fwrite(STDOUT, TERM_RESTORE_POSITION . $head . $pad . TERM_COLOR_YELLOW . $tail . TERM_RESET . PHP_EOL);
            unlink($download_path);
        }

        return $deletion_flag;
    }

    /** loop_through_posts
     * 
     * @since 0.2
     * description loops through the post and download the photos
     */
    private function loop_through_posts() {
        do {
            $posts = $this->get_blog_posts();

            if (empty($posts)) {
                return; // no more posts - end of loop
            }

            foreach ($posts as $post) {
                // loop through posts
                $photos = $post->photos;

                if (count($photos) == 0) {
                    // no photo in this post - continue
                    continue;
                }

                $photo_date = strtotime($post->date);
                $year = date("Y", $photo_date);
                $month = date("m", $photo_date);
                $day = date("d", $photo_date);

                $current_directory = $this->download_directory.DIRECTORY_SEPARATOR.$year.DIRECTORY_SEPARATOR.$month; // path_to_download_directory/blog_name/yyyy/mm
                if (!is_dir($current_directory)) {
                    mkdir($current_directory, 0777, true); // photo directory doesn't exist - create it
                }

                for ($i = 0; $i < count($photos); $i++) {
                    // loop through post photos
                    $photo = $photos[$i];
                    
                    $photo_url = $photo->original_size->url; // retrieve original size - preferred size
                    $pathinfo = pathinfo($photo_url); // retrieve path info (basename, extension, ...)
                    
                    if (empty($pathinfo['extension'])) {
                        // if no extension (invalid photo) - proceed with alternative sizes
                        foreach ($photo->alt_sizes as $alt_photo) {
                            $photo_url = $alt_photo->url;
                            $pathinfo = pathinfo($photo_url);

                            if (!empty($pathinfo['extension'])) {
                                // extension is not empty - we have a winner
                                break;
                            }
                        }
                    }

                    $this->file_name = $year.$month.$day."_".$pathinfo['basename']; // yyyymmdd_basename.extension
                    fwrite(STDOUT, TERM_SAVE_POSITION);
                    $date_string = $this->date_now_string();
                    $head = "[$date_string] $this->blog_name > $this->file_name";

                    if (empty($pathinfo['extension'])) {
                        // no winner found - skipping photo
                        $tail = "skipped: no valid url found!";
                        $pad = $this->pad_string($head, $tail);
                        fwrite(STDOUT, TERM_RESTORE_POSITION . $head . $pad . TERM_COLOR_YELLOW . $tail . TERM_RESET . PHP_EOL);

                        continue;
                    }
                    $extension = strtolower($pathinfo['extension']);
                    if (in_array($extension, $this->blacklist)) {
                        // extension is denied - skipping photo
                        $tail = "skipped: blacklisted '$extension'!";
                        $pad = $this->pad_string($head, $tail);
                        fwrite(STDOUT, TERM_RESTORE_POSITION . $head . $pad . TERM_COLOR_YELLOW . $tail . TERM_RESET . PHP_EOL);

                        continue;
                    }
                    if (count($this->whitelist) != 0 && !in_array($extension, $this->whitelist)) {
                        // extension is not allowed - skipping photo
                        $tail = "skipped: '$extension' is not in the whitelist!";
                        $pad = $this->pad_string($head, $tail);
                        fwrite(STDOUT, TERM_RESTORE_POSITION . $head . $pad . TERM_COLOR_YELLOW . $tail . TERM_RESET . PHP_EOL);

                        continue;
                    }

                    $tail = "preparing";
                    $pad = $this->pad_string($head, $tail);
                    fwrite(STDOUT, TERM_RESTORE_POSITION . $head . $pad . $tail);

                    $download_path = $current_directory.DIRECTORY_SEPARATOR.$this->file_name; // concat download_directory with file_name

                    $this->counter_processed++;

                    $found = false;
                    if (is_file($download_path)) {
                        // file already exist - abort and exit
                        $found = true;

                        $tail = "already exists!";
                        $pad = $this->pad_string($head, $tail);
                        fwrite(STDOUT, TERM_RESTORE_POSITION . $head . $pad . TERM_COLOR_GREEN . $tail . TERM_RESET . PHP_EOL);

                        if (!$this->continue) {
                            return;
                        }
                    }
                    if (!$found) {
                        $this->download_photo($photo_url, $download_path);
                    }
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
    private function get_blog_posts() {
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

        if (is_null($json_output) || $json_output->meta->status == 404) {
            $date_string = $this->date_now_string();
            fwrite(STDERR, "[$date_string] $this->blog_name > " . TERM_UNDERLINE . TERM_COLOR_RED . "ERROR:" . TERM_RESET . " Could not find blog named '$this->blog_name'!" . PHP_EOL);

            return array();
        }

        return $json_output->response->posts;
    }
}

$downloader = new TumblrDownloader($argv, $argc, $options);
$downloader->process();

?>
