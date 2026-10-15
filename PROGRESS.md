# PROGRESS

Локальная рабочая заметка по проекту (Krayin CRM → bizcrm) под Laragon + MySQL.

## Что сделано (2026-10-10)

**Окружение**
- Laragon, MySQL (root, без пароля), БД `bizcrm`, PHP 8.3.33, Composer 2.10.2.
- Dev-URL: `http://127.0.0.1:8000`.

**Установка Krayin**
- `composer dump-autoload --optimize --no-scripts` — довыполнил шаг, на котором висел `composer install` (46247 классов).
- `php artisan package:discover` — обнаружение пакетов Laravel.
- `.env` создан из `.env.example`, в нём:
  - `APP_NAME=bizcrm`
  - `DB_DATABASE=bizcrm`
  - `APP_URL=http://127.0.0.1:8000`
  - `APP_TIMEZONE=Europe/Moscow`
  - `APP_CURRENCY=KZT`
  - `APP_LOCALE=ru`, `APP_FALLBACK_LOCALE=en`
  - `MAIL_FROM_ADDRESS=laravel@bizcrm.local`
- `php artisan key:generate`.
- `php artisan migrate:fresh --force`.
- `php artisan db:seed --class='Webkul\Installer\Database\Seeders\DatabaseSeeder' --force`.
- `php artisan vendor:publish --provider='Webkul\Core\Providers\CoreServiceProvider' --force`.
- `php artisan storage:link`, `php artisan optimize:clear`.
- Админ создан прямым insert'ом + маркеры установки.

**Учётка админа:**
- email: `admin@example.com` / пароль: `admin123`

## Что сделано (2026-10-15)

### Этап 1 — русский язык (коммит `cf1ebdb0`)
- `.env`: `APP_LOCALE=ru`, `APP_FALLBACK_LOCALE=en`.
- `config/app.php`: в `available_locales` добавлен `ru => Русский` первым пунктом, английский оставлен доступным вторым.
- Переводы созданы в:
  - `lang/ru/{auth,pagination,passwords,validation}.php` — базовые Laravel.
  - `packages/Webkul/Admin/src/Resources/lang/ru/app.php` — главный файл UI (все разделы: дашборд, лиды, контакты, предложения, активности, настройки, конфигурация, воронки, шаблоны писем, атрибуты, роли, пользователи, склады, импорт/экспорт).
  - `packages/Webkul/Installer/src/Resources/lang/ru/app.php` — сид-строки + страница установщика.
  - `packages/Webkul/WebForm/src/Resources/lang/ru/app.php`.
  - `packages/Webkul/GoogleContact/src/Resources/lang/ru/app.php`.
- Все 8 файлов прошли `php -l`; `trans()` из CLI резолвит ru-ключи корректно.
- Остальные пакеты (Email, EmailTemplate, Marketing, Automation, Activity, Contact, Lead, Product, Quote, Tag, User, Warehouse, Attribute, Core, DataGrid, DataTransfer) собственных `lang/` папок не имеют — вся их локализация живёт в `Admin/app.php` и уже переведена.

### Этап 2 — ребрендинг под bizcrm (коммит `d09e996c`)

**Что заменено пользовательски видимое:**
- Логотипы (простые текстовые SVG «bizcrm»):
  - `packages/Webkul/Admin/src/Resources/assets/images/{logo,dark-logo,mobile-light-logo,mobile-dark-logo,favicon}.svg`
  - Собранные Vite-артефакты синхронизированы: `public/admin/build/assets/{logo-Bjh7YAuF,dark-logo-Dnw3cpYj,mobile-light-logo-CjoobCkl,mobile-dark-logo-COPgCide}.svg` и `public/favicon.svg`.
- Favicon ссылка в blade (`index.blade.php`, `anonymous.blade.php`) переключена с `.ico` на `public/favicon.svg` (через `asset()`).
- Удалён блок «Powered by Krayin» (ссылки на krayincrm.com) со страниц `login/forgot-password/reset-password.blade.php`.
- `packages/Webkul/Admin/src/Config/support.php`: массивы `services/resources/community` обнулены, `contact_url` пуст — страница **Помощь** больше не содержит krayincrm-ссылок.
- `packages/Webkul/Admin/src/Config/core_config.php`:
  - default `locale`: `en` → `ru`.
  - default `footer.label`: «Powered by Krayin, an open-source project by Webkul» → `bizcrm`.
