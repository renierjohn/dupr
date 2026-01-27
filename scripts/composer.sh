#!/bin/bash

# 1. Load the .env file
# This sed command ignores comments and empty lines, then exports variables
if [ -f .env ]; then
  export $(grep -v '^#' .env | xargs)
else
  echo "Error: .env file not found."
  exit 1
fi

# 2. Check if the ENV variable was actually loaded
if [ -z "$ENV" ]; then
  echo "Error: ENV variable is not set in .env file."
  exit 1
fi

# 3. Execute the command inside the container
# "$@" passes all arguments from the script to the drush command
docker exec -it "$ENV" composer "$@"
