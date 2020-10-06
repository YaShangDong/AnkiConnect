# PHP AnkiConnect API

`composer require yasd/ankiconnect`

```php
$anki = \YaSD\AnkiConnect\Anki::create();
echo sprintf("AnkiConnect API Version: %d", $anki->getApiVersion());;
// ... other api calls
```
