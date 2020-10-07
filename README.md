# PHP AnkiConnect API

`composer require yasd/ankiconnect nyholm/psr7 kriswallsmith/buzz`

```php
$anki = \YaSD\AnkiConnect\Anki::create();
echo sprintf("AnkiConnect API Version: %d", $anki->getApiVersion());;
// ... other api calls
```
