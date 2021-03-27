# Magento 2 Elastisuite Autocomplete speed up

WIP Magento 2 Module to speed autocomplete search

## Installation

###Composer
```
$ composer require "web200/magento-elasticsuite-autocomplete":"*"
```

### Github
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
"url":"<?php /* @escapeNotVerified */ echo $block->getUrl('search.php'); ?>",
```

## Features

This module use :
- Elasticsearch response
- Load the minimum magento class to display product (price and image helper)
- Return only products and categories (no other attributes)

To improve speed I try two ways of routing :
- Default magento 2 routing way (by declaring routes.xml and use controller)
- No routing way (use a search.php file in /pub directory)

## Benchmarks

For benchmarking, I use Magento 2.4.2 with sample data and all cache are active.
Local ubuntu with mysql / elasticsearch / apache (no docker use)

Results :

* Elasticsuite last version (2.10.3) :  360ms
* This Module with default magento routing : 120ms
* This Module without magento routing : 80ms

