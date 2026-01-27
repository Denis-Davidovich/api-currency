# Чеклист проверки модуля конвертации валют

## Предварительные требования

- [ ] Добавить секреты в `.env.local`:
  ```
  APP_SECRET=<сгенерировать: openssl rand -hex 16>
  FREECURRENCYAPI_KEY=<получить на freecurrencyapi.com>
  ```
  Получить бесплатный API ключ: https://freecurrencyapi.com/

- [ ] Пересобрать контейнеры (если изменили Dockerfile):
  ```bash
  docker compose build && docker compose up -d
  ```

- [ ] Создать схему БД и загрузить фикстуры:
  ```bash
  make console cmd="doctrine:schema:create"
  make console cmd="doctrine:fixtures:load --no-interaction"
  ```

---

## 1. База данных

- [ ] Проверить создание схемы:
  ```bash
  make console cmd="doctrine:schema:validate"
  ```

- [ ] Проверить наличие валют:
  ```bash
  make console cmd="dbal:run-sql 'SELECT * FROM currencies'"
  ```
  Ожидается: 6 валют (USD, EUR, GBP, RUB, CNY, JPY)

---

## 2. Консольные команды

- [ ] Синхронизация валют (опционально, загрузит все валюты из API):
  ```bash
  make console cmd="app:currencies:sync"
  ```
  Ожидается: `Successfully synced X currencies`

- [ ] Обновление курсов:
  ```bash
  make console cmd="app:exchange-rates:update"
  ```
  Ожидается: `Successfully updated X exchange rates`

- [ ] Проверить загруженные курсы:
  ```bash
  make console cmd="dbal:run-sql 'SELECT * FROM exchange_rates LIMIT 10'"
  ```

---

## 3. Админ-панель

- [ ] Открыть http://localhost:8081/admin
- [ ] Ввести логин: `admin`, пароль: `admin`
- [ ] Проверить отображение таблицы курсов
- [ ] Проверить фильтр по дате
- [ ] Нажать кнопку "Update Rates" и проверить обновление

---

## 4. Сервис конвертации (функциональный тест)

Создать тестовый скрипт или использовать Symfony console:

```bash
make console cmd="debug:autowiring CurrencyConverterService"
```

Или создать тестовый контроллер/команду для проверки:

```php
// Пример использования в коде
$result = $converter->convert(100, 'USD', 'EUR');
// Ожидается: строка с числом, например "92.50"

$result = $converter->convert(1000, 'RUB', 'USD');
// Ожидается: конвертация через обратный курс

$result = $converter->convert(100, 'EUR', 'RUB');
// Ожидается: кросс-курс через USD
```

---

## 5. Планировщик

- [ ] Проверить регистрацию расписания:
  ```bash
  make console cmd="debug:scheduler"
  ```

- [ ] Запустить воркер планировщика (в отдельном терминале):
  ```bash
  make console cmd="messenger:consume scheduler_exchange_rates -vv"
  ```

---

## 6. API статус

- [ ] Проверить что приложение работает:
  ```bash
  curl http://localhost:8081/api/status
  ```
  Ожидается: `{"status":"ok",...}`

---

## 7. Unit-тесты

- [ ] Запустить все тесты:
  ```bash
  make test
  ```
  Ожидается: все тесты пройдены

Покрытие тестами:
- `FreeCurrencyApiClientTest` - тесты API клиента (с mock HTTP)
- `CurrencyConverterServiceTest` - тесты конвертации валют
- `ExchangeRateServiceTest` - тесты сервиса обновления курсов

---

## 8. Проверка исключений

- [ ] Конвертация несуществующей валюты должна выбросить `CurrencyNotFoundException`
- [ ] Конвертация без курса должна выбросить `RateNotFoundException`
- [ ] Некорректная сумма должна выбросить `ConversionException`

---

## Возможные проблемы

### API возвращает ошибку
- Проверить валидность API ключа
- Проверить лимиты бесплатного плана (300 запросов/месяц)

### Нет курсов в БД
- Выполнить `app:exchange-rates:update`
- Проверить что валюты загружены (фикстуры)

### Ошибка авторизации в админке
- Логин: `admin`
- Пароль: `admin`
- Проверить `config/packages/security.yaml`

---

## Структура файлов

```
src/
├── Command/
│   ├── SyncCurrenciesCommand.php        ✓
│   └── UpdateExchangeRatesCommand.php   ✓
├── Controller/
│   ├── AdminController.php              ✓
│   └── HomeController.php               ✓
├── DataFixtures/
│   └── CurrencyFixtures.php             ✓
├── Entity/
│   ├── Currency.php                     ✓
│   └── ExchangeRate.php                 ✓
├── Repository/
│   ├── CurrencyRepository.php           ✓
│   └── ExchangeRateRepository.php       ✓
├── Scheduler/
│   ├── ExchangeRateSchedule.php         ✓
│   ├── UpdateExchangeRatesHandler.php   ✓
│   └── UpdateExchangeRatesMessage.php   ✓
└── Service/
    ├── Exception/
    │   ├── ConversionException.php      ✓
    │   ├── CurrencyNotFoundException.php ✓
    │   └── RateNotFoundException.php    ✓
    ├── FreeCurrencyApi/
    │   ├── DTO/
    │   │   ├── CurrencyData.php         ✓
    │   │   └── ExchangeRateData.php     ✓
    │   ├── Exception/
    │   │   └── ApiException.php         ✓
    │   └── FreeCurrencyApiClient.php    ✓
    ├── CurrencyConverterService.php     ✓
    └── ExchangeRateService.php          ✓

templates/
└── admin/
    └── rates.html.twig                  ✓

config/packages/
├── doctrine.yaml                        ✓
├── messenger.yaml                       ✓
└── security.yaml                        ✓
```
