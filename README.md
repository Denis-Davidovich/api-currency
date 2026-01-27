# Currency Converter API

Модуль конвертации валют на Symfony 8 с загрузкой курсов из FreeCurrencyAPI.

## Demo

https://api-currency.monopoly-gold.com/admin (admin:admin)

## Требования

- Docker & Docker Compose
- API ключ от [freecurrencyapi.com](https://freecurrencyapi.com/)

## Быстрый старт

```bash
# 1. Клонировать и перейти в директорию
git clone <repo> && cd api-currency

# 2. Добавить секреты в .env.local
cp .env.local.example .env.local
# Заполнить APP_SECRET и FREECURRENCYAPI_KEY

# 3. Запустить
make install

# 4. Создать БД и загрузить данные
make console cmd="doctrine:schema:create"
make console cmd="doctrine:fixtures:load --no-interaction"
make console cmd="app:exchange-rates:update"
```

## Использование

### Сервис конвертации

```php
use App\Service\CurrencyConverterService;

$result = $converter->convert(100, 'USD', 'RUB');    // "7652.64"
$result = $converter->convert(1000, 'EUR', 'USD');   // "1187.45"
$result = $converter->convert(50.5, 'GBP', 'JPY', 4); // "10567.8923"
```

### Консольные команды

```bash
# Обновить курсы
make console cmd="app:exchange-rates:update"

# Синхронизировать валюты из API
make console cmd="app:currencies:sync"
```

### Админ-панель

- URL: http://localhost:8081/admin
- Логин: `admin`
- Пароль: `admin`

## Архитектура

```
src/
├── Entity/
│   ├── Currency.php          # Валюта (USD, EUR, RUB...)
│   └── ExchangeRate.php      # Курс обмена
├── Service/
│   ├── CurrencyConverterService.php   # Конвертация
│   ├── ExchangeRateService.php        # Обновление курсов
│   └── FreeCurrencyApi/               # API клиент
├── Command/
│   ├── UpdateExchangeRatesCommand.php
│   └── SyncCurrenciesCommand.php
└── Scheduler/
    └── ExchangeRateSchedule.php       # Cron: 0 6 * * *
```

## Предустановленные валюты

USD, EUR, GBP, RUB, CNY, JPY

## Тесты

```bash
make test
```

## Планировщик

Автоматическое обновление курсов ежедневно в 06:00 UTC:

```bash
make console cmd="messenger:consume scheduler_exchange_rates"
```

## Стек

- PHP 8.5
- Symfony 8.0
- Doctrine ORM + SQLite
- Symfony HttpClient
- Symfony Scheduler
