# Arris.Config

Конфигуратор экосистемы Arris. Включает:

- низкоуровневый читатель/писатель конфигов `Arris\Core\Config\Config` (PHP, INI, JSON, XML, YAML, Properties, Serialize);
- прикладной класс `Arris\AppConfig` — загрузка конфиг-файлов с дефолтами и рекурсивным слиянием.

## Requirements

- PHP 8.2+

> **IMPORTANT:** Если нужны YAML-файлы или строки, подключите [Symfony Yaml component](https://github.com/symfony/Yaml) (`composer require symfony/yaml`).

## Installation

```sh
$ composer require karelwintersky/arris.config
```

## Usage

### AppConfig (рекомендуется)

Загружает файлы и сливает их с дефолтами. `null` в файле удаляет ключ из дефолтов, массивы сливаются рекурсивно.

```php
use Arris\AppConfig;

$config = new AppConfig(
    ['config.json', '?config.local.json'],  // '?' - опциональный файл
    ['db' => ['host' => 'localhost', 'port' => 3306]]
);

$host = $config->get('db.host');          // из файла или дефолта
$config->set('app.debug', true);          // установить
$config->add(['cache' => ['ttl' => 300]]); // массовое слияние
$config->replace([...]);                   // заменить целиком
```

Доступ через dot-нотацию, `ArrayAccess` и итерацию.

### Config (низкоуровневый)

```php
use Arris\Core\Config\Config;
use Arris\Core\Config\Parser\Json;

// Один файл
$conf = Config::load('config.json');
$conf = new Config('config.json');

// Несколько файлов
$conf = new Config(['config.json', 'config.xml']);

// Директория (glob по алфавиту)
$conf = new Config(__DIR__ . '/config');

// Опциональные файлы
$conf = new Config(['config.dist.json', '?config.json']);

// Указанный парсер (применяется ко всем файлам)
$conf = new Config('configuration.config', new Json);

// Из строки
$conf = Config::load($settingsJson, new Json, true);

// Сохранение (кроме PHP)
$conf->toFile('config.yaml');
$ini = $conf->toString(new \Arris\Core\Config\Writer\Ini());
```

При загрузке нескольких файлов значения дублирующихся ключей берутся из последнего файла.

**Warning:** Не включайте непроверенные конфиги в PHP-формате — он может выполнять произвольный код.

### Наследование AbstractConfig

Для конфига «без файлового I/O» расширьте `AbstractConfig` и заполните `getDefaults()`:

```php
use Arris\Core\Config\AbstractConfig;

class MyConfig extends AbstractConfig
{
    protected function getDefaults()
    {
        return [
            'host' => 'localhost',
            'port' => 80
        ];
    }
}
```

### Слияние инстансов

```php
$conf1 = Config::load('conf1.json');
$conf2 = Config::load('conf2.json');
$conf1->merge($conf2);
```

## Testing

``` bash
$ composer install
$ vendor/bin/phpunit
```

## License

The MIT License (MIT).
