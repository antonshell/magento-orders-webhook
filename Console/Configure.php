<?php
namespace Antonshell\OrdersWebhooks\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Class Configure
 * @package Antonshell\OrdersWebhooks\Console
 */
class Configure extends Command
{
    const INPUT_KEY_URL = 'webhook-url';
    const INPUT_KEY_TOKEN = 'webhook-token';

    public $options = [
        self::INPUT_KEY_URL => 'orders_webhook/webhook_url',
        self::INPUT_KEY_TOKEN => 'orders_webhook/webhook_token'
    ];

    private $configWriter;

    /**
     * Configure constructor.
     * @param WriterInterface $configWriter
     */
    public function __construct(WriterInterface $configWriter)
    {
        parent::__construct();

        $this->configWriter = $configWriter;
    }

    protected function configure()
    {
        $this->setName('orders-webhook:configure');
        $this->setDescription('Demo command line');
        $this->setDefinition([
            new InputOption(self::INPUT_KEY_URL, null, InputOption::VALUE_OPTIONAL),
            new InputOption(self::INPUT_KEY_TOKEN, null, InputOption::VALUE_OPTIONAL),
        ]);
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach($this->options as $name => $option){
            $value = $input->getOption($name);

            if($value){
                $this->configWriter->save($option, $value);
                $output->writeln("$option updated");
            }
        }

        $output->writeln("Job done");
    }
}