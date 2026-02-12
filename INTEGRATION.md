# ChatOn License SDK - Integration Guide

This guide will help you integrate the ChatOn License SDK into your Laravel application.

## Installation

### 1. Add SDK to Composer

Since this is a private package not yet published to Packagist, add it as a local repository:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./license-sdk"
        }
    ],
    "require": {
        "egiw/chaton-license-sdk": "@dev"
    }
}
```

Then run:

```bash
composer update egiw/chaton-license-sdk
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=chaton-license-config
```

This will create `config/chaton-license.php`.

### 3. Configure Environment

Add to your `.env`:

```env
CHATON_LICENSE_SERVER_URL=https://your-license-server.workers.dev
CHATON_LICENSE_CACHE_DRIVER=redis
CHATON_LICENSE_STRICT_MODE=true
CHATON_LICENSE_DOMAIN=null  # null = auto-detect
```

### 4. Run Migration

```bash
php artisan migrate
```

This creates the `license_cache` table for local caching.

### 5. Update Public Key

After generating RSA key pair on license server, update the public key in `config/chaton-license.php`:

```php
'public_key' => <<<'EOD'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA...
-----END PUBLIC KEY-----
EOD,
```

## Usage

### Activate License

Create an activation page or use the provided installation wizard:

```php
use Egiw\ChatonLicense\Facades\License;

$result = License::activate('PURCHASE-CODE-HERE', 'example.com');

if ($result['success']) {
    echo "License Type: " . $result['license_type'];
    // regular or extended
}
```

### Check License Validity

```php
if (License::isValid()) {
    // License is valid, proceed
} else {
    // License is invalid, show error
}
```

### Check SAAS Feature

```php
if (License::isSaasEnabled()) {
    // Show SAAS features
    // This returns true only for Extended license
} else {
    // Hide SAAS features
}
```

### Get License Information

```php
$info = License::getLicenseInfo();

// Returns:
// [
//     'purchase_code' => '...',
//     'domain' => 'example.com',
//     'license_type' => 'extended',
//     'features' => ['saas' => true],
//     'activated_at' => '...',
//     'valid' => true,
// ]
```

### Protect Routes with Middleware

#### Protect Vital Controllers

```php
// In routes/web.php or routes/api.php
Route::middleware(['license.valid'])->group(function () {
    Route::get('/important-feature', [Controller::class, 'method']);
});
```

#### Protect SAAS Features

```php
Route::middleware(['license.saas'])->group(function () {
    // SAAS-only routes (Extended license required)
    Route::resource('organizations', OrganizationController::class);
    Route::resource('subscriptions', SubscriptionController::class);
});
```

### Feature Gate in Blade Templates

```blade
@if(License::isValid())
    <p>License is valid!</p>
@endif

@if(License::isSaasEnabled())
    <a href="{{ route('organizations.index') }}">Manage Organizations</a>
@else
    <div class="alert alert-warning">
        SAAS features require Extended License. 
        <a href="#">Upgrade Now</a>
    </div>
@endif
```

### Feature Gate in Controllers

```php
use Egiw\ChatonLicense\Facades\License;
use Egiw\ChatonLicense\Exceptions\LicenseException;

class OrganizationController extends Controller
{
    public function index()
    {
        if (!License::isSaasEnabled()) {
            throw LicenseException::featureNotAvailable('SAAS');
        }

        // SAAS logic here
    }
}
```

### Using FeatureGate Facade

```php
use Egiw\ChatonLicense\Facades\FeatureGate;

// Check single feature
if (FeatureGate::isEnabled('saas')) {
    // Feature is enabled
}

// Check multiple features (OR logic)
if (FeatureGate::hasAny(['saas', 'plugins'])) {
    // At least one feature is enabled
}

// Check multiple features (AND logic)
if (FeatureGate::hasAll(['saas', 'plugins'])) {
    // All features are enabled
}

// Get all enabled features
$enabledFeatures = FeatureGate::getEnabledFeatures();
// Returns: ['saas']
```

## Daily Validation

The SDK includes automatic daily validation to ensure licenses remain valid.

### Schedule Configuration

The command is already scheduled in `routes/console.php`:

```php
Schedule::command('license:validate')->daily()->at('02:00');
```

### Manual Validation

You can manually trigger validation:

```bash
# Regular validation (uses cache if within 24h)
php artisan license:validate

