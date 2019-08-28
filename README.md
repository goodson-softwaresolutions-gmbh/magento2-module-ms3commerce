# Staempfli mediaSolution3 Commerce Import

## Description

This Module handles the Import from mediaSolution3 Commerce into Magento 2

## Installation

```
composer require "staempfli/magento2-module-ms3commerce": "~1.0"
```

### Create MS3 Database

```
mysql -u root
CREATE DATABASE <m2m_database>;
GRANT ALL ON <m2m_database>.* TO '<magento_user>'@'localhost'
```

if previous command does not work (ERROR: Can't find any matching row in the user table), try with `%` instead of `localhost`

```
GRANT ALL ON <m2m_database>.* TO '<magento_user>'@'%'
```

Note: In case that you have 2 different users for this database and magento database. You must give the magento user `select` rights.

```
GRANT SELECT ON <m2m_database>.* TO '<magento_user>'@'localhost';
```

## Project Setup

We have 2 setup options depending whether we install it in clean installations or in running projects.

### Clean installations

```
<magento_bin> install:setup ... --import-db-host=<m2m_host> --import-db-name=<m2m_name> --import-db-user=<m2m_user> --import-db-password=<m2m_password>
```

### Running installations

```
bin/magento module:enable Staempfli_CommerceImport
bin/magento ms3:database:config
bin/magento setup:upgrade
```


## Configuration

Once installed in Magento, we need to setup some previous configuration before we can start importing data.


### Magento Admin Configuration

0. Set Stores mapping (**Store view Scope**): `Stores > Configuration > Services > mS3 Commerce Import > Mapping`

0. Set Store Master (**Website Scope**): `Stores > Configuration > Services > mS3 Commerce Import > Mapping`

0. OPTIONAL: If you want to import categories in a different Category than the Website default, you can configure that:

    * `Catalog > Categories > Select category > Root Category`

You can find extended info about these steps here [Admin Configuration](docs/configuration/admin.md)


## Usage

You can find all available commands under the ms3 console group

* `bin/magento ms3:import --help`

### Options

* `bin/magento ms3:import [--only="..."] [--backup="..."] [--reindex="..."] [--cache="..."]`

> **--only**
> Select type for import: --only=[attributes|products|categories|validate]
>
> **--backup**
> Create Database backup before import: --backup=[true|false] (default: "true")
>
> **--reindex**
> Run reindex after import: --reindex=[true|false] (default: "true")
>
> **--cache**
> Clean cache after import: --cache=[true|false] (default: "true")


## Server Setup

### mS3Commerce dataTransfer

0. Setup dataTransfer files:

    ```
    cd shared/magento/pub
    git clone ssh://git@stash.staempfli.com:7999/ms3c/datatransfer.git dataTransfer
    mkdir -p Graphics import/mS3 dataTransfer/ext dataTransfer/uploads

    cd dataTransfer
    cp mS3CommerceStage.tmpl.php mS3CommerceStage.php
    cp runtime_config.tmpl.php runtime_config.php
    cp mS3CommerceDBAccess.tmpl.php mS3CommerceDBAccess.php
    ```

0. Set mS3 Database config:

	`vim mS3CommerceDBAccess.php`

    ```
    function MS3C_DB_ACCESS() {
            return array(
                    'ms3magento' => array(
                            'host' => 'localhost',
                            'username' => 'project_m2m',
                            'password' => 'abcdef123456',
                            'database' => 'project_m2m',
                    )
            );
    }
    ```

0. Set runtime config:

	`vim runtime_config.php`

    ```
    define('MS3C_CMS_TYPE', 'Magento');
    define('MS3C_MAGENTO_ONLY', true);
    define('MS3C_LOG_NOTIFICATION_ADDRESSES', 'magento@staempfli.com');
    define('MS3C_TASK_MAIL_RECEIVER','magento@staempfli.com');
    ```

0. Test that configuration is correct:

	* Open your browser and call [http://project_domain/dataTansfer/mS3CommerceCheck.php](http://project_domain/dataTansfer/mS3CommerceCheck.php)

### Cronjob

When running the import via cronjob on a server, you do not usually want to display any info besides error. For that reason you might want to use the option `--output-only-errors`

```
bin/magento ms3:import --output-only-errors
```

Sometimes you might also want to log everything in order to find the source of an issue. For that you can use the option `--log-debug`:

```
bin/magento ms3:import --output-only-errors --log-debug
```

## Integration tests Setup

Edit `dev/tests/integration/etc/install-config-mysql.php` with new params for m2m database setup:

```
return [
    //...
    'import-db-host' => 'localhost',
    'import-db-user' => 'project_m2m',
    'import-db-password' => 'abcdef12345',
    'import-db-name' => 'project_m2m_integration_tests',
];

```

## Troubleshooting

If you have issues while importing data, check the [issues](docs/troubleshooting/issues.md) section. If that is a known issue, you will find there how to sort it out:

* [Common import issues and how to solve them](docs/troubleshooting/issues.md)

## Requirements

* PHP: ~5.6.0|~7.0.0

## Compatibility

* Magento: 2.1

## Copyright

* (c) 2016, Stämpfli AG