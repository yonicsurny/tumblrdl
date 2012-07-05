#!/bin/bash

echo -e "PROCESS START" \
&& php tumblrdl.php -b brainmess \
&& php tumblrdl.php -b bonjourlesgeeks \
&& php tumblrdl.php -b chersvoisins \
&& echo -e "PROCESS FINISHED"