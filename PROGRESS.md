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

## Что сделано (2026-10-17) — WhatsApp через Baileys

Канальный приём и отправка WhatsApp-сообщений внутри CRM. Структура универсальная (одна таблица `messaging_messages` с полем `channel`), чтобы позже легко добавить Telegram/Instagram.

### Этап 1 — Node-мост (коммит `2e35464a`)

Папка `whatsapp-service/` — отдельный Node-сервис на Baileys 6.7, Express, pino, qrcode-terminal. QR печатается в терминал, авторизация хранится в `whatsapp-service/auth/` (в .gitignore).

HTTP-эндпоинты (слушает `127.0.0.1:3000`):
- `GET /health` — проверка связи с WhatsApp (`state: connected|disconnected`).
- `POST /send` — отправка текста. Защищён заголовком `X-Whatsapp-Secret`.
- При входящем — POST на `LARAVEL_INCOMING_URL` с тем же секретом.

Конфиг в `whatsapp-service/.env` (gitignore), шаблон — в `whatsapp-service/.env.example`.

### Этап 2 — Laravel-пакет (коммит `347de713`)

Пакет `packages/Webkul/Whatsapp/`:
- Таблица `messaging_messages` (`channel`, `chat_id`, `from_number`, `direction`, `content`, FK на `persons`/`leads`).
- `/api/whatsapp/incoming` — приём от Node. Автосоздание контакта и лида для нового номера (source «WhatsApp», pipeline/stage по умолчанию). Для существующего контакта берётся его последний открытый лид.
- `/admin/messages` — список чатов слева, переписка справа, поле ответа. Polling каждые 3с (без перезагрузки страницы).
- `/admin/messages/send` POST — отправка через Node.
- Пункт в сайдбаре «Сообщения» с иконкой `icon-mail`, ACL `messages.view` / `messages.send`.
- Переводы: `ru` (по умолчанию) и `en`.

Регистрация:
- `bootstrap/providers.php` — `WhatsappServiceProvider`.
- `config/concord.php` — `WhatsappModuleServiceProvider`.
- `composer.json` — PSR-4 `Webkul\Whatsapp\`.

Shared secret хранится в двух местах — `whatsapp-service/.env` и корневом `.env` (`WHATSAPP_SECRET`). Должны совпадать байт в байт.

### Этап 3 — как запустить и проверить (локально)

**Разово (если ставите на чистой машине):**
```powershell
cd whatsapp-service
npm install     # уже сделано — пропустить, если node_modules на месте
cd ..
```

**Каждый запуск — два окна PowerShell:**

Окно 1 — Laravel (нужен поднятый MySQL в Laragon):
```powershell
cd C:\Users\Asus\Desktop\Bitirix24\bizcrm
& 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe' artisan serve
# слушает http://127.0.0.1:8000
```

Окно 2 — Node WhatsApp мост:
```powershell
cd C:\Users\Asus\Desktop\Bitirix24\bizcrm\whatsapp-service
npm start
# первый запуск — в терминале появится QR-код
```

**Привязка WhatsApp (разово):**
1. На телефоне: WhatsApp → Настройки → Связанные устройства → Привязать устройство.
2. Отсканировать QR из окна Node. После успешной привязки в терминале появится `[wa] connected as 7XXXXXXXXXX@s.whatsapp.net`.
3. Папка `whatsapp-service/auth/` сохранит сессию — следующий запуск уже без QR.

**Проверка:**
1. Открыть `http://127.0.0.1:8000/admin/login` → войти как `admin@example.com` / `admin123`.
2. В левом сайдбаре — пункт «Сообщения». Пока пусто.
3. Со **второго** телефона отправить сообщение на привязанный номер.
4. Через 1–3 секунды чат появится в разделе «Сообщения» слева. Параллельно в разделе «Контакты» будет создан контакт, в «Лиды» — лид (источник «WhatsApp»).
5. Открыть чат → ввести ответ → «Отправить». Сообщение придёт в WhatsApp на телефон.

**Диагностика:**
- `GET http://127.0.0.1:3000/health` → `{"ok":true,"state":"connected"}` означает, что мост жив и привязан.
- Если `state: disconnected` — проверьте, что в Node-терминале нет ошибок, при необходимости удалите `whatsapp-service/auth/` и перескандируйте QR.
- Логи Laravel: `storage/logs/laravel.log`.
- Если `/api/whatsapp/incoming` отвечает 401 — секреты в `whatsapp-service/.env` и корневом `.env` не совпадают.

### Фикс кликов по чату после Vue-mount (2026-10-24)

