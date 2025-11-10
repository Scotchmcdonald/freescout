# Makefile for running tests and generating reports
# This Makefile is now a legacy wrapper. Use scripts/run_tests.sh for a better experience.

.PHONY: all test-all generate-report clean

# Default target: Run all tests via the new script
all:
	@scripts/run_tests.sh

# Kept for backward compatibility
test-all: all

# Clean up generated files
clean:
	@echo "Cleaning up reports and coverage..."
	@rm -rf reports
	@rm -rf coverage-report
