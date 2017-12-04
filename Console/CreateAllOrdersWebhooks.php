<?php
namespace Antonshell\OrdersWebhooks\Console;

use Antonshell\OrdersWebhooks\Helpers\WebhookHelper;
use Antonshell\OrdersWebhooks\Observer\MOrderSaveObserver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Class Configure
 * @package Antonshell\OrdersWebhooks\Console
 */
class CreateAllOrdersWebhooks extends Command
{
    private $webhookHelper;

    /**
     * Configure constructor.
     * @param WriterInterface $configWriter
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\App\State $state,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->webhookHelper = new WebhookHelper($scopeConfig,$orderRepository,$searchCriteriaBuilder,$objectManager);

        //$state->setAreaCode('frontend');
        $state->setAreaCode('adminhtml');

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('orders-webhook:create-all');
        $this->setDescription('Demo command line');
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '2048M');
        $output->writeln("Create webhooks for all orders");
        //$this->webhookHelper->createAllWebhooks();

        $ordersIds = $this->webhookHelper->getAllOrdersIds();

        $count = count($ordersIds);

        foreach ($ordersIds as $i=>$orderId) {
            $order = $this->webhookHelper->getOrderById($orderId);
            $this->webhookHelper->saveWebhook($order,MOrderSaveObserver::EVENT_NAME);
            $order = null;
            $output->writeln("( $i / $count ) Order ID #" . $orderId );
        }

        $output->writeln("Job done");
    }
}