# Force remote validation
php artisan license:validate --force
```

## Grace Period

If the license server is unreachable, the application will continue using cached license data for **7 days** (configurable).

After grace period expires, the application will be locked until connection is restored.

## Error Handling

### Handle License Exceptions

```php
use Egiw\ChatonLicense\Exceptions\LicenseException;

try {
    License::activate($purchaseCode, $domain);
} catch (LicenseException $e) {
    // Handle specific errors:
    // - Invalid purchase code
    // - Domain mismatch
    // - License expired
    // - Server unreachable
    Log::error('License error: ' . $e->getMessage());
}
```

### Non-Strict Mode

In non-strict mode, invalid licenses will show warnings but won't block access:

```env
CHATON_LICENSE_STRICT_MODE=false
```

This is useful for development or testing.

## SAAS Feature Control

The SDK automatically controls SAAS features based on license type:

- **Regular License**: SAAS features are DISABLED
- **Extended License**: SAAS features are ENABLED

### Example: Conditional SAAS Routes

```php
// Only register SAAS routes if enabled
if (app(\Egiw\ChatonLicense\Contracts\LicenseInterface::class)->isSaasEnabled()) {
    Route::prefix('saas')->group(function () {
        // SAAS routes here
    });
}
```

### Example: Blade Component

```blade
{{-- resources/views/components/saas-feature.blade.php --}}
@props(['feature' => 'saas'])

@php
    $enabled = \Egiw\ChatonLicense\Facades\License::isSaasEnabled();
@endphp

@if($enabled)
    {{ $slot }}
@else
    <div class="alert alert-warning">
        This feature requires Extended License.
        <a href="https://codecanyon.net" target="_blank">Upgrade Now</a>
    </div>
@endif
```

Usage:

```blade
<x-saas-feature>
    <h2>Multi-Tenant Organizations</h2>
    <!-- SAAS content here -->
</x-saas-feature>
```

## Security Best Practices

1. **Never expose purchase codes**: Store securely in cache/database
2. **Use HTTPS**: Always use HTTPS for license server communication
3. **Monitor logs**: Check validation logs for suspicious activity
4. **Rate limiting**: Implement rate limiting on activation endpoint
5. **Keep SDK updated**: Update SDK when security patches are available

## Troubleshooting

### License Activation Fails

**Issue**: "License server is unreachable"
- Check internet connection
- Verify `CHATON_LICENSE_SERVER_URL` is correct
- Check Cloudflare Workers status

**Issue**: "Invalid purchase code"
- Verify purchase code is correct
- Check Envato API token has correct permissions
- Ensure item is published on CodeCanyon

**Issue**: "Domain already activated"
- License is locked to another domain
- Contact support to release license

### Daily Validation Fails

**Issue**: Grace period keeps extending
- Check cron is running: `php artisan schedule:list`
- Verify scheduled command is executing: check logs

### SAAS Features Not Working

**Issue**: SAAS disabled despite Extended license
- Clear cache: `php artisan cache:clear`
- Force validation: `php artisan license:validate --force`
- Check license info: `License::getLicenseInfo()`

## Advanced Configuration

### Custom Cache Driver

```php
// config/chaton-license.php
'cache' => [
    'driver' => 'redis', // or 'file', 'database'
    'ttl' => 86400, // 24 hours
],
```

### Custom Validation Schedule

```php
// routes/console.php
Schedule::command('license:validate')->hourly(); // Validate every hour
```

### Extend Grace Period

```php
// config/chaton-license.php
'grace_period_days' => 14, // 14 days instead of 7
```

## Testing

### Mock License in Tests

```php
use Egiw\ChatonLicense\Facades\License;

// In your test
License::shouldReceive('isValid')
    ->andReturn(true);

License::shouldReceive('isSaasEnabled')
    ->andReturn(true);

// Test your code
```

### Test with Different License Types

```php
// Test Regular License
config(['chaton-license.features.saas.regular' => false]);

// Test Extended License
config(['chaton-license.features.saas.extended' => true]);
```

## Support

For issues or questions:
- Email: support@chaton.app
- Documentation: https://docs.chaton.app
- CodeCanyon: https://codecanyon.net/user/yourname

## License

This SDK is proprietary software included with ChatOn application.
