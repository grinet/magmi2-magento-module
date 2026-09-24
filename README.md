# Magmi Magento Module

Magento-native adapter for `grinet/magmi2-core`. Uses Magento's live PDO connection, table prefix, application root and media directory. No duplicated database password or manually entered Magento path. The standalone GUI is not required.

This first release and its Core dependency are alpha versions. The root project must permit alpha dependencies; prefer-stable keeps stable versions preferred.

Requires PHP 8.2+, Composer 2 and Magento 2.4. Tested with Magento Open Source 2.4.9. Use the PHP version required by your Magento release.

## Install in Magento

```sh
# Run in the Magento root as the deployment user.
# Required until these packages are available on Packagist:
composer config repositories.magmi-core vcs https://github.com/grinet/magmi2-core
composer config repositories.magmi-magento vcs https://github.com/grinet/magmi2-magento-module
composer config minimum-stability alpha
composer config prefer-stable true
composer require grinet/magmi2-magento:^0.1@alpha
bin/magento module:enable Grinet_Magmi2
bin/magento setup:upgrade
bin/magento cache:clean
```

For production mode, include `setup:di:compile` and your normal static-content deployment in your deployment process.

Upload import files to Magento `var/import`. Open **Catalog → Magmi Import** with the `Grinet_Magmi2::import` ACL permission. The native admin form uses Magento authentication and form keys. It processes at most 1,000 rows per request (default 100); use CLI for larger imports. It is a basic import screen, not the standalone GUI profile editor.

```sh
bin/magento magmi:import --file=products.csv --profile=Default --mode=update
# Optional Excel support:
composer require phpoffice/phpspreadsheet:^5.0
```

Profiles are stored in `var/magmi/data/config`; `Default` is created on first use. Custom plugins go in `var/magmi/plugins`. CLI files must resolve inside `var/import`, including symlink targets. After successful imports, refresh relevant Magento indexers and cache through your normal operational process.

Inject `Grinet\Magmi2\Model\ImportService` into Magento code and call `createDataPump()` or `importFile()`. Paths and the connection are resolved through Magento dependency injection. DataPump writes immediately, so callers should inspect the returned import report.

## Update

```sh
composer update grinet/magmi2-magento grinet/magmi2-core --with-all-dependencies
bin/magento setup:upgrade
bin/magento cache:clean
```

Include your normal production compile and static-content deployment steps. Configuration in `var/magmi` is preserved.

## Validation

```sh
php tests/magento-smoke.php /path/to/test-magento /path/to/core/vendor/autoload.php
```

The integration smoke test uses the native Magento object manager and connection, creates/updates a temporary disabled product and removes it afterward. It does not install the module or perform a browser test of the admin screen. Run only against a test store. Full production deployment remains a separate verification step.

## Release status

This is an **alpha release**. Import behavior has been exercised against Magento Open Source 2.4.9 and PHP 8.4; other Magento versions allowed by Composer constraints are not yet certified. Sixteen legacy plugin entries are configuration-only and cannot be enabled for import. Indexers/cache are not refreshed automatically after direct database writes. Use a test store and validate your import before production use.

License: [OSL-3.0](LICENSE).
