#!/bin/bash
BOT_TOKEN="8788435077:AAFWoIAJOaFXDdzaDjfmMCFh2NlpYbNNxic"
LOG_FILE="/Users/ayaghoury/.gemini/antigravity-ide/brain/43a0a65c-5543-48b1-93cc-966182b4a11e/.system_generated/tasks/task-3519.log"
CURRENT_URL=""

while true; do
    # Get the latest URL from the log file
    NEW_URL=$(grep "\.lhr\.life" "$LOG_FILE" | tail -n 1 | grep -o "https://[a-zA-Z0-9.-]*\.lhr\.life")
    if [ ! -z "$NEW_URL" ] && [ "$NEW_URL" != "$CURRENT_URL" ]; then
        CURRENT_URL=$NEW_URL
        echo "Tunnel changed! Updating Telegram webhook to $CURRENT_URL..."
        curl -s -X POST "https://api.telegram.org/bot$BOT_TOKEN/setWebhook?url=$CURRENT_URL/webhook/telegram" > /dev/null
    fi
    sleep 2
done
