#!/bin/bash

# 1. Define the log file and target directory
LOG_FILE="/var/www/html/reports/ci_master.log"
CI_DIR="/var/www/html/scripts/ci"

mkdir -p "$(dirname "$LOG_FILE")"
: > "$LOG_FILE"

# 2. The Master Wrapper: mirror all output to terminal and log file
exec > >(tee -a "$LOG_FILE") 2>&1

echo "========================================"
echo " Starting CI Pipeline Run"
echo " Date: $(date)"
echo " Target Directory: $CI_DIR"
echo "========================================"

# 3. Validate that the target directory actually exists
if [ ! -d "$CI_DIR" ]; then
    echo "FATAL ERROR: The directory '$CI_DIR' does not exist."
    exit 1
fi

# 4. Loop through every .sh file in the directory
for script in "$CI_DIR"/*.sh; do

    # Check if the file actually exists (prevents errors if the folder is empty)
    if [ -e "$script" ]; then
        echo ""
        echo "--- Executing: $(basename "$script") at $(date) ---"

        # Run the script using 'bash' to bypass missing execute (+x) permissions
        bash "$script"

        # Capture the exit code to log success or failure
        EXIT_CODE=$?
        if [ $EXIT_CODE -ne 0 ]; then
            echo "--> WARNING: $(basename "$script") failed with exit code $EXIT_CODE"
        else
            echo "--> SUCCESS: $(basename "$script") completed."
        fi
    else
        echo "No bash scripts (*.sh) found in $CI_DIR."
    fi
done

echo ""
echo "========================================"
echo " CI Pipeline Run Complete at $(date)"
echo "========================================"
