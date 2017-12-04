<?php
namespace Antonshell\OrdersWebhooks\Observer;

use Antonshell\OrdersWebhooks\Helpers\WebhookHelper;
use Magento\Framework\Event\ConfigInterface;
use Magento\Framework\Event\InvokerInterface;

class MOrderDeleteObserver implements \Magento\Framework\Event\ObserverInterface
{
    protected $scopeConfig;

    protected $webhookHelper;

    const EVENT_NAME = 'sales_order_delete_after';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->webhookHelper = new WebhookHelper($scopeConfig,$orderRepository,$searchCriteriaBuilder,$objectManager);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $event = self::EVENT_NAME;
        $this->webhookHelper->saveWebhook($order,$event);
        return $this;
    }
}
