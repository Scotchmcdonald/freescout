#!/bin/bash
set -e

# Memory limit to prevent OOM
PHP_MEM="php -d memory_limit=1024M"

echo "========================================================"
echo "RUNNING BATCH 1: Unit Tests (Part A)"
echo "========================================================"
$PHP_MEM vendor/bin/pest tests/Unit/Controllers tests/Unit/Console --stop-on-failure

echo ""
echo "========================================================"
echo "RUNNING BATCH 1.5: Unit Tests (Part B)"
echo "========================================================"
$PHP_MEM vendor/bin/pest tests/Unit/ --exclude-group=controllers,console --stop-on-failure

echo ""
echo "========================================================"
echo "RUNNING BATCH 2: Feature Tests [A-I]"
echo "========================================================"
# Admin, Auth, Billing, Caching, Console, Conversation, Core, Customer, Integration, InterfaceSegregation
$PHP_MEM vendor/bin/pest tests/Feature/[A-I]* --stop-on-failure

echo ""
echo "========================================================"
echo "RUNNING BATCH 3: Feature Tests [M-P]"
echo "========================================================"
# Mailbox, Modules, Observability, PIB, Partials, Payment, Performance, Public
$PHP_MEM vendor/bin/pest tests/Feature/[M-P]* --stop-on-failure

echo ""
echo "========================================================"
echo "RUNNING BATCH 4: Feature Tests [Q-Z]"
echo "========================================================"
# Queue, Security, Settings, Smoke, System, Theme, User, Webhooks
$PHP_MEM vendor/bin/pest tests/Feature/[Q-Z]* --stop-on-failure

echo ""
echo "========================================================"
echo "RUNNING BATCH 5: Module Tests"
echo "========================================================"
$PHP_MEM vendor/bin/pest Modules/*/Tests/ Modules/*/tests/ --stop-on-failure

echo ""
echo "========================================================"
echo "ALL TESTS PASSED SUCCESSFULLY"
echo "========================================================"
