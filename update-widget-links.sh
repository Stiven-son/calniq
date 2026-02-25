#!/bin/bash
CSS_FILE=$(ls -t /var/www/calniq/public/build/assets/widget-*.css 2>/dev/null | head -1)
if [ -n "$CSS_FILE" ]; then
    rm -f /var/www/calniq/public/widget-assets/bookingstack.css
    ln -s "$CSS_FILE" /var/www/calniq/public/widget-assets/bookingstack.css
    echo "Updated CSS symlink → $CSS_FILE"
fi