После коммита `c89babed` клик по карточке чата в `/admin/messages`
ничего не делал: заголовок справа не менялся, поле ответа и кнопка
«Отправить» оставались `disabled`. Ошибок в консоли при этом не было.

Причина: админский layout монтирует Vue через `app.mount("#app")` на
`window.load`, и Vue 3 **заменяет** все дочерние узлы `#app` на
свежерендеренные. IIFE в `whatsapp::admin.index` выполнялся раньше —
во время парсинга `@stack('scripts')` — и кэшировал ссылки через
`getElementById` (`headerEl`, `textInput`, `sendBtn`, `chatListEl`,
`threadEl`, `sendForm`). После монтирования Vue эти ссылки указывают
на detached-узлы. Click-обработчик по чату срабатывал (его успевал
переподцепить `bindChatItems()` на следующий poll через
`document.querySelectorAll`), но внутри `selectChat()` все
`headerEl.textContent = …`, `textInput.disabled = false` писали в
orphan-ноды — живой DOM не менялся. Submit вообще не стрелял, т.к.
`sendForm.addEventListener('submit', …)` висел на detached-форме.

Фикс в `packages/Webkul/Whatsapp/src/Resources/views/admin/index.blade.php`:
- вместо кэша — ленивый `$ = (id) => document.getElementById(id)` в
  каждой функции,
- делегирование `click`/`submit` с `document` (`closest('.wa-chat-item')`
  / `closest('#wa-send-form')`), чтобы хэндлеры переживали ре-рендер,
- старт polling'а перенесён в `window.load` (чтобы первый проход шёл
  уже по live-DOM Vue).

Проверено headless-Chrome'ом через CDP: клик по чату меняет заголовок,
разблокирует input/кнопку, подгружает тред; submit отправляет
сообщение в WhatsApp и обновляет список бабблов. `tools/smoke.ps1` —
все страницы 200 OK, `thread` endpoint отвечает.

### Фикс поддержки `@lid`-чатов (коммит `9130b70a`)

WhatsApp для части отправителей присылает `remoteJid` вида `...@lid` (LID privacy). В первой версии Node приводил такой JID к `@s.whatsapp.net`, из-за чего ответы не доходили, а один сбой расшифровки клал сервис.

- Node хранит `remoteJid` как есть и отдаёт его в Laravel как `chat_id`. Телефон берётся из `key.senderPn`, если WhatsApp его раскрыл; иначе `from_number = "lid:<n>"`.
- Отправка (`/send`): если прилетел JID — шлётся как есть, цифровой номер оборачивается в `@s.whatsapp.net`.
- `try/catch` вокруг каждой итерации `messages.upsert` + `uncaughtException`/`unhandledRejection` — одна битая расшифровка больше не роняет мост.
- Laravel матчит контакт сначала по стабильному `chat_id`, затем по телефону. В `contact_numbers` хранятся оба значения (label=whatsapp для JID, label=work для телефона).
- `LeadRepository::create` требует `entity_type: 'leads'` — без него падал `AttributeValueRepository::save` с «Undefined array key».
- Исходящие (`/admin/messages/send`) всегда маршрутизируются по `chat_id`, а не по display-номеру.

## Что сделано (2026-10-22) — ускорение админки

Админка грузилась ~1.1-1.3 с на страницу. Все замеры — `tools/measure.ps1`, 5 прогонов на страницу через authenticated HTTP на `127.0.0.1:8000`.

**Было (baseline)**

| страница   | avg_ms | min_ms | max_ms |
|------------|--------|--------|--------|
| dashboard  | 1233   | 1210   | 1265   |
| leads      | 1316   | 1178   | 1516   |
| contacts   | 1298   | 1191   | 1468   |
| messages   | 1108   | 1004   | 1297   |

**Диагностика (`tools/profile_queries.php /admin/<path>`):**
- SQL всего 20-40 мс на страницу — БД не узкое место.
- Остальные ~500-700 мс — чистый PHP (парсинг файлов, рендер Blade, Vue setup).
- 15x `attribute_options` + 8-10x `attributes` + 9-15x `core_config` — повторные запросы в `quick-creation.blade.php` (хедер с модалкой «быстро создать» на каждой странице). Суммарно дёшево (~25 мс), оставлено как есть.
- `barryvdh/laravel-debugbar` активен по умолчанию из-за `APP_DEBUG=true` — отжирал ~550 мс на запрос и +250 KB HTML.
- opcache выключен (`;opcache.*` закомментированы в `php.ini`) — ~400 мс парсинга PHP на каждый запрос.

