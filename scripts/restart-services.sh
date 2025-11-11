#!/bin/bash
# Restart all Supervisor-managed services for FreeScout.

set -e

echo "Restarting all FreeScout services (Supervisor)..."
sudo supervisorctl restart all
echo "Services restarted."
echo ""
echo "Current status:"
sudo supervisorctl status
