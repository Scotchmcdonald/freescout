#!/bin/bash
# Start, stop, or restart the Laravel development servers.

# --- Configuration ---
PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." &> /dev/null && pwd )"
SERVE_PID_FILE="$PROJECT_DIR/storage/logs/serve.pid"
VITE_PID_FILE="$PROJECT_DIR/storage/logs/vite.pid"

# --- Functions ---

start_servers() {
    echo "Starting Laravel development server..."
    cd "$PROJECT_DIR"
    php artisan serve &
    echo $! > "$SERVE_PID_FILE"

    echo "Starting Vite development server..."
    npm run dev &
    echo $! > "$VITE_PID_FILE"

    echo "Development servers started."
    echo "Laravel PID: $(cat $SERVE_PID_FILE)"
    echo "Vite PID: $(cat $VITE_PID_FILE)"
}

stop_servers() {
    echo "Stopping development servers..."
    if [ -f "$SERVE_PID_FILE" ]; then
        kill $(cat "$SERVE_PID_FILE")
        rm "$SERVE_PID_FILE"
    fi
    if [ -f "$VITE_PID_FILE" ]; then
        kill $(cat "$VITE_PID_FILE")
        rm "$VITE_PID_FILE"
    fi
    echo "Servers stopped."
}

# --- Main Logic ---

case "$1" in
    start)
        start_servers
        ;;
    stop)
        stop_servers
        ;;
    restart)
        stop_servers
        sleep 2
        start_servers
        ;;
    *)
        echo "Usage: $0 {start|stop|restart}"
        exit 1
        ;;
esac
