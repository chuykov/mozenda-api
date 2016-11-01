## Simple PHP Helper for Mozenda API

Mozenda-api is a simple PHP library for Mozenda API calls.

### Installation

To install via [Composer](https://getcomposer.org), add the following to your composer.json file and run `composer update`:

```
    {
        "require": {
            "pixel/mozenda-api": "dev-master"
        },
        "repositories": [
            {
                 "type": "git",
                 "url": "https://github.com/chuykov/mozenda-api"
            }
        ]
    }
```

### Dependencies

```
"php": ">=5.6.4",
"guzzlehttp/guzzle": "~6.0"
```

### Basic Usage

Detailed explanation can be found below:

- Create an instance of `MozendaAPI` class with appropriate Web Service Key.

```php
$mozenda = new MozendaAPI('XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX');
```

- Add an empty collection in your account.

```php
$collectionID = $mozenda->createCollection('My Collection');
```

- Add a field to the desired Collection. This field is created with the default format of “Text”.

```php
$mozenda->addCollectionField($collectionID, 'Field Name');
```

- Get a list of views for a particular collection.

```php
$viewID = $mozenda->getMozendaView($collectionID);
```

- Start a new job for an Agent.

```php
$jobIDs = $mozenda->runMozendaJob($agentID);
```

- Get the details of a job by the Job ID.

```php
foreach($jobIDs as $jobID) {
    $JobStatus = $mozenda->checkJobStatus($jobID);
}
```

- Get result data from the defined agent.

```php
$items = $mozenda->collectData($agentID);
```

### Important Links

1. All Mozenda API information can be found [here](http://www.mozenda.com/api).

### Contribution

If you find any bugs, either post an issue or pull request are always welcome! :)

### License

This library is licensed under the MIT License.