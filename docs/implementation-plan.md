# План реализации модуля конвертации валют

## Цель
Создать модуль для хранения и конвертации валют с загрузкой курсов с freecurrencyapi.com.

## Требования
- Предопределённый список валют
- Загрузка и хранение курсов в БД (SQLite)
- Ежедневное обновление курсов
- Сервис конвертации: `$converter->convert(123, 'USD', 'RUB')`
- Простая админ-страница с HTTP Basic Auth (admin:admin)

---

## Фаза 1: Установка пакетов

```bash
composer require symfony/orm-pack           # Doctrine ORM
composer require symfony/http-client        # HTTP клиент
composer require symfony/scheduler          # Планировщик задач
composer require symfony/security-bundle    # Для HTTP Basic Auth
composer require doctrine/doctrine-fixtures-bundle --dev  # Фикстуры
```

---

## Фаза 2: Конфигурация

### .env
```dotenv
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
FREECURRENCYAPI_BASE_URL=https://api.freecurrencyapi.com/v1
```

### config/packages/doctrine.yaml
```yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
    orm:
        auto_generate_proxy_classes: true
        auto_mapping: true
        mappings:
            App:
                type: attribute
                dir: '%kernel.project_dir%/src/Entity'
                prefix: 'App\Entity'
```

### config/packages/security.yaml
```yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\InMemoryUser: plaintext

    providers:
        in_memory:
            memory:
                users:
                    admin:
                        password: admin
                        roles: ['ROLE_ADMIN']

    firewalls:
        admin:
            pattern: ^/admin
            http_basic:
                realm: Admin Area
            provider: in_memory
        main:
            lazy: true

    access_control:
        - { path: ^/admin, roles: ROLE_ADMIN }
```

---

## Фаза 3: Сущности

### src/Entity/Currency.php
| Поле      | Тип       | Описание                    |
|-----------|-----------|-----------------------------|
| id        | int       | PK                          |
| code      | string(3) | ISO код (USD, EUR) - unique |
| name      | string    | Название                    |
| symbol    | string    | Символ ($, €)               |
| isActive  | bool      | Активна ли валюта           |
| createdAt | datetime  | Дата создания               |
| updatedAt | datetime  | Дата обновления             |

### src/Entity/ExchangeRate.php
| Поле           | Тип            | Описание            |
|----------------|----------------|---------------------|
| id             | int            | PK                  |
| baseCurrency   | Currency       | Базовая валюта (FK) |
| targetCurrency | Currency       | Целевая валюта (FK) |
| rate           | decimal(20,10) | Курс                |
| rateDate       | date           | Дата курса          |
| createdAt      | datetime       | Дата создания       |

Уникальный индекс: (baseCurrency, targetCurrency, rateDate)

---

## Фаза 4: Сервисы

### src/Service/FreeCurrencyApi/FreeCurrencyApiClient.php
HTTP клиент для API:
- `getLatestRates(baseCurrency, currencies)` - получить курсы
- `getCurrencies()` - получить список валют

### src/Service/FreeCurrencyApi/DTO/
- `ExchangeRateData.php` - DTO для курсов
- `CurrencyData.php` - DTO для валют

### src/Service/ExchangeRateService.php
- `updateRates()` - загрузить и сохранить курсы
- `syncCurrencies()` - синхронизировать список валют

### src/Service/CurrencyConverterService.php
```php
public function convert(
    float|int|string $amount,
    string $fromCurrency,
    string $toCurrency,
    int $precision = 2
): string
```
Поддержка:
- Прямой курс (USD → RUB)
- Обратный курс (RUB → USD через инверсию)
- Кросс-курс через USD (EUR → RUB через USD)

---

## Фаза 5: Консольные команды

### src/Command/UpdateExchangeRatesCommand.php
```bash
php bin/console app:exchange-rates:update
```

### src/Command/SyncCurrenciesCommand.php
```bash
php bin/console app:currencies:sync
```

---

## Фаза 6: Планировщик

### src/Scheduler/ExchangeRateSchedule.php
Ежедневное обновление в 6:00 UTC:
```php
RecurringMessage::cron('0 6 * * *', new UpdateExchangeRatesMessage())
```

### src/Scheduler/UpdateExchangeRatesHandler.php
Обработчик сообщения - вызывает `ExchangeRateService::updateRates()`

---

## Фаза 7: Админ-страница (простая)

### src/Controller/AdminController.php
- Роут: `GET /admin` - список курсов
- HTTP Basic Auth: admin:admin
- Простой Twig шаблон с таблицей курсов

### templates/admin/rates.html.twig
- Таблица всех курсов
- Фильтр по дате
- Кнопка ручного обновления (опционально)

---

## Фаза 8: Фикстуры

### src/DataFixtures/CurrencyFixtures.php
Предустановленные валюты:
- USD, EUR, GBP, RUB, CNY, JPY

---

## Структура файлов

```
src/
├── Command/
│   ├── SyncCurrenciesCommand.php
│   └── UpdateExchangeRatesCommand.php
├── Controller/
│   ├── AdminController.php          # Простая админка
│   └── HomeController.php
├── DataFixtures/
│   └── CurrencyFixtures.php
├── Entity/
│   ├── Currency.php
│   └── ExchangeRate.php
├── Repository/
│   ├── CurrencyRepository.php
│   └── ExchangeRateRepository.php
├── Scheduler/
│   ├── ExchangeRateSchedule.php
│   ├── UpdateExchangeRatesHandler.php
│   └── UpdateExchangeRatesMessage.php
└── Service/
    ├── Exception/
    │   ├── ConversionException.php
    │   ├── CurrencyNotFoundException.php
    │   └── RateNotFoundException.php
    ├── FreeCurrencyApi/
    │   ├── DTO/
    │   │   ├── CurrencyData.php
    │   │   └── ExchangeRateData.php
    │   ├── Exception/
    │   │   └── ApiException.php
    │   └── FreeCurrencyApiClient.php
    ├── CurrencyConverterService.php
    └── ExchangeRateService.php

templates/
└── admin/
    └── rates.html.twig
```

---

## Порядок реализации

1. Установить пакеты (composer)
2. Настроить doctrine.yaml для SQLite
3. Настроить security.yaml для HTTP Basic Auth
4. Создать сущности Currency, ExchangeRate
5. Создать репозитории
6. Запустить миграции
7. Создать FreeCurrencyApiClient + DTO + Exception
8. Создать ExchangeRateService
9. Создать CurrencyConverterService
10. Создать консольные команды
11. Настроить планировщик
12. Создать AdminController и шаблон
13. Загрузить фикстуры
14. Протестировать

---

## Верификация

### Тест конвертера
```php
$converter->convert(100, 'USD', 'RUB'); // -> "9150.00" (примерно)
$converter->convert(1000, 'RUB', 'EUR'); // -> "10.50" (примерно)
```

### Консольные команды
```bash
# Синхронизировать валюты
make console cmd="app:currencies:sync"

# Загрузить курсы
make console cmd="app:exchange-rates:update"
```

### Админка
- Открыть http://localhost:8081/admin
- Ввести логин: admin, пароль: admin
- Увидеть таблицу курсов

### Планировщик
```bash
# Запустить воркер планировщика
make console cmd="messenger:consume scheduler_exchange_rates"
```
