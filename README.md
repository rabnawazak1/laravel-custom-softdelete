# Laravel Custom SoftDelete

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rabnawazak1/laravel-custom-softdelete.svg?style=flat-square)](https://packagist.org/packages/rabnawazak1/laravel-custom-softdelete)
[![Total Downloads](https://img.shields.io/packagist/dt/rabnawazak1/laravel-custom-softdelete.svg?style=flat-square)](https://packagist.org/packages/rabnawazak1/laravel-custom-softdelete)
[![License](https://img.shields.io/packagist/l/rabnawazak1/laravel-custom-softdelete.svg?style=flat-square)](LICENSE.md)
[![PHP Version](https://img.shields.io/packagist/php-v/rabnawazak1/laravel-custom-softdelete.svg?style=flat-square)](https://packagist.org/packages/rabnawazak1/laravel-custom-softdelete)

A Laravel package that provides a custom soft delete implementation using three dedicated columns — `is_deleted`, `deleted_at`, and `deleted_by` — giving you full audit-trail awareness of *who* deleted a record, not just *when*.

Unlike Laravel's built-in `SoftDeletes` trait which relies solely on a nullable `deleted_at` column, this package uses a boolean `is_deleted` flag as the primary filter, making it more explicit, index-friendly, and compatible with legacy database schemas or external systems that expect a dedicated status flag.

---

## Features

- Three-column soft delete: `is_deleted`, `deleted_at`, `deleted_by`
- Automatically captures the authenticated user's ID on delete
- Global query scope — deleted records are filtered out automatically
- Supports `withTrashed()`, `onlyTrashed()`, `withoutTrashed()` query macros
- `restore()` resets all three columns cleanly
- `forceDelete()` performs a real `DELETE FROM` on the database
- Column names are overridable per model via constants
- Artisan command to generate migrations for any table
- Works with any Eloquent model — apply to as many tables as you want
- No conflicts with Laravel's native `SoftDeletes` trait

---

## Requirements

| Dependency      | Version        |
|-----------------|----------------|
| PHP             | ^8.1           |
| Laravel         | ^10.0 \| ^11.0 |

---

## Installation

Install the package via Composer:

```bash
composer require rabnawazak1/laravel-custom-softdelete
```

The package uses Laravel's auto-discovery. The service provider will be registered automatically — no manual setup needed.

---

## Database Setup

### Option A — Artisan Command (Recommended)

Use the built-in Artisan command to generate a migration for any table:

```bash
php artisan softdelete:add patients
php artisan softdelete:add appointments
php artisan softdelete:add invoices
```

This generates a migration that adds the three columns to your specified table. Run it afterwards:

```bash
php artisan migrate
```

### Option B — Manual Migration

If you prefer to write migrations yourself, add the following columns to your table:

```php
Schema::table('your_table', function (Blueprint $table) {
    $table->boolean('is_deleted')->default(0)->index()->after('id');
    $table->timestamp('deleted_at')->nullable()->after('is_deleted');
    $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
});
```

---

## Usage

### 1. Apply the Trait to a Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Rabnawazak1\CustomSoftDelete\Traits\CustomSoftDeletes;

class Patient extends Model
{
    use CustomSoftDeletes;
}
```

That's it. The global scope is applied automatically.

---

### 2. Soft Deleting a Record

```php
$patient = Patient::find(1);
$patient->delete();

// The following columns are now set:
// is_deleted  => 1
// deleted_at  => 2025-04-27 10:30:00
// deleted_by  => 3  (Auth::id() at the time of deletion)
```

---

### 3. Querying Records

By default, all queries exclude soft-deleted records automatically:

```php
// Only active (non-deleted) records
Patient::all();
Patient::where('status', 'active')->get();

// Include soft-deleted records
Patient::withTrashed()->get();
Patient::withTrashed()->where('id', 1)->first();

// Only soft-deleted records
Patient::onlyTrashed()->get();

// Explicitly exclude soft-deleted (useful after withTrashed())
Patient::withoutTrashed()->get();
```

---

### 4. Restoring a Soft-Deleted Record

```php
$patient = Patient::withTrashed()->find(1);
$patient->restore();

// Resets:
// is_deleted  => 0
// deleted_at  => null
// deleted_by  => null
```

---

### 5. Permanently Deleting a Record

```php
$patient = Patient::withTrashed()->find(1);
$patient->forceDelete();
// Issues a real DELETE FROM patients WHERE id = 1
```

---

### 6. Checking Delete Status

```php
$patient = Patient::withTrashed()->find(1);

$patient->isSoftDeleted(); // true / false
$patient->trashed();       // alias for isSoftDeleted()
```

---

## Customizing Column Names

If your table uses different column names, override them in your model using constants:

```php
class Patient extends Model
{
    use CustomSoftDeletes;

    const IS_DELETED = 'is_removed';      // default: 'is_deleted'
    const DELETED_AT = 'removed_at';      // default: 'deleted_at'
    const DELETED_BY = 'removed_by';      // default: 'deleted_by'
}
```

All internal logic — scopes, queries, restore — will automatically use your custom column names.

---

## Artisan Command Reference

```bash
php artisan softdelete:add {table}
```

| Argument | Description                                |
|----------|--------------------------------------------|
| `table`  | The database table to add the columns to   |

**Example:**

```bash
php artisan softdelete:add medical_records
# Generates: database/migrations/xxxx_xx_xx_add_soft_delete_to_medical_records_table.php
```

---

## How It Works

This package deliberately avoids extending Laravel's built-in `SoftDeletes` trait because the two approaches are architecturally incompatible:

| Aspect              | Laravel `SoftDeletes`             | This Package                          |
|---------------------|-----------------------------------|---------------------------------------|
| Primary filter      | `WHERE deleted_at IS NULL`        | `WHERE is_deleted = 0`                |
| Delete mechanism    | Sets `deleted_at` to now          | Sets all 3 columns                    |
| Audit trail         | Timestamp only                    | Timestamp + User ID                   |
| Restore             | Nullifies `deleted_at`            | Resets all 3 columns                  |
| Column flexibility  | One column, fixed behavior        | All column names overridable          |

Internally, this package:

1. Registers a `CustomSoftDeletingScope` (implements `Illuminate\Database\Eloquent\Scope`) that applies `WHERE is_deleted = 0` globally
2. Hooks into Eloquent's `deleting` event, cancels the default `DELETE` query (by returning `false`), and runs an `UPDATE` instead
3. Exposes `withTrashed()`, `onlyTrashed()`, and `withoutTrashed()` as Builder macros via the scope's `extend()` method — matching Laravel's API exactly

---

## Testing

```bash
composer test
```

---

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for what has changed in each release.

---

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on the process for submitting pull requests.

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Commit your changes: `git commit -m 'Add some feature'`
4. Push to the branch: `git push origin feature/my-feature`
5. Open a pull request

---

## Security

If you discover any security-related issues, please email **rabnawazabdulkhaliq@gmail.com** instead of using the issue tracker.

---

## Credits

- **[Rabnawaz](https://github.com/rabnawazak1)** — author and maintainer
- Inspired by the architecture of [Laravel's own SoftDeletes](https://laravel.com/docs/eloquent#soft-deleting) and [Spatie's Laravel Permission](https://github.com/spatie/laravel-permission) package

---

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.
