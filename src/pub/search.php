<?php

use Magento\Framework\App\Area;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManager;

require __DIR__ . '/../app/bootstrap.php';

$params[Bootstrap::INIT_PARAM_FILESYSTEM_DIR_PATHS] = array_replace_recursive(
    $params[Bootstrap::INIT_PARAM_FILESYSTEM_DIR_PATHS] ?? [],
    [
        DirectoryList::PUB => [DirectoryList::URL_PATH => ''],
        DirectoryList::MEDIA => [DirectoryList::URL_PATH => 'media'],
        DirectoryList::STATIC_VIEW => [DirectoryList::URL_PATH => 'static'],
        DirectoryList::UPLOAD => [DirectoryList::URL_PATH => 'media/upload'],
    ]
);

$params[StoreManager::PARAM_RUN_CODE] = $_SERVER[StoreManager::PARAM_RUN_CODE] ?? null;
$bootstrap = Bootstrap::create(BP, $params);

$obj = $bootstrap->getObjectManager();
$obj->get('Magento\Framework\App\State')->setAreaCode('frontend');
$obj->get('Magento\Framework\View\DesignLoader')->load(Area::PART_DESIGN);

header('Content-Type: application/json');
echo json_encode($obj->get('Web200\ElasticsuiteAutocomplete\Model\Query')->execute());
