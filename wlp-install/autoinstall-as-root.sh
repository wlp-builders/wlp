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
if [ -z "$1" ]; then
    echo "Usage: $0 <path_to_folder> [optional:local_domain]"
    exit 1
fi

if [ -f "$1/wlp-config.php" ]; then
    echo "wlp-config.php already exists";
    exit 1
fi

# Get the full path and base folder name
FULL_FOLDER_PATH=$(realpath "$1")
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
if [ -n "$2" ]; then
	DOMAIN_NAME=$2
fi

# Construct the URL for the GET request (without query parameters)
URL="http://$DOMAIN_NAME/wlp-install/index.php"

# Send request to generate install-key.php for $_GET auto install
curl -s "$URL" > /dev/null

sleep 1 # for file to be created

# Extract the key from the PHP file (assumed to be between single quotes)
INSTALL_KEY=$(grep -oP "define\('install_key', '\K[^']+" "$FOLDER_PATH/wlp-install/install-key.php")


# Construct the URL with query parameters for Firefox (including db_name, db_user, db_pass)
URL_WITH_PARAMS="/wlp-install/index.php?install_key=$INSTALL_KEY&db_name=$DB_NAME&db_user=$DB_USER&db_pass=$DB_PASS"
INDEX_CONTENT=`cat $FULL_FOLDER_PATH/index.php`;

echo """<?php // AUTOINSTALL BEGIN
// Get the full URL
\$protocol = isset(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
\$host = \$_SERVER['HTTP_HOST'];
\$requestUri = \$_SERVER['REQUEST_URI'];
\$fullUrl = \$protocol . '://' . \$host . \$requestUri;

// Redirect to the new URL with \"/hello\" appended
\$newUrl = rtrim(\$fullUrl, '/') . '$URL_WITH_PARAMS';

// Check if the script can remove the AUTOINSTALL block
\$currentFile = __FILE__;

if (is_writable(\$currentFile)) {
    // Register shutdown function to edit the file after redirection
    register_shutdown_function(function() use (\$currentFile) {
        // Get the current content of the file
        \$fileContent = file_get_contents(\$currentFile);

        // Find the positions of the BEGIN and END markers
        \$startPos = strpos(\$fileContent, \"<?php // AUTOINSTALL BEGIN\");
        \$endPos = strpos(\$fileContent, str_replace('ENX','END',\"// AUTOINSTALL ENX ?>\"));

        // If both markers are found, remove everything between them, including the markers themselves
        if (\$startPos !== false && \$endPos !== false) {
            // Calculate the length from the start of the BEGIN marker to the END marker (inclusive of the END marker)
            \$length = \$endPos + strlen(\"// AUTOINSTALL ENX ?>\") - \$startPos;
            // Remove the content between the markers
            \$updatedContent = substr_replace(\$fileContent, '', \$startPos, \$length);
            // Save the updated file content
            file_put_contents(\$currentFile, \$updatedContent);
        }
    });

    // Redirect to the new URL
    header('Location: ' . \$newUrl);
    exit;
} else {
    // Handle error if file is not writable
    echo \"This script is not writable and cannot remove the autoinstall block.\";
    exit;
}
// AUTOINSTALL END ?>$INDEX_CONTENT
""" > $FULL_FOLDER_PATH/index.php


echo "Browse to index.php to install"

