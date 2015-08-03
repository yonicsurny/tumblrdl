#!/bin/bash
# this script is called in crontab once per hour
# it automates the process of downloading every photo of the given tumblr

# declare array of tumblr to download
declare -a arr=(
	"brainmess" 
	"bonjourlesgeeks" 
	"chersvoisins"
)

###############################################################################

# storing count if variable
count=${#arr[@]}

for (( i = 0; i < $count ; i++ )); do

	# print status
    printf "\n**** Processing[$((i+1))/$count]: ${arr[$i]} ****\n\n"

    # run downloader for each tumblr in array
    eval "php tumblrdl.php -b ${arr[$i]} -u --wallfilter --whitelist=jpg,jpeg,png"

done