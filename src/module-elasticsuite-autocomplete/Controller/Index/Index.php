<?php

declare(strict_types=1);

namespace Web200\ElasticsuiteAutocomplete\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Web200\ElasticsuiteAutocomplete\Model\Query;

/**
 * Class Index
 *
 * @package   Web200\ElasticsuiteAutocomplete\Controller\Index
 * @author    Web200 <contact@web200.fr>
 * @copyright 2021 Web200
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.web200.fr/
 */
class Index implements HttpGetActionInterface
{
    /**
     * Query
     *
     * @var Query $query
     */
    protected $query;
    /**
     * Result json factory
     *
     * @var $resultJsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Index constructor.
     *
     * @param Query      $query
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Query $query,
        JsonFactory $resultJsonFactory
    ) {
        $this->query            = $query;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($this->query->execute());

        return $resultJson;
    }
}
