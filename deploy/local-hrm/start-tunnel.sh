#!/usr/bin/env sh
set -eu

VPS_HOST="${1:-157.66.46.75}"
VPS_USER="${2:-root}"

echo "Open SSH tunnel: localhost:13306 -> ${VPS_HOST}:3306"
exec ssh -N -L 13306:127.0.0.1:3306 "${VPS_USER}@${VPS_HOST}"
