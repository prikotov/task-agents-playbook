# 07. База данных

```bash
# создание роли и БД
sudo -u postgres psql -c "CREATE ROLE task_user LOGIN PASSWORD '***';"
sudo -u postgres createdb -O task_user task_db
sudo -u postgres psql -d task_db -c "CREATE EXTENSION IF NOT EXISTS vector;"

# перенос из dev → prod
sudo -u postgres pg_dump -Fc task_dev_db > /_backups/pg/task_dev_db.dump
sudo -u postgres pg_restore --no-owner --role=task_user -d task_db /_backups/pg/task_dev_db.dump
```

Резервные копии:
```bash
( crontab -l -u postgres 2>/dev/null; echo '5 3 * * * pg_dump -Fc task_db > /_backups/pg/task_db_$(date +\\%F).dump && find /_backups/pg -name "task_db_*.dump" -mtime +7 -delete' ) | sudo crontab -u postgres -
```
