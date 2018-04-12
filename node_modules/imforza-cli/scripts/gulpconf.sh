#!/bin/bash

# Constants for terminal colors
GREEN='\033[00;32m'
YELLOW='\033[00;93m'
RED='\033[00;91m'
END='\033[0m'

# Progress bar function
function progress_bar() {
	echo -ne "${YELLOW}==                        (10%)\r${END}"
	sleep .1
	echo -ne "${YELLOW}======                    (25%)\r${END}"
	sleep .1
	echo -ne "${YELLOW}=========                 (33%)\r${END}"
	sleep .1
	echo -ne "${YELLOW}=============             (50%)\r${END}"
	sleep .1
	echo -ne "${YELLOW}=================         (66%)\r${END}"
	sleep .1
	echo -ne "${YELLOW}===================       (75%)\r${END}"
	sleep .1
	echo -ne "${YELLOW}======================    (85%)\r${END}"
	sleep .1
	echo -ne "${YELLOW}========================= (100%)\r${END}"
	echo -ne '\n'
}

# Dat imFORZA ascii art
echo ' _             ______  ____   _____   ______          '
echo '(_)           |  ____|/ __ \ |  __ \ |___  /    /\    '
echo ' _  _ __ ___  | |__  | |  | || |__) |   / /    /  \   '
echo "| || '"'_ ` _ \ |  __| | |  | ||  _  /   / /    / /\ \ '
echo '| || | | | | || |    | |__| || | \ \  / /__  / ____ \ '
echo '|_||_| |_| |_||_|     \____/ |_|  \_\/_____|/_/    \_\'
echo ' _____                    '
echo '|  __ \                   '
echo '| |  | |  ___ __   __ ___ '
echo '| |  | | / _ \\ \ / // __|'
echo '| |__| ||  __/ \ V / \__ \'
echo '|_____/  \___|  \_/  |___/'
echo ''

# Ask for local domain name
echo -e -n "${GREEN}Local Install Domain: ${END}"
read domain

# Ask if theme or plugin
echo -e "${GREEN}Choose type: ${END}"
PS3="(1|2): "
select the_type in theme plugin
do
	# Display prompt again if input is wrong.
	if [ "theme" = "$the_type" ] || [ "plugin" = "$the_type" ]; then
    break
  else
    echo -e "${RED}Option invalid. Please enter 1 or 2${END}"
  fi
done

# Termination sequence
echo "var WEBSITE='$domain'; exports.WEBSITE=WEBSITE; var CONTENT_TYPE='$the_type'; exports.CONTENT_TYPE=CONTENT_TYPE" > gulpconf.js
echo -e  "${GREEN}Saving config${END}"
progress_bar
