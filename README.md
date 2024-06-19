# Team23_CleanupEav

The `Team23_CleanupEav` module enables you to maintain and cleanup your eav database.

## Installation

Installation is done via composer

```shell
composer require team23/module-cleanup-eav
bin/magento module:enable Team23_CleanupEav
```

## Configuration

Some core paths are excluded from being cleaned up in database. You can add more for other modules too inside a `di.xml`
file.

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <type name="Team23\CleanupEav\Model\Config">
        <arguments>
            <argument name="excludedPaths" xsi:type="array">
                <item name="amasty_first" xsi:type="string">amasty_base/system_value/first_module_run</item>
                <item name="amasty_last" xsi:type="string">amasty_base/system_value/last_update</item>
                <item name="amasty_remove" xsi:type="string">amasty_base/system_value/remove_date</item>
            </argument>
        </arguments>
    </type>
</config>
```

## Extensibility

Extension developers can interact with the `Team23_CleanupEav` module. For more information about the Magento extension 
mechanism, see [Magento plug-ins](http://devdocs.magento.com/guides/v2.4/extension-dev-guide/plugins.html).

[The Magento dependency injection mechanism](http://devdocs.magento.com/guides/v2.4/extension-dev-guide/depend-inj.html) 
enables you to override the functionality of the `Team23_CleanupEav` module.

## CLI commands

- `eav:cleanup:media` Compares database and disk space of product images and cleans both of them.
- `eav:cleanup:config-path` Removes orphaned paths in database for core configuration table.
- `eav:cleanup:config-scope` Compare global and scope data for paths, remove the scope entry if identical.
