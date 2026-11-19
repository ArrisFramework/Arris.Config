# Arris.Config

Небольшой пакет для чтения конфигурации. Работает сам по себе, без фреймворка Arris.

Умеет читать:

- PHP-файлы, которые возвращают массив;
- INI;
- JSON;
- YAML (нужен `symfony/yaml`);
- TOML (нужен `internal/toml`).

Писать конфиги обратно в файлы пакет не умеет — только читает. Изменять конфиг в памяти можно через `set()`, `replace()`, `remove()` и `add()`.

PHP-парсер выполняет код файла (`require`) и строки (`eval`), поэтому загружайте только доверенные конфиги.

## Установка

```bash
composer require karelwintersky/arris.config
```

Для YAML и TOML нужны отдельные библиотеки:

```bash
composer require symfony/yaml
composer require internal/toml
```

PHP, INI и JSON работают без дополнительных зависимостей.

## Быстрый старт

```php
use Arris\AppConfig;

$config = new AppConfig(
    files: ['config.php', '?config.local.php'],
    defaults: [
        'app' => [
            'debug' => false,
        ],
        'database' => [
            'host' => 'localhost',
            'port' => 3306,
        ],
    ]
);

$config->get('database.host'); // localhost
$config->get('database.port'); // 3306
```

Файлы загружаются поверх значений по умолчанию. Если файла с `?` нет — ничего не ломается, он просто пропускается.

Можно передать несколько файлов:

```php
$config = new AppConfig(['config.yaml'], $defaults);
$config = new AppConfig(['base.yaml', '?local.yaml'], $defaults);
```

Для одного файла есть короткий хелпер:

```php
$config = AppConfig::fromFile('config.yaml', $defaults);
```

Директории не поддерживаются: передавайте явный список файлов. Путь с `?` пропускается, если файла нет.

Можно собрать конфиг из строки:

```php
use Arris\Core\Config\Parser\Json;

$config = AppConfig::fromString($json, new Json(), $defaults);
```

## Как обращаться к ключам

Внутри конфига ключи — это обычные строки. Точка в имени ключа ничем не особенна.

А вот в **выражении доступа** точка означает переход на уровень вложенности:

```php
$config->get('database.host');    // ключ host внутри database
$config->get('a\\.b');            // один ключ с именем "a.b"
$config->get('a.b\\.c');          // ключ a -> "b.c"
```

То есть:

```php
$config->set('database.host', 'localhost');
// получится ['database' => ['host' => 'localhost']]

$config->set('database\\.host', 'localhost');
// получится ['database.host' => 'localhost']
```

`get()` без аргументов возвращает весь конфиг. Символ `*` — обычный ключ, а не wildcard:

```php
$config->get();   // весь конфиг
$config->get('*'); // значение ключа '*', а не весь конфиг
```

Массивы, которые вы передаёте в `add()`, `replace()` или в конструктор, **не разворачиваются**:

```php
$config->add(['a.b' => 1]);
// это литеральный ключ 'a.b', а не вложенность

$config->add(['a' => ['b' => 1]]);
// а вот это — вложенность
```

Так сделано специально: `all()` можно передать обратно в `replace()` без неожиданной перестройки ключей.

## Изменение конфига

### `set()` — аккуратное изменение

`set()` умеет создавать вложенные ключи, но не меняет тип уже существующего узла.

```php
$config->set('app.debug', true);
$config->set('app.logging.enabled', true);

$config->get('app.logging'); // ['enabled' => true]
```

Если узел уже существует:

- скаляр заменяется скаляром;
- список заменяется списком;
- ассоциативные массивы сливаются;
- пустой массив `[]` очищает существующий массив.

Списки не охраняются: `remove()` из list или запись по разреженному индексу может превратить list в assoc. Это ожидаемое поведение.

А вот менять скаляр на массив и наоборот через `set()` нельзя:

```php
$config->set('app', true);
$config->set('app.debug', true); // ошибка: app уже скаляр
```

```php
$config->set('app.debug', true);
$config->set('app', true); // ошибка: app уже массив
```

В таких случаях нужно явно сказать, что вы заменяете узел:

```php
$config->replace('app', true);
// или
$config->remove('app');
$config->set('app.debug', true);
```

`null` — обычное значение. `set('app', ['debug' => null])` допустим. Но `set()` не сужает структуру: если узел уже непустой массив/список, `set($path, null)` бросит `StructureConflictException`. Для явной замены ветки на `null` используйте `replace($path, null)`. Для удаления — `remove()`.

### `replace()` — явная замена

```php
$config->replace('app', true);             // заменить узел целиком
$config->replace('app', ['debug' => true]); // заменить массив целиком, без слияния
$config->replace('app', null);             // записать null
$config->replace(['app' => ['debug' => true]]); // заменить весь конфиг
```

### `remove()`

```php
$config->remove('app.debug');
```

## Значения по умолчанию и файлы

`AppConfig` считает значения по умолчанию базой, а файлы — слоями поверх неё.