**Что сделано (без правки БД, по согласованию):**
1. **Debugbar выключен** через `DEBUGBAR_ENABLED=false` в `.env`. Сам пакет оставлен в composer. `APP_DEBUG=true` сохранён.
2. **`php artisan optimize`** → `config:cache`, `route:cache`, `view:cache`, `event:cache`. Если правите `.env` или роуты — `php artisan optimize:clear`, потом снова `optimize`.
3. **Prerender на hover** через Speculation Rules API (Chrome/Edge). В `packages/Webkul/Admin/src/Resources/views/components/layouts/index.blade.php` добавлен `<script type="speculationrules">` с `eagerness: moderate`. Браузер прогревает `/admin/*` GET-страницы (кроме logout/create/edit/delete/export) на hover; клик по пункту меню переключается на уже отрисованную страницу мгновенно. Firefox/Safari этот блок игнорируют.
4. **Turbo (Hotwire) не внедрялся** — архитектура Krayin (один `createApp` на `#app`, который оборачивает весь body, монтируется на `window.load`) требует демонтажа/монтажа Vue на каждом переходе. Это ломает TinyMCE, Flatpickr, polling в `/admin/messages` и формы. Пререндер даёт ту же perceived-скорость с нулевым риском.

**Стало**

| страница   | avg_ms | min_ms | max_ms | Δ vs baseline |
|------------|--------|--------|--------|---------------|
| dashboard  | 677    | 591    | 901    | **−45 %**     |
| leads      | 774    | 677    | 998    | **−41 %**     |
| contacts   | 816    | 700    | 1146   | **−37 %**     |
| messages   | 579    | 508    | 788    | **−48 %**     |

**Opcache включён (2026-10-22):**

В `C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.ini` раскомментированы и выставлены:
```ini
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=1
opcache.revalidate_freq=2
opcache.save_comments=1
```

Проверка:
```powershell
& 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe' -r "var_dump(opcache_get_status(false)['opcache_enabled'] ?? false);"
```

**Эффект opcache (поверх всего остального):**

| страница   | до opcache | opcache ON (warm) | Δ    |
|------------|-----------:|------------------:|-----:|
| dashboard  | 677        | 628               | −49  |
| leads      | 774        | 644               | −130 |
| contacts   | 816        | 713               | −103 |
| messages   | 579        | 490               | −89  |

Прибавка скромнее теоретических «−400 мс», потому что `config/route/view/event cache` уже сняли основную часть парсинг-работы, а PHP built-in server на NTFS в принципе хуже раскрывает opcache, чем nginx+php-fpm на Linux.

**Итого от baseline:**

| страница   | было | стало | Δ         |
|------------|-----:|------:|----------:|
| dashboard  | 1233 | 628   | **−49 %** |
| leads      | 1316 | 644   | **−51 %** |
| contacts   | 1298 | 713   | **−45 %** |
| messages   | 1108 | 490   | **−56 %** |

**Про `APP_DEBUG=true`**: отдельно от debugbar он стоит ~0-30 мс на страницу (замерял — в пределах шума). Можно оставить как есть.

**Чем мерить:**

```powershell
# Серверная сторона, 5 прогонов на страницу:
powershell -NoProfile -File tools/measure.ps1 -Label 'now' -Runs 5

# Что в HTML (debugbar, Vite dev, число ассетов):
powershell -NoProfile -File tools/inspect.ps1 -Path '/admin/dashboard'

# Профиль SQL на конкретной странице:
php tools/profile_queries.php /admin/leads
```

**Если что-то сломалось**: откатить speculationrules — удалить блок `<script type="speculationrules">` из `components/layouts/index.blade.php` и `view:cache`. Вернуть debugbar — убрать строку `DEBUGBAR_ENABLED=false` из `.env`.

## Что сделано (2026-10-26) — доработка дизайна админки

Четыре этапа визуальной полировки после замеров на реальном использовании.
Все изменения проверяются `tools/smoke.ps1` (HTTP + HTML sanity) и
`tools/cdp-smoke.ps1` (Chrome DevTools Protocol: клики, видимость панелей,
мобильный breakpoint).

### Этап 1 — страница «Сообщения» в стиле WhatsApp Web (коммит `72e5ba8c`)

Коммит из прошлой сессии: редизайн `/admin/messages`. Список чатов 320px
слева с аватаркой-инициалом, временем, превью и бейджем непрочитанных,
переписка справа с баблами (исходящие — брендовым цветом, входящие —
белые), разделители дат («Сегодня», «Вчера», «3 июня»). Composer с
автоувеличением высоты, Enter — отправить, Shift+Enter — перенос. Polling
3с через JSON-эндпойнт `/admin/messages/thread`.

