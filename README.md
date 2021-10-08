# Magento 2 Elastisuite Autocomplete speed up

Magento 2 Module to speed autocomplete search with elasticsuite

## Features

This module use :
- Elasticsearch response
- Load the minimum magento class to display product (price and image helper)
- Return only products (with additional_attributes), categories

To improve speed I try two ways of routing :
- Default magento 2 routing way (by declaring routes.xml and use controller)
- No routing way (use a search.php file in /pub directory)

## Installation

### Composer
```
$ composer require "web200/magento-elasticsuite-autocomplete":"*"
```

### Or Github
```
git clone git@github.com:Web200/magento-elasticsuite-autocomplete.git
```

Copy
```
$MAGENTO_ROOT/app/code/Web200/ElasticsuiteAutocomplete/pub/search.php
```
in
```
$MAGENTO_ROOT/pub/
```

### Nginx specification

```
# Defaut magento installation (Nginx) protect php script execution, you need to edit your virtualhost like this :
location ~ ^/(index|get|static|errors/report|errors/404|errors/503|health_check)\.php$ {
# =>
location ~ ^/(index|get|static|errors/report|errors/404|errors/503|health_check|search)\.php$ {
```

## Use 

- Reindex Magento Catalog
```
php bin/magento indexer:reindex
```

If you clone the repository don't forget to copy /pub/search.php file (file is copied automatically if you use composer install)

```
# To test the module you only need to install it, and edit this file (by overriding in your theme module):
# smile/elasticsuite/src/module-elasticsuite-core/view/frontend/templates/search/form.mini.phtml

# Default elasticsuite module :
"url":"<?php /* @escapeNotVerified */ echo $block->getUrl('search/ajax/suggest'); ?>",
# This Module with default magento routing :
"url":"<?php /* @escapeNotVerified */ echo $block->getUrl('autocomplete'); ?>",
# This Module without magento routing : 
"url":"<?= $block->getFormViewModel()->getSearchUrl() ?>",
```

## Benchmarks

For benchmarking, I use Magento 2.4.2 with sample data and all cache are active.
Local ubuntu with mysql / elasticsearch / apache (no docker use)

Results :

* Elasticsuite last version (2.10.3) :  360ms
* This Module with default magento routing : 120ms
* This Module without magento routing : 80ms

## Wildcard

To use wildcard feature you need to active it in Store > Configuration > Elasticsuite > Autocommplete > Product > Wildcard
And you need to define new elasticsuite config file :

File : *etc/elasticsuite_indices.xml*
```
<?xml version="1.0"?>
<indices xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="urn:magento:module:Smile_ElasticsuiteCore:etc/elasticsuite_indices.xsd">
    <index identifier="catalog_product" defaultSearchType="product">
        <type name="product" idFieldName="entity_id">
            <mapping>
                <field name="name" type="text">
                    <isSearchable>1</isSearchable>
                    <isUsedInSpellcheck>1</isUsedInSpellcheck>
                    <isFilterable>1</isFilterable>
                    <defaultSearchAnalyzer>partial_custom_analyzer</defaultSearchAnalyzer>
                </field>
            </mapping>
        </type>
    </index>
</indices>
```

File : *etc/elasticsuite_analysis.xml*
```
<?xml version="1.0"?>
<analysis xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:noNamespaceSchemaLocation="urn:magento:module:Smile_ElasticsuiteCore:etc/elasticsuite_analysis.xsd">
    <filters>
        <filter name="ngram_filter_custom" type="edge_ngram" language="default">
            <min_gram>3</min_gram>
            <max_gram>8</max_gram>
        </filter>
    </filters>

    <analyzers>
        <analyzer name="partial_custom_analyzer" tokenizer="standard" language="default">
            <filters>
                <filter ref="ascii_folding" />
                <filter ref="trim" />
                <filter ref="word_delimiter" />
                <filter ref="lowercase" />
                <filter ref="elision" />
                <filter ref="standard" />
                <filter ref="ngram_filter_custom"/>
            </filters>
            <char_filters>
                <char_filter ref="html_strip"/>
            </char_filters>
        </analyzer>
    </analyzers>
</analysis>
```

