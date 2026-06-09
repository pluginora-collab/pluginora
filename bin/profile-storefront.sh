#!/usr/bin/env bash

set -euo pipefail

iterations="${PLUGINORA_PROFILE_ITERATIONS:-5}"

if [[ "$#" -eq 0 ]]; then
    echo "Usage: $0 <url> [url ...]" >&2
    exit 1
fi

if ! [[ "$iterations" =~ ^[0-9]+$ ]] || [[ "$iterations" -lt 1 ]]; then
    echo "PLUGINORA_PROFILE_ITERATIONS must be a positive integer." >&2
    exit 1
fi

for url in "$@"; do
    echo "Profiling $url"

    timings="$({
        for ((i = 1; i <= iterations; i++)); do
            curl --location --silent --output /dev/null --write-out '%{time_total}\n' "$url"
        done
    })"

    printf '%s\n' "$timings" | awk '
        NR == 1 { min = $1; max = $1 }
        {
            sum += $1
            if ($1 < min) min = $1
            if ($1 > max) max = $1
        }
        END {
            printf "  runs=%d avg=%.3fs min=%.3fs max=%.3fs\n", NR, sum / NR, min, max
        }
    '
done