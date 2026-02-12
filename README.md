# ChatOn SDK

Official SDK for ChatOn application - License validation, auto-updates, and plugin management.

[![Latest Version](https://img.shields.io/packagist/v/chaton/chaton-sdk.svg)](https://packagist.org/packages/chaton/chaton-sdk)
[![Total Downloads](https://img.shields.io/packagist/dt/chaton/chaton-sdk.svg)](https://packagist.org/packages/chaton/chaton-sdk)
[![License](https://img.shields.io/packagist/l/chaton/chaton-sdk.svg)](https://packagist.org/packages/chaton/chaton-sdk)

## Features

- 🔐 **License Validation** - RSA signature verification with embedded public key
- 🔄 **Auto Updates** - Automatic version updates for ChatOn core and plugins
- 🧩 **Plugin Management** - Install, update, and manage plugins
- 💾 **Smart Caching** - Multi-layer caching with 7-day grace period
- 🌐 **Domain Locking** - Secure domain-based licensing
- 🚀 **SAAS Control** - Feature gating based on license type

## Installation

Install via Composer:

```bash
composer require chaton/chaton-sdk
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=chaton-config
```

## Usage

### License Validation

```php
use Chaton\SDK\Facades\License;

// Check if license is valid
if (License::isValid()) {
    // License is valid
}

// Check SAAS features
if (License::isSaasEnabled()) {
    // SAAS features available
}
```

### Get License Information

```php
$info = License::getLicenseInfo();
// Returns: purchase_code, domain, license_type, features, etc.
```

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x

## License

The ChatOn SDK is proprietary software. Unauthorized distribution is prohibited.

## Support

- Documentation: https://docs.chaton.app
- Support: support@chaton.app
- Website: https://chaton.app

