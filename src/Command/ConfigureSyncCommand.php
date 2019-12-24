<?php

namespace App\Command;

use App\Client\Google\GoogleClient;
use App\Client\Google\InvalidGoogleTokenException;
use Google_Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class ConfigureSyncCommand extends Command
{
    /** @var string */
    public static $defaultName = 'sync:configure';

    /** @var GoogleClient */
    private $googleClient;

    /** @var string */
    private $googleDomain;

    public function __construct(
        GoogleClient $googleClient,
        string $googleDomain
    ) {
        $this->googleClient = $googleClient;
        $this->googleDomain = $googleDomain;
        parent::__construct();
    }

    public function configure()
    {
        $this->setDescription('Configures the utility so it can sync member lists');
        $this->setHelp(sprintf('An interactive command that prompts for all configuration needed to sync member lists via the %s command.', RunSyncCommand::getDefaultName()));

        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Forces the configuration prompts whether or not values are currently set.'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->isGoogleClientConfigured() || $input->getOption('force')) {
            $io->block(sprintf('This utility requires a valid token for authentication with your Google account (%s). Please visit the following URL in a web browser and paste the provided authentication code into the prompt below.', $this->googleDomain));
            $io->writeln($this->googleClient->createAuthUrl().PHP_EOL);

            $authCode = trim($io->ask('Paste the auth code here:'));

            if (!$authCode) {
                $io->error('A Google authentication code must be provided.');

                return 1;
            }

            $this->googleClient->setAuthCode($authCode);

            return 0;
        }
    }

    /**
     * Determines whether the Google Client can be initialized without error.
     *
     * @return bool
     *
     * @throws Google_Exception
     */
    private function isGoogleClientConfigured(): bool
    {
        try {
            $this->googleClient->initialize();
        } catch (InvalidGoogleTokenException | FileNotFoundException $e) {
            return false;
        }

        return true;
    }
}
