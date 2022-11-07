# D3 Webauthn / FIDO2 Tests

## Requirements

Both unit and acceptance tests require OXID Testing Library installed.
See https://github.com/OXID-eSales/testing_library.

### Configuration

Please install the packages listed in the composer.json in "require-dev". Unfortunately Composer does not provide an automatic installation.

Here is an example of Testing Library configuration file `oxideshop/test_config.yml`

```
# This file is auto-generated during the composer install
mandatory_parameters:
    shop_path: /var/www/oxideshop/source
    shop_tests_path: /var/www/oxideshop/tests
    partial_module_paths: d3/oxwebauthn
optional_parameters:
    shop_url: null
    shop_serial: ''
    enable_varnish: false
    is_subshop: false
    install_shop: false
    remote_server_dir: null
    shop_setup_path: null
    restore_shop_after_tests_suite: false
    test_database_name: null
    restore_after_acceptance_tests: false
    restore_after_unit_tests: false
    tmp_path: /tmp/oxid_test_library/
    database_restoration_class: DatabaseRestorer
    activate_all_modules: false
    run_tests_for_shop: false
    run_tests_for_modules: true
    screen_shots_path: null
    screen_shots_url: null
    browser_name: firefox
    selenium_server_ip: 127.0.0.1
    selenium_server_port: '4444'
    additional_test_paths: null
```

## Unit Tests

To execute unit tests run the following:

```
cd /var/www/oxideshop/
PHPBIN=/usr/bin/php7.4 ./vendor/bin/runtests
```
