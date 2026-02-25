#!/bin/bash
cd /var/www/calniq

# Check for uncommitted changes
CHANGES=$(git diff --stat)
UNTRACKED=$(git ls-files --others --exclude-standard)

if [ -z "$CHANGES" ] && [ -z "$UNTRACKED" ]; then
    exit 0
fi

# Build message
MSG="⚠️ Uncommitted changes detected on production server!\n\n"

if [ -n "$CHANGES" ]; then
    MSG+="Modified files:\n${CHANGES}\n\n"
fi

if [ -n "$UNTRACKED" ]; then
    MSG+="Untracked files:\n${UNTRACKED}\n\n"
fi

MSG+="Server: $(hostname)\nTime: $(date '+%Y-%m-%d %H:%M:%S')"

# Send via Laravel/Resend
cd /var/www/calniq
sudo -u calniq php artisan tinker --execute="
Mail::raw('$(echo -e "$MSG")', function(\$m) {
    \$m->to('profiadverts@gmail.com')->subject('⚠️ Calniq: Uncommitted changes on production');
});
"
