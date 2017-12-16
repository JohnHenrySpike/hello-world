#!/bin/bash
echo "Backup started at " `date +%H:%M:%S' '%d-%m-%Y`
SITE_PATH=/var/www/gis.ktga.kz
BACKUP_PATH=/backup/
DATE=$(date +%d%m%Y)
echo "****************"
echo "Runing database backup..."
pg_dump -d gis -Upostgres | gzip > $SITE_PATH$BACKUP_PATH'gis_'$DATE'.dump.gz'
echo "Database backup complete"
echo "Runing files backup..."
tar -czvPf $SITE_PATH$BACKUP_PATH'gis_'$DATE'.tar.gz' --exclude='.svn' --exclude='backend/web/assets' --exclude='backend/runtime' --exclude='frontend/runtime'--exclude='backup' --exclude='frontend/web/assets' $SITE_PATH
echo "Files backup complete"
echo "********************"
echo "* Backup complete! *"
echo "********************"
unset DATE
unset SITE_PATH
unset BACKUP_PATH
echo "Backup completed at " `date +%H:%M:%S' '%d-%m-%Y`