### Этап 2 — порядок меню, счётчик непрочитанных, логотип (коммит `991587bd`)

**Порядок сайдбара**: Главная → Сообщения → Лиды → Контакты →
Активности → Коммерческие предложения → Продукты → Почта → Настройки.
«Конфигурация» и «Помощь и ресурсы» вынесены верхнего уровня и теперь
живут внутри Настроек как подраздел «Система» (`settings.system`) с
карточками `settings.system.configuration` и `settings.system.help`.
Правка в `packages/Webkul/Admin/src/Config/menu.php` + `Whatsapp/src/Config/menu.php`.

**Счётчик непрочитанных WhatsApp**. Красный пилюля-бейдж у «Сообщений»
в сайдбаре. Источник — view composer в
`WhatsappServiceProvider::boot()`, который привязан к обоим сайдбарам
(`admin::components.layouts.sidebar.desktop.index`,
`admin::components.layouts.sidebar.mobile.index`). Считает
`messaging_messages` где `direction=incoming AND read_at IS NULL`. Авто-сброс
происходит, когда юзер открывает чат — `MessageController::markChatAsRead()`
уже проставлял `read_at` в `thread` эндпоинте.

**Фикс обрезанного логотипа**. Старые SVG были 140×40 с `font-size=26` и
`y=28` — глифы «b»/«m» упирались в край viewBox, визуально обрезаясь.
Новые SVG: viewBox 156×40, брендовая иконка-квадрат слева + wordmark
«bizcrm» с `font-size=20`, `y=27` — запас по вертикали ~8px сверху и снизу.
Обновлены и исходники (`packages/Webkul/Admin/src/Resources/assets/images/`),
и собранные Vite-артефакты (`public/admin/build/assets/*logo*.svg`) —
пересборка Vite не нужна.

### Этап 3 — дружественный пустой экран (коммит `0aeae0cf`)

В общем датагриде (`packages/Webkul/Admin/src/Resources/views/components/datagrid/table.blade.php`)
плоская строчка «No Records Available.» заменена на блок: кружок-иконка +
заголовок «Записей пока нет» + подсказка «Создайте первую запись с помощью
кнопки выше или измените фильтры». Применяется ко всем страницам со
списками (лиды в режиме table, контакты, активности, продукты, предложения,
подстраницы настроек). Канбан лидов уже имел нормальный пустой экран с
кнопкой «Создать лид» — не трогал.

### Этап 4 — мобильная адаптация страницы «Сообщения» (коммит `43654708`)

До правки `/admin/messages` на 390px-телефоне рендерился как 320px
список + 70px треда. Переделал на классический mobile-first флоу
WhatsApp Web:
- `<md` (<768px): показан только список (aside на всю ширину), секция
  треда скрыта.
- По клику на чат → aside скрывается, тред раскрывается, в его
  заголовке появляется стрелка `#wa-back`.
- Клик по стрелке → возврат к списку. Отключается input/кнопка
  «Отправить», очищается состояние активного чата.
- `>=md`: обе панели видны всегда (как было).
- `window.resize` переключает отображение, если юзер меняет ширину
  окна через md-порог.

Проверка — `tools/cdp-smoke.ps1`. Запускается после запуска headless
Chrome с `--remote-debugging-port=9222`. Проходит сценарий: login →
dashboard → сайдбар-порядок → логотип → `/admin/messages` → клик по
чату → тред + enabled input → эмуляция 390×844 → список-видим →
клик → тред-видим + back-виден → клик back → список-видим.

### Проверочные инструменты

**`tools/smoke.ps1`** — HTTP smoke. Логинится, тащит `/admin/*` страницы,
проверяет размер (все 200 OK), JSON `/admin/messages/thread`, порядок
меню по css-классам иконок, отсутствие `icon-help`/`icon-configuration`
в сайдбаре (должны быть внутри Настроек), вшита бейджа непрочитанных
(ищет `bg-red-500` в блоке сайдбара), viewBox логотипа.

**`tools/cdp-smoke.ps1`** — e2e через Chrome DevTools Protocol. Нужен
живой headless Chrome на `:9222`. Проверяет реальный DOM после
mount'а Vue — особенно важно для `/admin/messages`, где админский
layout монтирует Vue на `window.load` и ссылки на DOM через
`getElementById` ломаются (см. раздел «Фикс кликов по чату» выше).

