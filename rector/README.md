# Rector

[Rector](https://github.com/rectorphp/rector/) helps to instantly upgrades and refactors PHP code. The `rector.php` can interact as starting point when refactoring or upgrading [Flynt](https://github.com/flyntwp/flynt/) projects to newer PHP versions.

## Usage

1. Download the `rector` folder and move the `rector.php` inside your theme folder.
2. Install `require-dev` packages from `composer.json` file in to your theme.
3. Adjust folders or files to skip `rector.php`

```php
$rectorConfig->skip([
    __DIR__ . '/vendor',
    __DIR__ . '/node_modules',
]);
```

4. Modify `rector.php` to your needs, see also [Rector](https://github.com/rectorphp/rector/) for more details.

5. Dry run Rector:

```shell
# wp-content/themes/flynt
vendor/bin/rector process ../PATH_TO_FOLDER_WITH_PHP_FILES --dry-run
```

6. Run Rector:

```shell
# wp-content/themes/flynt
vendor/bin/rector process ../PATH_TO_FOLDER_WITH_PHP_FILES
```
