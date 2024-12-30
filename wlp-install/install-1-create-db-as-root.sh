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

# Get the full path and base folder name
FOLDER_PATH=$(realpath "$1")
FOLDER_NAME=$(basename "$FOLDER_PATH")

# Replace '.' with '__' in the folder name
SANITIZED_NAME=$(echo "$FOLDER_NAME" | sed 's/\./__/g' | sed 's/\-/_/g')

# Generate database name, username, and password
DATABASE_NAME="${SANITIZED_NAME}_db"
USERNAME="${SANITIZED_NAME}_user"
PASSWORD=$(openssl rand -base64 32 | tr '+/' '-_' | tr -d '=')

# Open MySQL and execute commands
sudo mysql <<EOF
CREATE DATABASE IF NOT EXISTS $DATABASE_NAME;
CREATE USER IF NOT EXISTS '$USERNAME'@'localhost' IDENTIFIED BY '$PASSWORD';
GRANT ALL PRIVILEGES ON $DATABASE_NAME.* TO '$USERNAME'@'localhost';
FLUSH PRIVILEGES;
EOF

# Output the details as a JSON object
JSON_OUTPUT=$(cat <<EOF
{
  "db_name": "$DATABASE_NAME",
  "db_user": "$USERNAME",
  "db_pass": "$PASSWORD"
}
EOF
)

echo "$JSON_OUTPUT"


