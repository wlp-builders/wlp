#!/bin/bash

# Check if the script is run as root (sudo)
if [ "$(id -u)" -ne 0 ]; then
    echo "Error: This script must be run with sudo or as root."
    exit 1
fi

# Check if the shell is bash
if [ -z "$BASH_VERSION" ]; then
    echo "Warning: This script requires 'sudo bash script.sh'"
    exit 1
fi

# Check if the correct number of arguments are provided
if [ "$#" -ne 1 ]; then
    echo "Usage: $0 <path_to_folder>"
    exit 1
fi

if [ -f "$1/wlp-config.php" ]; then
    echo "wlp-config.php already exists";
    exit 1
fi

# Get the full path and base folder name
FOLDER_PATH=$(realpath "$1")
FOLDER_NAME=$(basename "$FOLDER_PATH")

# Replace '.' with '__' in the folder name
SANITIZED_NAME=$(echo "$FOLDER_NAME" | sed 's/\./__/g' | sed 's/\-/_/g')

# Generate database name, username, and password
DATABASE_NAME="${SANITIZED_NAME}_db"
TS=$(date +%s)
USERNAME="${SANITIZED_NAME}_user_${TS}"
PASSWORD=$(openssl rand -base64 32 | tr '+/' '-_' | tr -d '=')

# Open MySQL and execute commands
sudo mysql <<EOF
CREATE DATABASE IF NOT EXISTS $DATABASE_NAME;
CREATE USER IF NOT EXISTS '$USERNAME'@'localhost' IDENTIFIED BY '$PASSWORD';
GRANT ALL PRIVILEGES ON $DATABASE_NAME.* TO '$USERNAME'@'localhost';
FLUSH PRIVILEGES;
EOF

# Parse the JSON output to extract database details
DB_NAME=$DATABASE_NAME
DB_USER=$USERNAME
DB_PASS=$PASSWORD
DOMAIN_NAME=$FOLDER_NAME

# Construct the URL for the GET request (without query parameters)
URL="http://$DOMAIN_NAME/wlp-install/index.php"

# Send request to generate install-key.php for $_GET auto install
curl -s "$URL" > /dev/null

sleep 1 # for file to be created

# Extract the key from the PHP file (assumed to be between single quotes)
INSTALL_KEY=$(grep -oP "define\('install_key', '\K[^']+" "$FOLDER_PATH/wlp-install/install-key.php")

# Construct the URL with query parameters for Firefox (including db_name, db_user, db_pass)
URL_WITH_PARAMS="http://$DOMAIN_NAME/wlp-install?install_key=$INSTALL_KEY&db_name=$DB_NAME&db_user=$DB_USER&db_pass=$DB_PASS"

# Open the URL with query parameters in Firefox
echo "$URL_WITH_PARAMS"

