<h1 align="center">Bouteg Payfip Plugin</h1>

<p align="center">DGFIP Payfip payment plugin for Sylius applications.</p>

## Requirements

| Package | Version |
| --- | --- |
| PHP | ^7.3 |
| Sylius | 1.7 |


## Installation

Use Composer to install the plugin :

```
composer require bouteg/payfip-plugin
```

Add plugin dependencies to your `config/bundles.php` file :
```php
return [
  ...
  Bouteg\PayfipPlugin\BoutegPayfipPlugin::class => ['all' => true],
];
```


## Usage

- Go to the Payment Methods admin page and choose to create a new "Payfip payment" method. 
- Choose a name & a code for the method.
- Grab your client id from the DGFIP and paste it into the appropriate fields. 
- Set the mode to Test if you want to first test the integration with a fake credit card.