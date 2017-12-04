<?php
namespace Antonshell\OrdersWebhooks\Helpers;

use Antonshell\OrdersWebhooks\Observer\MOrderSaveObserver;
use Magento\Framework\Event\ConfigInterface;
use Magento\Framework\Event\InvokerInterface;
use ReflectionClass;

class WebhookHelper
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    protected $objectManager;

    protected $connection;
    protected $webhooksTable;

    const WEBHOOK_URL_PARAM = 'orders_webhook/webhook_url';
    const TABLE_NAME = 'orders_webhooks_items';

    const STATUS_QUEUED = 0;
    const STATUS_SENT = 1;
    const STATUS_ERROR = 2;

    private static $statusLabels = [
        self::STATUS_QUEUED => 'Queued',
        self::STATUS_SENT => 'Sent',
        self::STATUS_ERROR => 'Error',
    ];

    /**
     * WebhookHelper constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->objectManager = $objectManager;

        $resources = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Magento\Framework\App\ResourceConnection');

        $this->connection = $resources->getConnection();
        $this->webhooksTable = $resources->getTableName(self::TABLE_NAME);
    }

    /**
     * @param $order
     * @param $event
     */
    public function saveWebhook($order,$event)
    {
        $webhookData = $this->getWebhookData($order);
        $webhookUrl = $this->getWebhookUrl();
        $row = $this->mapWebhookData($event,$webhookUrl,$webhookData, $order);

        // delete old webhooks for specific order
        /*$sql = "DELETE FROM " . $this->webhooksTable . " WHERE order_id= :order_id";
        $binds = ['order_id' => $row['order_id']];
        $this->connection->query($sql,$binds);*/

        // build insert query
        $fields = array_keys($row);

        $values = '';
        foreach ($fields as $field){
            $values .= ':' . $field . ', ';
        }

        $values = rtrim($values, ', ');
        $fields = implode(', ', $fields);

        // insert new webhook data
        $sql = "INSERT INTO " . $this->webhooksTable . "(" . $fields . ") VALUES (" . $values . ")";
        $this->connection->query($sql, $row);
    }

    /**
     * @return mixed
     */
    public function getWebhooksQueue()
    {
        $sql = "SELECT * FROM " . $this->webhooksTable . " WHERE status != :status";
        $binds = [ 'status' => self::STATUS_SENT ];
        $rows = $this->connection->fetchAll($sql,$binds);

        return $rows;
    }


    public function createAllWebhooks()
    {
        $orders = $this->getAllOrders();

        foreach ($orders as $order) {
            $this->saveWebhook($order,MOrderSaveObserver::EVENT_NAME);
        }
    }

    /**
     * @return array
     */
    public function getAllOrdersIds()
    {
        $ordersIds = [];
        $sql = "SELECT entity_id FROM `sales_order`";
        $rows = $this->connection->fetchAll($sql);

        foreach ($rows as $row) {
            $ordersIds[] = $row['entity_id'];
        }

        return $ordersIds;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getOrderById($id){
        $modelClassName = 'Magento\Sales\Model\Order';
        $instance = $this->objectManager->create($modelClassName);
        return $instance->load($id);
    }

    /**
     * @return \Magento\Sales\Api\Data\OrderInterface[]
     */
    public function getAllOrders(){
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $orders = $this->orderRepository->getList($searchCriteria);
        return $orders->getItems();
    }

    /**
     * @param $id
     * @param $status
     * @param $response
     */
    public function updateWebhook($id,$status,$response){
        $sql = "UPDATE " . $this->webhooksTable . " SET status = :status, sent_at = :sent_at, response = :response WHERE id = :id";
        $binds = [
            'status' => $status,
            'sent_at' => date('Y-m-d H:i:s'),
            'response' => $response,
            'id' => $id,
        ];
        $this->connection->query($sql, $binds);
    }

    /**
     * @param $webhook
     * @return string
     */
    public function sendWebhook($webhook)
    {
        $url = $webhook['url'];

        $data = json_decode($webhook['data'],true);
        $data['webhook_meta'] = [
            'id' => $webhook['id'],
            'event' => $webhook['event'],
            'order_id' => $webhook['order_id'],
            'url' => $webhook['url'],
            'created_at' => $webhook['created_at'],
            'verification_token' => $this->getWebhookToken(),
        ];
        $data = json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        $result = json_decode($response,true);

        if(isset($result['success']) && $result['success'] === 'true'){
            $status = WebhookHelper::STATUS_SENT;
        }
        else{
            $status = WebhookHelper::STATUS_ERROR;
        }

        $this->updateWebhook($webhook['id'], $status, $response);

        $message = '#' . $webhook['id'] . ' - ' . self::$statusLabels[$status];
        return $message;
    }

    public function clearWebhooks()
    {
        //delete webhooks that was sent successfully earlier then 1 week ago
        $sql = "DELETE FROM " . $this->webhooksTable . " WHERE  status = :status AND sent_at <= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK),'%Y-%m-%d')";
        $binds = [ 'status' => self::STATUS_SENT ];
        $this->connection->query($sql, $binds);
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getWebhookUrl(){
        $sql = "SELECT * FROM core_config_data WHERE path = 'orders_webhook/webhook_url'";
        $row = $this->connection->fetchAll($sql);

        if(!isset($row[0]['value'])){
            throw new \Exception('Can\'t get webhook url');
        }

        return $row[0]['value'];
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getWebhookToken(){
        $sql = "SELECT * FROM core_config_data WHERE path = 'orders_webhook/webhook_token'";
        $row = $this->connection->fetchAll($sql);

        if(!isset($row[0]['value'])){
            throw new \Exception('Can\'t get webhook Token');
        }

        return $row[0]['value'];
    }

    /**
     * @param $order
     * @return string
     */
    private function getWebhookData($order){
        $orderData = $order->getData();
        $items = $order->getAllVisibleItems();

        $webhookData = $orderData;
        unset($webhookData['items']);
        unset($webhookData['store_name']);

        foreach($items as $item){
            $itemData = $item->getData();
            $itemData['item_id'] = $item->getItemId();

            $webhookData['items'][] = $itemData;
        }

        $webhookData = json_encode($webhookData);

        return $webhookData;
    }

    /**
     * @param $event
     * @param $webhookUrl
     * @param $webhookData
     * @param $order
     * @return array
     */
    private function mapWebhookData($event, $webhookUrl, $webhookData, $order)
    {
        $row = [
            'event' => $event,
            'url' => $webhookUrl,
            'data' => $webhookData,
            'response' => '',
            'created_at' => date('Y-m-d H:i:s'),
            'sent_at' => '',
            'status' => self::STATUS_QUEUED,
            'order_id' => $order->getId(),
        ];

        return $row;
    }
}