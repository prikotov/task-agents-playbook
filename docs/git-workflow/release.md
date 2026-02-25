# Релизы и CHANGELOG

Этот гайд стандартизирует процесс выпуска релизов и ведения CHANGELOG в проекте TasK для разработчиков и maintainers. Основан на Conventional Commits и semver.

## Что уже настроено в проекте
- Conventional Commits: интерактивный помощник `vendor/bin/conventional-commits` для подготовки сообщений коммитов.
- Генератор changelog/релизов: `vendor/bin/conventional-changelog` (скрипты в composer.json).
- Make-цели (простые алиасы):
  - `make prepare-commit` — помощник сообщений коммитов.
  - `make changelog` — сгенерировать/обновить `CHANGELOG.md` из коммитов (без коммита).
  - `make release` — автотип patch; обновит CHANGELOG, сделает коммит и git-тег.
  - `make release-patch` | `make release-minor` | `make release-major` — явный выбор типа релиза.

## Правила версионирования (SemVer)
- patch: совместимые исправления (обычно `fix`, `perf`).
- minor: совместимые фичи (`feat`).
- major: несовместимые изменения. Помечайте `feat!:`/`fix!:` или добавляйте блок `BREAKING CHANGE:` в описание коммита.

Тип релиза определяется по истории коммитов (Conventional Commits). В спорных случаях используйте явные цели `release-*`.

## Подготовка коммитов (Conventional Commits)
Используйте интерактивный помощник:

```bash
make prepare-commit
```

> ⚠️ Известная проблема: при запуске `vendor/bin/conventional-commits prepare` сейчас может воспроизводиться `TypeError` (`Ramsey\ConventionalCommits\Message::__construct(): Argument #2 ($description) must be of type ...`). Это баг зависимости; если встретили сообщение об ошибке, сформируйте заголовок вручную по Conventional Commits и выполните `git commit -m "type(scope): description"` без помощника.

Примеры корректных сообщений:
- `feat(auth): add OAuth2 login for Google`
- `fix(ui): align submit button on mobile`
- `refactor(payment): extract tax calculator`
- `docs: add release workflow guide`
- `feat!: remove legacy v1 endpoints`  # MAJOR из-за `!`

Советы:
- Короткий заголовок ≤ 72 символов; деталь — в теле.
- Для major-доступа используйте `!` в типе или раздел `BREAKING CHANGE:` в теле коммита.

## Обновление CHANGELOG без релиза
Собрать/обновить `CHANGELOG.md` из коммитов (с последнего тега):

```bash
make changelog
# проверьте изменения
git diff CHANGELOG.md
```

Команда меняет файл, но НЕ делает коммит и тег. Используйте для предпросмотра.

## Выпуск релиза
Перед релизом проверьте качество:

```bash
make check
```

Затем выберите один из вариантов.

Вариант A — авто (по истории коммитов, по умолчанию patch):
```bash
make release
```

Вариант B — явно задать тип релиза:
```bash
make release-patch   # только багфиксы
make release-minor   # новые фичи
make release-major   # breaking changes
```

Что сделает команда релиза:
- Сгенерирует секцию релиза в `CHANGELOG.md` (на основе Conventional Commits).
- Определит версию (SemVer) — автоматически или по выбранному типу.
- Сделает коммит с обновлениями.
- Поставит git-тег с номером версии.

После локального релиза не забудьте запушить:
```bash
git push && git push --tags
```

После push тегов обязательно опубликуйте GitHub Release для созданного тега:
```bash
gh release create vX.Y.Z --generate-notes
```

Если release для тега уже существует, команда вернет ошибку `release already exists` — в этом случае проверьте опубликованный релиз:
```bash
gh release view vX.Y.Z
```

## Частые сценарии
- Хотите указать конкретную версию вручную или сделать pre-release (alpha/beta/rc)?
  Используйте напрямую composer-скрипт (см. `composer.json`), например:
  ```bash
  composer run release -- --ver=1.2.3       # конкретная версия
  composer run release -- --rc               # release candidate
  composer run release -- --beta             # beta
  composer run release -- --alpha            # alpha
  ```

- Нужен полный пересбор `CHANGELOG.md` из всей истории:
  ```bash
  vendor/bin/conventional-changelog --history
  ```

## Рекомендации команды
- В PR/коммитах строго соблюдайте Conventional Commits — это влияет на качество CHANGELOG и автоопределение версии.
- Перед релизом всегда запускайте: `make check` (unit/integration тесты, статический анализ, стиль, архитектура).
- **Перед релизом обязательно запускайте E2E тесты:** `make tests-e2e` (подробнее см. [docs/testing/e2e.md](../testing/e2e.md)).
- В описании PR указывайте breaking changes и помечайте их в коммитах (`!`/`BREAKING CHANGE`).

## TL;DR
- Подготовить коммит: `make prepare-commit` → выбрать тип/описание.
- Предпросмотр CHANGELOG: `make changelog` → `git diff`.
- Релиз: `make release-minor` (или иной тип) → `git push && git push --tags` → `gh release create vX.Y.Z --generate-notes`.
