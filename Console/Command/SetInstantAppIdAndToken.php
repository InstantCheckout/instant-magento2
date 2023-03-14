<?php

namespace Instant\Checkout\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Instant\Checkout\Service\DoRequest;

class SetInstantAppIdAndToken extends Command
{
    private $doRequest;

    public function __construct(DoRequest $doRequest)
    {
        $this->doRequest = $doRequest;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('set_app_id_and_token:command');
        $this->setDescription('Sets the Instant App ID and Access Token from the backend.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Hello World! Now logging command:');
        // $this->logger->debug("=== Logging from console command! ===");

        return 0;
    }
}