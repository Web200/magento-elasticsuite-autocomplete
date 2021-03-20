<?php

declare(strict_types=1);

namespace Web200\ElasticsuiteAutocomplete\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Web200\ElasticsuiteAutocomplete\Model\Render;

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
     * Render
     *
     * @var Render $render
     */
    protected $render;
    /**
     * Result json factory
     *
     * @var $resultJsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Index constructor.
     *
     * @param Render      $render
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Render $render,
        JsonFactory $resultJsonFactory
    ) {
        $this->render            = $render;
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
        $resultJson->setData($this->render->execute());

        return $resultJson;
    }
}