- ассоциативные массивы сливаются;
- списки заменяются целиком;
- скаляр может заменить массив и наоборот — файл сильнее;
- `null` в файле удаляет ключ из значений по умолчанию;
- `null` в значениях по умолчанию — это обычное значение.

`AppConfig` использует patch-семантику, поэтому `null` из файла удаляет ключ, даже если в defaults его не было: явный `null` из файла в `AppConfig` не сохраняется. Низкоуровневый `Config`, наоборот, сохраняет `null` из файла как значение.

Root-список (файл возвращает список верхнего уровня) всегда заменяет предыдущий, а не сливается поэлементно.

## Низкоуровневый `Config`

Если значения по умолчанию не нужны, можно читать файлы напрямую:

```php
use Arris\Core\Config\Config;

$config = new Config(['config.json']);
$config = Config::load(['config.php', '?config.local.php']);
$config = Config::fromFile('config.json');

// Пропускать отсутствующие файлы вместо исключения
$config = new Config(['config.json'], null, true);
$config = Config::load(['config.php', '?config.local.php'], null, true);
```

Из строки:

```php
use Arris\Core\Config\Parser\Json;
use Arris\Core\Config\Parser\Yaml;
use Arris\Core\Config\Parser\Toml;

$config = Config::fromString($json, new Json());
$config = Config::fromString($yaml, new Yaml());
$config = Config::fromString($toml, new Toml());
```

Список источников — плоский массив строк. Вложенные массивы и glob-паттерны (`*.yaml`, `**/*.yaml`) не поддерживаются. Директории не сканируются: передавайте явные файлы.

### INI

INI читается как есть. Ключи с точками остаются литеральными:

```ini
network.http.host = localhost

[application]
name = configuration
```

```php
$config->get('network\\.http\\.host'); // localhost
$config->get('application.name');      // configuration
```

## Слияние и `all()`

`all()`, `getAll()` и `get()` без аргументов возвращают весь конфиг массивом.

```php
$all = $config->all();
$all = $config->getAll();
$all = $config->get();
```

`get('*')` — это обычный литеральный ключ `*`, а не wildcard.

`getAllMatch($mask)` зарезервирован и пока кидает `NotImplementedException`.

Если нужно получить точную копию:

```php
$copy = new AppConfig();
$copy->replace($all);
```

`add($all)` тоже сработает, но `null`-значения при этом удалятся: для `add()` `null` означает «удалить ключ».

## Исключения

Все исключения пакета реализуют маркерный интерфейс `Arris\Core\Config\ConfigExceptionInterface`, поэтому их можно ловить одним `catch`:

```php
try {
    $config = new AppConfig(['config.json']);
} catch (\Arris\Core\Config\ConfigExceptionInterface $exception) {
    // любое исключение пакета
}
```

- `StructureConflictException` — попытка изменить тип узла через `set()`.
- `InvalidConfigException` — JSON/YAML/PHP с корнем-скаляром вместо объекта/массива.
- `NotImplementedException` — зарезервированный метод (`getAllMatch()`).
- `FileNotFoundException` — файл не найден.
- `UnsupportedFormatException` — неизвестный формат или отсутствует optional-библиотека YAML/TOML.
- `ParseException` — ошибка разбора файла или строки; наследует `ErrorException`, но реализует общий маркер.

`EmptyDirectoryException` оставлен для обратной совместимости, но больше не бросается: сканирование директорий удалено.

## Известные ограничения

Это сознательно принятые ограничения текущего релиза, а не список багов к исправлению:

- `getAllMatch($mask)` остаётся зарезервированным и бросает `NotImplementedException`; семантика маски пока не определена.
- `Php::parseString()` не поддерживает строки, начинающиеся с `declare(strict_types=1)`: PHP требует, чтобы `declare` был первым statement, а парсер оборачивает строку в `eval`. Для таких конфигов используйте `Config::fromFile()`/`AppConfig::fromFile()` (там используется `require`).
- Списки не охраняются: `remove()`, разреженный индекс или индекс с ведущим нулём могут превратить list в assoc. Это ожидаемое поведение.
- `AppConfig` поддерживает только `?`-префикс для необязательных файлов; отдельных параметров `$ignoreNonExistentFiles` и forced parser у него нет.
- Сканирование директорий удалено. `EmptyDirectoryException` оставлен для обратной совместимости, но больше не бросается.

## Обновление с 1.x

- Удалены writer'ы и парсеры XML/Properties/Serialize.
- Удалён `Config::fromDirectory()`; передавайте явные файлы.
- `get('*')` больше не означает «весь конфиг»: используйте `get()`/`getAll()`/`all()`.
- `AbstractConfig::$data` теперь `protected`; для чтения — `all()`/`getAll()`, для замены — `replace(array)`.
- Удалён `Iterator` (остался `IteratorAggregate`) и внутренний кэш значений.
- `set()` строгий, списки при разреженных операциях могут становиться assoc.
- Публичный API типизирован под PHP 8.2.

## Тесты

```bash
composer install
vendor/bin/phpunit
```

## Лицензия

MIT.
