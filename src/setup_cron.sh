PHP_PATH=$(which php)
SCRIPT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/cron.php"
LOG_FILE="$HOME/xkcd_cron.log"
chmod +x "$SCRIPT_PATH"
CRON_JOB="0 9 * * * $PHP_PATH $SCRIPT_PATH >> $LOG_FILE 2>&1"
(crontab -l 2>/dev/null | grep -Fv "$SCRIPT_PATH"; echo "$CRON_JOB") | crontab -
echo "✅ CRON job set: $SCRIPT_PATH will run daily at 9:00 AM."
