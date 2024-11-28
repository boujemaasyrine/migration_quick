#!/bin/bash
set -euo pipefail

# Fonction pour afficher l'aide
usage() {
    echo "Usage: $0 <port>"
    echo "  <port>   The port number to monitor (must be a number between 1 and 65535)"
    exit 1
}

# Vérification si l'argument est fourni
if [[ $# -ne 1 ]]; then
    echo "Error: Port number is required."
    usage
fi

PORT=$1

# Vérification si l'argument est un nombre valide et qu'il est dans la plage des ports
if ! [[ "$PORT" =~ ^[0-9]+$ ]] || [[ "$PORT" -lt 1 ]] || [[ "$PORT" -gt 65535 ]]; then
    echo "Error: Invalid port number. Please provide a number between 1 and 65535."
    usage
fi

PROCESS_NAME="solo.pl -port=$PORT"
MAX_RUNTIME_SECONDS=1800  # 30 minutes
LOG_FILE="/var/log/cron/process_monitor_$(date +\%Y-\%m-\%d).log"

log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" | tee -a "$LOG_FILE"
}

get_process_info() {
    ps -eo pid,etime,cmd | grep "[s]olo.pl -port=$PORT" | head -n 1
}


ps -eo pid,etime,cmd | grep "[q]uick:wynd:rest:import"

convert_to_seconds() {
    echo "$1" | awk -F':' '
    {
        if (NF == 2) {
            print $1*60 + $2
        } else if (NF == 3) {
            split($1, a, "-")
            if (a[2] != "") {
                print ((a[1]*24+a[2])*60 + $2) * 60 + $3
            } else {
                print ($1*60 + $2) * 60 + $3
            }
        }
    }'
}

terminate_process() {
    local pid=$1
    if pgrep -P "$pid" > /dev/null; then
        log "Terminating child processes of PID $pid"
        pkill -TERM -P "$pid"
        sleep 5
        if pgrep -P "$pid" > /dev/null; then
            log "Force killing remaining child processes of PID $pid"
            pkill -KILL -P "$pid"
        fi
    else
        log "No child processes found for PID $pid"
    fi

    log "Terminating process $pid"
    if kill -TERM "$pid"; then
        sleep 5
        if kill -0 "$pid" 2>/dev/null; then
            log "Process $pid did not terminate gracefully. Forcing termination."
            kill -KILL "$pid"
        else
            log "Process $pid terminated successfully"
        fi
    else
        log "Failed to terminate process $pid"
    fi
}

main() {
    log "Starting process monitor for '$PROCESS_NAME'"

    process_info=$(get_process_info)
    if [[ -z "$process_info" ]]; then
        log "No process found running '$PROCESS_NAME'"
        exit 0
    fi

    pid=$(echo "$process_info" | awk '{print $1}')
    etime=$(echo "$process_info" | awk '{print $2}')
    seconds_elapsed=$(convert_to_seconds "$etime")

    if (( seconds_elapsed > MAX_RUNTIME_SECONDS )); then
        log "Process has been running for more than $(( MAX_RUNTIME_SECONDS / 60 )) minutes. Initiating termination."
        terminate_process "$pid"
    else
        log "Process has been running for $seconds_elapsed seconds. No action taken."
    fi

    log "Process monitor completed"
}

main "$@"