- `packages/Webkul/Automation/.../Activity.php:292`: `PRODID:-//Krayincrm//Krayincrm//EN` → `PRODID:-//bizcrm//bizcrm//EN` в iCal.
- `.env`: `APP_NAME='bizcrm'`, `MAIL_FROM_ADDRESS=laravel@bizcrm.local`.

**Primary color — одна переменная для правки:**
- `packages/Webkul/Admin/src/Resources/assets/css/app.css`, строка `:root { --brand-color: #0E90D9; }` в самом верху. Поменяйте значение — поменяются кнопки, ссылки, акценты, иконки. Та же инъекция сделана и в уже собранном `public/admin/build/assets/app-oZri8xg_.css`, чтобы работало без пересборки Vite.
- Blade-слои (`index.blade.php`, `anonymous.blade.php`) инлайновый `:root { --brand-color: … }` теперь пишут только если админ задал custom-значение в UI; дефолт берётся из CSS.

**НЕ тронуто (сознательно):**
- `LICENSE`, `composer.json`, PHP-неймспейсы (`Webkul\...`, `Krayin\...`).
- Установщик `packages/Webkul/Installer/.../views/installer/index.blade.php` — установка завершена, пользователь эту страницу не видит.
- CLI-команда `krayin-crm:version` (не UI; внешние вызовы не ломаем).

**Чтобы подложить настоящий логотип позже:**
- Через админку: Настройки → Конфигурация → Основное → Admin Logo (поля `logo_image` и `favicon_image`) — приоритет у них, SVG-заглушки спрячутся.
- Или напрямую заменить файлы:
  - исходники: `packages/Webkul/Admin/src/Resources/assets/images/{logo,dark-logo,mobile-light-logo,mobile-dark-logo}.svg`, `packages/Webkul/Admin/src/Resources/assets/images/favicon.svg` (+ `public/favicon.svg`).
  - билд (чтобы без пересборки Vite): `public/admin/build/assets/{logo-Bjh7YAuF,dark-logo-Dnw3cpYj,mobile-light-logo-CjoobCkl,mobile-dark-logo-COPgCide}.svg`.
  - Если решите пересобрать Vite (`npm install && npm run build` в `packages/Webkul/Admin`), хеши в именах перегенерируются — трогать прямые файлы в `public/admin/build/assets` уже не нужно, но учтите, что в `manifest.json` появится `favicon.svg` → можно будет вернуть blade на `vite()->asset('images/favicon.svg')`.

### Этап 3 — скорость локально (в процессе)

**Apache vhost `bizcrm.test` создан:**
- Файл: `C:\laragon\etc\apache2\sites-enabled\auto.bizcrm.test.conf`.
- DocumentRoot: `C:/Users/Asus/Desktop/Bitirix24/bizcrm/public`.

**Чтобы `http://bizcrm.test` открылся, нужно (разово, нужны права администратора):**
1. Добавить в `C:\Windows\System32\drivers\etc\hosts` строку:
   ```
   127.0.0.1 bizcrm.test
   ```
   Из-под обычного пользователя файл не редактируется. Варианты:
   - Открыть «Блокнот» через правый клик → «Запуск от имени администратора», в нём открыть hosts, добавить строку, сохранить.
   - Или в меню Laragon правой кнопкой: *«Terminal → Run as administrator»*, затем:
     ```powershell
     Add-Content C:\Windows\System32\drivers\etc\hosts "`n127.0.0.1 bizcrm.test"
     ```
   - Либо Laragon → меню *Apache → sites-enabled → …* сам иногда прописывает hosts, если запущен администратором.
2. В меню Laragon: Reload Apache (значок перезагрузки).
3. Поднять MySQL в Laragon (сейчас остановлен — виден в логе `storage/logs/laravel.log` отказ подключения к 127.0.0.1:3306).
4. Открыть `http://bizcrm.test` → должен открыться `/admin/login`.

