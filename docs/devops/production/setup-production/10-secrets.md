# 09. Secrets и переменные окружения

```bash
sudo -u wwwtask php /var/www/task/bin/console secrets:generate-keys --env=prod
sudo -u wwwtask php /var/www/task/bin/console secrets:set APP_SECRET --env=prod
sudo -u wwwtask php /var/www/task/bin/console secrets:set TELEGRAM_BOT_TOKEN --env=prod
```

Также: `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, `MISTRAL_API_KEY`, `GEMINI_API_KEY`, `MAILER_DSN`, `SENTRY_DSN` и др.

