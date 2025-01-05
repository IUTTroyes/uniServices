#!/bin/bash

# Define the source directory
SOURCE_DIR="front/packages/common-images"

# Loop through each application directory in apps/*/public
for APP_DIR in front/apps/*/public; do
  # Check if the directory exists
  if [ -d "$APP_DIR" ]; then
    # Remove existing symbolic link if it exists
      if [ -L "$APP_DIR/common-images" ]; then
        rm "$APP_DIR/common-images"
        echo "Removed existing symlink for $APP_DIR"
      fi
    # Create a symbolic link in the public directory
    ln -s "$(pwd)/$SOURCE_DIR/" "$APP_DIR/"
    echo "Created symlink for $APP_DIR"
  else
    echo "Directory $APP_DIR does not exist"
  fi
done