**Opcache — выключен (как включить):**
- Текущее состояние: расширение `opcache` в `C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.ini` закомментировано (`;zend_extension=opcache`), включая весь блок `[opcache]`.
- Чтобы включить:
  1. Открыть `C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.ini` (как администратор).
  2. Снять `;` перед:
     ```
     zend_extension=opcache
     opcache.enable=1
     opcache.enable_cli=1
     opcache.memory_consumption=128
     opcache.interned_strings_buffer=16
     opcache.max_accelerated_files=20000
     opcache.validate_timestamps=1
     opcache.revalidate_freq=2
     opcache.save_comments=1
     ```
     (значения `memory_consumption=128`, `interned_strings_buffer=16`, `max_accelerated_files=20000` — комфортный профиль для Laravel-проекта такого размера; дефолты в ini-файле меньше, можно оставить и дефолты.)
  3. Laragon → Reload Apache (или рестарт).
  4. Проверить: `& 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe' -r "var_dump(opcache_get_status(false));"` → должен показать `opcache_enabled => true`.
- Короткий путь через GUI: в трее Laragon правой кнопкой → **PHP → Extensions → opcache** (поставить галку), затем Reload Apache.

## Проблемы и как решены (архив с прошлой сессии)

- **composer install висел на «Generating optimized autoload files»** — вендоры уже были скачаны, но `vendor/autoload.php` отсутствовал. `composer dump-autoload --optimize --no-scripts` отдельно, затем `package:discover` вручную.
- **Интерактивный инсталлятор `krayin-crm:install`** просит ввод через `Laravel\Prompts`. Обход: шаги по отдельности (миграции, сиды, publish, storage:link, insert админа + маркеры).
- **Сиды через собственный скрипт падали** на `$this->command->writeln()` — вне контекста Artisan `Seeder::$command` null. Решение: `php artisan db:seed --class=...`.
- **419 Page Expired при логине** — старые cookies после `key:generate`. Решение: очистить cookies для `127.0.0.1:8000` / инкогнито.

## Как запустить заново

```powershell
# 1. Поднять Laragon:
#    - Apache (с vhost auto.bizcrm.test.conf),
#    - MySQL (БД bizcrm должна существовать).

cd C:\Users\Asus\Desktop\Bitirix24\bizcrm

# 2. Если .env потерян — восстановить из .env.example и прописать:
#    APP_NAME=bizcrm, DB_DATABASE=bizcrm, APP_URL=http://bizcrm.test,
#    APP_TIMEZONE=Europe/Moscow, APP_CURRENCY=KZT,
#    APP_LOCALE=ru, APP_FALLBACK_LOCALE=en,
#    MAIL_FROM_ADDRESS=laravel@bizcrm.local
#    затем:  & 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe' artisan key:generate

# 3. Зависимости (если vendor пуст):
composer install
```

Открыть: <http://bizcrm.test> → редиректит на `/admin/login`. Войти как `admin@example.com` / `admin123`.

Полная переустановка БД (сотрёт данные):
```powershell
php artisan migrate:fresh --force
php artisan db:seed --class='Webkul\Installer\Database\Seeders\DatabaseSeeder' --force
php artisan vendor:publish --provider='Webkul\Core\Providers\CoreServiceProvider' --force
php artisan storage:link
php artisan optimize:clear
```

## Что ещё осталось

**Для меня (разработчик):**
- Smoke-тест `http://bizcrm.test` после включения MySQL и hosts — глазами пройти по дашборду, лидам, контактам, предложениям, активностям, настройкам. Если найдутся непереведённые ключи — сообщите, добавлю.
- Пересобрать Vite (`npm install && npm run build` в `packages/Webkul/Admin`) — опционально, когда нужно будет единое поведение «только исходники». Сейчас исходники и билд в sync вручную.

**Для вас (разово, нужны права администратора):**
- Прописать `127.0.0.1 bizcrm.test` в hosts (см. раздел Этапа 3 выше).
- Включить opcache в `php.ini` (см. раздел Этапа 3 выше).
- Поднять MySQL в Laragon.

**На будущее:**
- Заменить SVG-заглушки «bizcrm» на реальный логотип (пути — в разделе Этапа 2 выше).
- Если решите убрать помощь из сайдбара совсем — скажите, уберу route/menu.
