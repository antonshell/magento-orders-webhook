<?php
namespace Antonshell\OrdersWebhooks\Console;

use Antonshell\OrdersWebhooks\Helpers\WebhookHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Class Configure
 * @package Antonshell\OrdersWebhooks\Console
 */
class SendWebhooks extends Command
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
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->webhookHelper = new WebhookHelper($scopeConfig,$orderRepository,$searchCriteriaBuilder,$objectManager);

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('orders-webhook:send');
        $this->setDescription('Send webhooks');
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('orders-webhook:send');

        $webhooks = $this->webhookHelper->getWebhooksQueue();

        foreach($webhooks as $webhook){
            if($webhook['event'] == 'sales_order_delete_after'){
                continue;
            }

            $message =  $this->webhookHelper->sendWebhook($webhook);
            $output->writeln($message);
        }

        $output->writeln("Job done");
    }
}