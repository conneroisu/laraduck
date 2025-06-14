#!/usr/bin/env bash
set -e

# Copy examples directory
cp -r ../examples ./examples

# Build using bun directly
ASTRO_TELEMETRY_DISABLED=1 bun --bun run build