### Что осталось на будущее

- Пустой экран с кнопкой «Добавить первый…» в датагриде умеет только
  общую подсказку. Если понадобится явная кнопка — добавить в датагрид
  prop'ы `empty-create-url` + `empty-create-label` и прокинуть из каждой
  index-страницы.
- «Простые русские тексты вместо машинного перевода» — RU-переводы в
  `admin/ru/app.php` в целом в порядке (точечно сделаны руками в этапе 1
  русификации). Если наткнётесь на корявую строку — скажите, починю.
- Подсказки у непонятных полей форм (лид/контакт) — поля в Krayin
  рендерятся из таблицы `attributes`. Чтобы добавить подсказку, нужно
  в админке в разделе Атрибутов задать поле `help_text`. Можно сид'ом
  проставить для всех system-атрибутов.
- Пересборка Vite (`npm install && npm run build` в
  `packages/Webkul/Admin`) — пока не делал, трогаю исходники + hashed
  артефакты параллельно.

## Статус на 2026-10-17 (конец дня)

**Работает:** Node мост привязан, входящие (включая `@lid`) доходят в CRM, автоматически создаётся контакт + лид, список чатов виден в `/admin/messages`.

**Осталось на следующий раз:**
- **Проверить отправку ответа** из CRM в WhatsApp на живом подключении (код правлен, но e2e пока не прогоняли).
- **Карточка чата в списке**: сейчас вместо имени/номера и превью рендерятся точки (вероятно, Blade экранирует значения или они приходят в неожиданном формате). Проверить `chatList()` в `MessageController` и шаблон в `index.blade.php`.
- **Расширить колонку чатов до ~300px** (сейчас 320px по макету, но визуально узко — проверить реальную ширину после рендера и либо увеличить до 320–340, либо убрать внутренний паддинг).

**Что НЕ сделано (сознательно, вне MVP):**
- Картинки/медиа — только текст.
- Групповые чаты — приходят, но без корректной привязки к контакту.
- Статусы доставки/прочтения — не отслеживаются.
- Отдельный worker/queue — отправка синхронная внутри HTTP-запроса.
- WebSocket — вместо него polling 3с.

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

## Что сделано (2026-10-26, вторая половина дня) — русификация сид-данных + стабилизация WhatsApp

### WhatsApp-мост: устойчивость сессии + файловый лог (коммит `ffb4b1c9`)

После рескана QR на новое устройство входящие перестали доходить. Причины
нашлись две:

1. **Старый `messages.upsert` фильтровал только `type: 'notify'`** и
   отбрасывал `type: 'append'` — а именно append-ом Baileys шлёт то, что
   пришло, пока сокет был оторван. Теперь принимаются оба типа.
2. **Нет колбэка `getMessage`** — при `Message Retry Receipt` от
   контрагента Baileys не мог перезашифровать сообщение, и оно оставалось
   недоставленным. Добавлен in-memory LRU-кэш на 500 сообщений, который
   этот колбэк и возвращает.

