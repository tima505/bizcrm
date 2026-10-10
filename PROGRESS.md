# PROGRESS

Локальная рабочая заметка по проекту (Krayin CRM → bizcrm) под Laragon + MySQL.

## Что сделано (2026-10-10)

**Окружение**
- Laragon, MySQL (root, без пароля), БД `bizcrm`, PHP 8.3.33, Composer 2.10.2.
- Dev-URL: `http://127.0.0.1:8000`.

**Установка Krayin**
- `composer dump-autoload --optimize --no-scripts` — довыполнил шаг, на котором висел `composer install` (46247 классов).
- `php artisan package:discover` — обнаружение пакетов Laravel.
- `.env` создан из `.env.example`, в нём изменено:
  - `DB_DATABASE=bizcrm`
  - `APP_URL=http://127.0.0.1:8000`
  - `APP_TIMEZONE=Europe/Moscow`
  - `APP_CURRENCY=KZT`
  - (локаль оставлена `APP_LOCALE=en`)
- `php artisan key:generate` — выставлен `APP_KEY`.
- `php artisan migrate:fresh --force` — миграции применены.
- `php artisan db:seed --class='Webkul\Installer\Database\Seeders\DatabaseSeeder' --force` — базовые сиды (атрибуты, страны, email-шаблоны, lead-пайплайны, роли, воркфлоу).
- `php artisan vendor:publish --provider='Webkul\Core\Providers\CoreServiceProvider' --force` — опубликовал `config/concord.php`, `config/cors.php`, `config/sanctum.php`.
- `php artisan storage:link`, `php artisan optimize:clear`.
- Админ создан прямым insert'ом в `users` + установлены маркеры установки
  (`storage/installed`, `DatabaseManager::markInstallationCompleted/clearInstallationInProgress`).

**Учётка админа (дефолт инсталлятора):**
- email: `admin@example.com`
- пароль: `admin123`

## Проблемы и как решены

- **composer install висел на «Generating optimized autoload files»** —
  вендоры уже были скачаны, но `vendor/autoload.php` отсутствовал. Запустил
  `composer dump-autoload --optimize --no-scripts` отдельно (у него был
  скрипт `package:discover` в post-autoload-dump, из-за которого исходный
  install подвисал). После успеха вручную выполнил `package:discover`.

- **Интерактивный инсталлятор `krayin-crm:install`** просит ввод через
  `Laravel\Prompts`, что плохо под неинтерактивным shell'ом. Обошёл: не
  запускал команду, а выполнил её шаги по отдельности (миграции, сиды,
  publish, storage:link, optimize:clear, insert админа + маркеры).

- **Сиды через собственный скрипт падали** на `$this->command->writeln()`.
  Причина: без контекста Artisan-команды `Seeder::$command` остаётся null.
  Решение: запуск через `php artisan db:seed --class=...` — тогда Laravel
  сам прокидывает command context.

- **419 Page Expired при логине в браузере** — после `key:generate` в
  браузере остались куки `XSRF-TOKEN`/`krayin_crm_session` от прежней
  сессии. CLI-симуляция логина проходит в 302 → `/admin/dashboard`.
  Решение: очистить куки для `127.0.0.1:8000` или открыть инкогнито.
  Серверные session-файлы на диске я также почистил.

## Как запустить заново

```powershell
# 1. Поднять Laragon (Apache можно не трогать — используем artisan serve)
#    Убедиться, что MySQL запущен и БД bizcrm существует.

cd C:\Users\Asus\Desktop\Bitirix24\bizcrm

# 2. Если .env потерян — восстановить из .env.example и прописать:
#    DB_DATABASE=bizcrm, APP_URL=http://127.0.0.1:8000,
#    APP_TIMEZONE=Europe/Moscow, APP_CURRENCY=KZT
#    затем:  php artisan key:generate

# 3. Зависимости (если vendor пуст):
composer install

# 4. Запуск dev-сервера:
php artisan serve --host=127.0.0.1 --port=8000
```

Открыть: <http://127.0.0.1:8000> → редиректит на `/admin/login`.
Войти как `admin@example.com` / `admin123`.

Полная переустановка БД (сотрёт данные):
```powershell
php artisan migrate:fresh --force
php artisan db:seed --class='Webkul\Installer\Database\Seeders\DatabaseSeeder' --force
php artisan vendor:publish --provider='Webkul\Core\Providers\CoreServiceProvider' --force
php artisan storage:link
php artisan optimize:clear
```

## Следующий шаг (TODO)

1. **Русская локаль интерфейса** — переключить `APP_LOCALE=ru`, проверить
   покрытие переводов в `lang/` и в `packages/Webkul/*/src/Resources/lang/`.
   Там, где `ru` отсутствует — добавить файлы переводов.
2. **Полный ребрендинг (bizcrm)**:
   - Название приложения во всех видимых местах (title, header,
     email-шаблоны, страница логина, meta-теги) — заменить `Krayin` на
     наше имя.
   - Логотип — заменить SVG/PNG в
     `packages/Webkul/Admin/src/Resources/assets/images/` и
     `public/vendor/webkul/ui/assets/images/` (после publish).
   - Цветовая схема — tailwind-конфиг / переменные темы в
     `packages/Webkul/Admin/src/Resources/assets/css`.
   - Убрать упоминания «Krayin» из UI, писем, футеров, favicon,
     config-файлов (кроме composer/namespace — там трогать нельзя).
   - **`LICENSE` не трогать.**
