#!/bin/bash
set -e
cd /var/www/calniq

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

if [ ! -d ".git" ]; then
    echo -e "${RED}Error: Not a git repository${NC}"
    exit 1
fi

echo -e "${YELLOW}📋 Changes:${NC}"
git status --short

if git diff --quiet && git diff --cached --quiet && [ -z "$(git ls-files --others --exclude-standard)" ]; then
    echo -e "${GREEN}✅ Nothing to commit — working tree clean${NC}"
    exit 0
fi

if [ -n "$1" ]; then
    MSG="$1"
else
    MSG="backup: $(date '+%Y-%m-%d %H:%M')"
fi

git add .
echo -e "${YELLOW}📦 Committing: ${MSG}${NC}"
git commit -m "$MSG"

echo -e "${YELLOW}🚀 Pushing to GitHub...${NC}"
git push origin main

echo -e "${GREEN}✅ Backup complete!${NC}"
echo -e "${GREEN}   Commit: $(git log --oneline -1)${NC}"