Дополнительно в `whatsapp-service/index.js`:
- флаг `reconnecting` против параллельных стартов,
- `cleanupSocket()` снимает слушатели + закрывает ws у старого сокета (убирает
  утечку event-handler'ов при серии `badSession` code=500),
- `DisconnectReason.restartRequired` (515) → мгновенный рестарт за 100мс,
  остальные — 3с с экспоненциальным backoff до 30с,
- `markOnlineOnConnect: false` — наш статус в WA не прыгает в online при
  каждом реконнекте,
- детектор залипшего `badSession`: 5 code=500 подряд → громкий stderr
  «удали auth/ и рескан QR»,
- логи pino через `pino.multistream` в stdout **и**
  `whatsapp-service/logs/wa.log` (gitignore). Свои `console.log`/`.error`
  заменены на `log`/`logErr` с ISO-таймстемпами, папка `logs/` создаётся
  при старте, `logs/` и `auth-backup-*/` добавлены в `.gitignore`
  сервиса.
- `/health` отдаёт `reconnectAttempts`, `cachedMessages`,
  `consecutiveBadSession` — удобный пульс без копания в логе.

На Laravel-стороне в `IncomingController` добавлен **дедуп по
`(channel, external_id)`**: Baileys со стороны `append`-реплея может
прислать то же сообщение второй раз, второй POST теперь отвечает
`{ok: true, deduped: true}` без второй вставки в `messaging_messages`.

Перепривязка подтверждена: старый `auth/` → `auth-backup-20261026-223253/`,
новый QR отсканирован, после рескана первые два реальных сообщения
со второго телефона легли в БД (`messaging_messages` id=10, 11),
`wa.log` поймал полный путь: 515 restart → `connected as` → `init queries`
timeout → `/admin/messages/thread` отдаёт свежие строки.

### Фикс bare `/admin` под `php artisan serve` (коммит `90e1ac8e`)

LAN-доступ `http://10.161.221.210:8000/admin` с телефона отдавал нативный
PHP-404 «The requested resource /admin was not found on this server». До
Laravel запрос не доходил.

Причина: `vendor/.../server.php` на запрос `/admin` делает
`file_exists('public/admin')` → true (это директория с Vite-ассетами),
возвращает `false`, и встроенный PHP-сервер пытается листить каталог
без index-файла. 404.

Фикс: кладём свой `server.php` в корень проекта — `ServeCommand` его
подхватывает приоритетнее дефолтного. В нашем роутере одно отличие:
`is_file()` вместо `file_exists()`. Реальные ассеты по-прежнему
отдаются напрямую, а `/admin` уходит в Laravel и редиректится на
`/admin/login` штатным middleware.

### Русификация сид-данных (коммит `6c0a3605`)

Миграция `packages/Webkul/Installer/src/Database/Migrations/2026_10_26_000001_translate_seeded_strings_to_russian.php`
переписывает всё, что сидеры Krayin оставили на английском. Поиск
по стабильным колонкам, down() симметрично откатывает:

| Таблица | Правка |
|---|---|
| `lead_pipelines` | «Default Pipeline» → «Основная воронка» |
| `lead_pipeline_stages` | 6 стадий по `code`: new→«Новый», follow-up→«Связались», prospect→«Потенциальный», negotiation→«Переговоры», won→«Успешно», lost→«Провал» |
| `lead_types` | «New Business»→«Новый клиент», «Existing Business»→«Повторный клиент» |
| `lead_sources` | 5 строк (Email, Web→«Сайт», Web Form→«Веб-форма», Phone→«Телефон», Direct→«Прямой контакт»). «WhatsApp» не трогаем. |
| `users` | `name` для `id=1 AND email='admin@example.com'`: «Example» → «Администратор» |
| `roles` | `id=1`: «Administrator» → «Администратор», description → «Роль администратора» |
| `attributes` | 42 строки по `(entity_type, code)` — Title/Description/Source/Type/Sales Owner/… для leads/persons/organizations/products/quotes/warehouses |
| `email_templates` | id=1,2: `name` и `subject` — «Активность создана», «Активность изменена». HTML-тело оставлено на отдельную итерацию (там внутри `<td>Title</td>`). |

Для **новых инсталляций** починены сами сиды:
- `installer/lang/ru/app.php`: формулировки под твой словарь (воронка
  «Основная», стадии как в таблице выше, type «Повторный клиент»),
  плюс добавлен ключ `seeders.user.name` в обеих локалях (EN и RU).
- `UserSeeder.php` больше не хардкодит «Example Admin» — читает
  `trans('installer::app.seeders.user.name', ..., $defaultLocale)`.

Проверено byte-level через HTTP: `/admin/leads/get?pipeline_id=1` отдаёт
6 стадий русские, `/admin/settings/users` XHR → имя «Администратор»,
`/admin/settings/roles` XHR → «Администратор» / «Роль администратора»,
`/admin/leads/create` HTML содержит все переведённые опции Source/Type
и русские метки атрибутов. `tools/smoke.ps1` — 16/16 ✓.

Пре-миграционный бэкап: `storage/backups/pre-i18n-20261026-225327.sql`
(gitignore). Откат — `php artisan migrate:rollback --step=1` крутит
`down()`, либо `mysql -uroot bizcrm < storage/backups/pre-i18n-...sql`.

### Старые заголовки WhatsApp-лидов переписаны

Миграция `packages/Webkul/Whatsapp/src/Database/Migrations/2026_10_26_000002_rename_old_whatsapp_lead_titles.php`
находит все `leads` с `title LIKE 'WhatsApp:%'` и переписывает:

- Если `persons.name` содержит буквы (`\p{L}`) → `«<имя> (WhatsApp)»`.
- Иначе если в `contact_numbers` есть телефон (не `@lid`, не `lid:`) →
  `«Клиент <phone>»`.
- Иначе (lid-only чат без раскрытого номера) → `«Клиент WhatsApp»`.

Обновляет и `leads.title`, и зеркало в `attribute_values.text_value`
(атрибут `(entity_type='leads', code='title')`) в одной транзакции —
иначе kanban/datagrid показывают разное.

На живой БД: 3 старых заголовка превратились в
`«♡Rabiya♡ (WhatsApp)»`, `«Клиент +77064181709»`, `«Клиент WhatsApp»`.
Заодно вычистил 18 осиротевших `attribute_values` от удалённых ранее
тестовых лидов — локальная ad-hoc правка, миграцией не оформлял
(на свежей инсталляции такой ситуации не бывает).

**Что осталось переводом не покрыто (отмечено явно):**
- HTML-содержимое `email_templates.content` — там `<td>Title</td>` и
  аналогичные ярлычки внутри HTML-таблицы письма, отдельный заход.
- `activities.title` для старых записей — Krayin их генерирует на лету
  (например «Обновлено Воронка»), для старых событий в БД они уже
  зафиксированы с русскими словами благодаря правке attributes.name.

## Что сделано (2026-10-27) — интеграция Telegram

Второй канал в тот же `/admin/messages`. Без Node-моста — отправка и приём
работают из Laravel. Токен бота вводится через UI (Настройки → Интеграции),
хранится в БД зашифрованно; `.env` оставлен fallback для dev.

### Пакет `packages/Webkul/Telegram/`

- Миграция `channel_integrations` (channel unique, enabled, settings
  encrypted JSON, display_name, username, external_id, verified_at).
- `TelegramClient` — HTTP-обёртка над Bot API: `sendMessage`, `getMe`,
  `setWebhook`, `deleteWebhook`, `getWebhookInfo`, `getUpdates`. Токен
  читается из `channel_integrations` в приоритете, фолбэк `TELEGRAM_BOT_TOKEN`.
- `IncomingHandler` — общий код приёма (используется и webhook'ом,
  и polling'ом): берёт `message.text`, дедуп по `(channel, external_id)`,
  автосоздание контакта (`contact_numbers[].value = 'tg:'.chatId`) и лида
  (source «Telegram», findOrCreate), title лида:
  - есть `first_name/last_name` → `«<Имя Фамилия> (Telegram)»`,
  - есть `username` → `«@username (Telegram)»`,
  - иначе → `«Клиент Telegram #<chat_id>»`.
- `WebhookController` (`POST /api/telegram/webhook`) + middleware
  `VerifyTelegramSecret` сверяет заголовок `X-Telegram-Bot-Api-Secret-Token`
  с секретом из integration row (или env).
- Artisan `telegram:poll [--once]` — long polling loop для локалки.
- Artisan `telegram:webhook info|set|delete` — для прода.

### Страница Настройки → Интеграции

- `/admin/settings/integrations` (пункт меню `settings.integrations` в
  группе Settings).
- Карточка Telegram: статус (connected/disconnected), поле «Токен бота»
  (type=password), кнопки «Подключить» / «Переподключить» / «Отключить»,
  подсказка про @BotFather и короткие cheat-команды (`telegram:poll` /
  `telegram:webhook`).
- `POST connect` валидирует формат токена (`\d+:[A-Za-z0-9_-]{20,}`),
  вызывает `getMe` для проверки; если ок — шифрует и сохраняет, запоминает
  `username`/`display_name`/`external_id`/`verified_at`, автогенерирует
  `webhook_secret` (Str::random(48)) если его ещё нет.
- `POST disconnect` переключает `enabled=false`, токен остаётся — можно
  включить обратно.
- ACL: `settings.integrations.view`, `settings.integrations.edit`.

### Multi-канальная отправка в `/admin/messages`

- `Whatsapp\Admin\MessageController::send()` теперь выбирает канал по
  последнему сообщению `chat_id` (новый метод `resolveChannel`), и
  маршрутизирует в `TelegramClient::sendMessage` или
  `WhatsappClient::send`. Записывает outgoing с правильным `channel`.
- `chatList()` отдаёт поле `channel` в каждом элементе.
- Blade + JS `renderChatList`: мини-значок канала (голубой самолётик для
  Telegram, зелёная трубка для WhatsApp) на правый нижний угол аватарки.
- Unread badge в сайдбаре уже суммирует оба канала (нет фильтра по channel).

### Регистрация

- `composer.json` autoload: `Webkul\\Telegram\\` → `packages/Webkul/Telegram/src`.
- `bootstrap/providers.php`: `TelegramServiceProvider`.
- `config/concord.php`: `TelegramModuleServiceProvider` (`ChannelIntegration` + `Message` c Contracts и ModelProxy).
- `.env.example` дополнен: `TELEGRAM_BOT_TOKEN`, `TELEGRAM_WEBHOOK_SECRET`, `TELEGRAM_WEBHOOK_URL`.

### Как подключить (разово)

1. У @BotFather создать бота, скопировать токен.
2. В CRM: Настройки → Интеграции → вставить токен → «Подключить».
   После успешной проверки появится статус «Подключён» и имя `@your_bot`.
3. Окно PowerShell: `& 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe' artisan telegram:poll`.
4. Со второго аккаунта написать боту в Telegram → чат появится в
   `/admin/messages` через 1–2 секунды. Контакт и лид создадутся
   автоматически (source «Telegram»).

### Что не покрыто (вне MVP)

- Картинки/медиа — только текст.
- Групповые чаты — приходят, без правильной привязки.
- Inline-кнопки / reply markup.
- Статусы доставки/прочтения (у Bot API их нет аналогично WA).
- Queue — отправка синхронная внутри HTTP-запроса.

## (архив) Что было запланировано на 2026-10-27 — интеграция Telegram

Повторить WhatsApp-паттерн для второго канала.

**Node-мост.** Отдельный Node-сервис (можно в этом же `whatsapp-service/`
переименованной или `telegram-service/` рядом — решим по месту), на
`node-telegram-bot-api` или прямом Bot API:
- локально — long polling (`getUpdates`), потому что домашняя машина
  за NAT и вебхук туда не достать;
- на сервере — webhook (`setWebhook` на `https://.../api/telegram/incoming`),
  TLS уже даст Cloudflare/прод-настройка.
- выбор режима — ENV-переменная `TELEGRAM_MODE=polling|webhook`.
- те же шаблоны, что в WhatsApp-мосту: `/health`, `/send` c
  `X-Telegram-Secret`, форвард в Laravel с теми же полями `channel`,
  `external_id`, `chat_id`, `from_number`, `content`, `direction`,
  `timestamp`.

**Laravel.** В `messaging_messages` поле `channel` уже обобщённое —
нужно только разрешить значение `'telegram'`. Отображение в
`/admin/messages`:
- тот же список чатов слева, добавить индикатор канала (значок WA /
  TG) рядом с аватаркой-инициалом;
- отправка идёт тем же `POST /admin/messages/send`, но контроллер
  маршрутизирует в тот или иной Node-сервис по `channel` сообщения;
- для новых входящих — тот же сценарий автосоздания контакт + лид,
  но источник (`lead_sources`) — «Telegram» (создаётся on-demand,
  как сейчас с «WhatsApp»).

**Карточка лида из Telegram.** По той же логике, что сейчас с
WhatsApp:
- есть username → `«@username (Telegram)»`;
- есть first_name/last_name → `«Имя Фамилия (Telegram)»`;
- иначе → `«Клиент Telegram #<chat_id>»`.

**ACL.** Новые permissions `messages.telegram.view` и `.send` (или
общие с WhatsApp — обсудить). Пункт сайдбара «Сообщения» уже один,
счётчик непрочитанных просуммирует оба канала (view composer в
`WhatsappServiceProvider` переименовать в что-то обобщённое, или
дублировать).

**На потом.** Поиск по чатам, групповые чаты, медиа (сейчас обе
интеграции только текст).

## Что ещё осталось (общее)

**Для меня (разработчик):**
- Smoke-тест `http://bizcrm.test` после включения MySQL и hosts — глазами пройти по дашборду, лидам, контактам, предложениям, активностям, настройкам. Если найдутся непереведённые ключи — сообщите, добавлю.
- Пересобрать Vite (`npm install && npm run build` в `packages/Webkul/Admin`) — опционально, когда нужно будет единое поведение «только исходники». Сейчас исходники и билд в sync вручную.
- Перевод HTML-содержимого `email_templates.content`.

**Для вас (разово, нужны права администратора):**
- Прописать `127.0.0.1 bizcrm.test` в hosts (см. раздел Этапа 3 выше).
- Включить opcache в `php.ini` (см. раздел Этапа 3 выше).
- Поднять MySQL в Laragon.
- К завтрашней Telegram-интеграции: создать бота у @BotFather, получить токен и `TELEGRAM_BOT_USERNAME`, зафиксировать в `.env` (как `WHATSAPP_SECRET` сейчас).

**На будущее:**
- Заменить SVG-заглушки «bizcrm» на реальный логотип (пути — в разделе Этапа 2 выше).
- Если решите убрать помощь из сайдбара совсем — скажите, уберу route/menu.
