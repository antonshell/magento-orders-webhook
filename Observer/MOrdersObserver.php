<?php
namespace MageCode\OrdersWebhooks\Observer;

class MOrdersObserver implements \Magento\Framework\Event\ObserverInterface
{
    /** @var \Magento\Framework\Logger\Monolog */
    protected $logger;
    protected $scopeConfig;

    public function __construct(
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $loggerInterface;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $webhookUrl = $this->scopeConfig->getValue(
            'orders_webhook/webhook_url',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $order = $observer->getEvent()->getOrder();
        $orderData = $order->getData();
        $items = $order->getAllVisibleItems();

        $this->logger->debug( 'MOrdersObserver - ' . date('Y-m-d H:i:s') );

        $webhookData = $orderData;
        unset($webhookData['items']);

        foreach($items as $item){
            $itemData = $item->getData();
            $itemData['item_id'] = $item->getItemId();

            $webhookData['items'][] = $itemData;
        }

        $webhookData = json_encode($webhookData);

        $url = 'http://local9round.com/integrations/my-orders/shopify?XDEBUG_SESSION_START=1';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $webhookData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);

       file_put_contents(__DIR__ . '/log.txt',date('Y-m-d H:i:s') . "\n", FILE_APPEND);
       return $this;
    }
}
