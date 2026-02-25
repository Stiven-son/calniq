#!/bin/bash
set -e
cd /var/www/calniq-stage

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${RED}⚠️  Deploy staging → production${NC}"
read -p "Are you sure? (y/n): " confirm
if [ "$confirm" != "y" ]; then
    echo "Cancelled."
    exit 0
fi

# 0. Backup production BEFORE anything
echo -e "${YELLOW}💾 Backing up production...${NC}"
cd /var/www/calniq
bash backup.sh "pre-deploy backup: $(date '+%Y-%m-%d %H:%M')"
sudo -u calniq pg_dump calniq_prod > /root/db_backup_$(date +%Y%m%d_%H%M%S).sql
echo -e "${GREEN}✅ DB backup saved to /root/${NC}"

# 1. Commit & push from staging
echo -e "${YELLOW}📦 Committing staging...${NC}"
cd /var/www/calniq-stage
git add .
git commit -m "deploy: $(date '+%Y-%m-%d %H:%M')" || echo "Nothing to commit"
git push origin main

# 2. Pull on production
echo -e "${YELLOW}🚀 Pulling on production...${NC}"
cd /var/www/calniq
git pull origin main

# 3. Rebuild
echo -e "${YELLOW}🔧 Rebuilding production...${NC}"
sudo -u calniq composer install --no-dev --no-interaction
sudo -u calniq php artisan migrate --force
sudo -u calniq php artisan config:cache
sudo -u calniq php artisan route:cache
sudo -u calniq php artisan view:cache
sudo -u calniq npm run build
bash /var/www/calniq/update-widget-links.sh
sudo systemctl restart calniq-worker

echo -e "${GREEN}✅ Production updated!${NC}"
echo -e "${GREEN}   Commit: $(git log --oneline -1)${NC}"
