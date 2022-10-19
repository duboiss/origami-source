<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Service\ApplicationContext;
use App\Service\ApplicationData;
use App\Service\Setup\ConfigurationFiles;
use App\Service\Setup\EnvironmentBuilder;
use App\Service\Wrapper\OrigamiStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'origami:update',
    description: 'Updates the configuration of a previously installed environment'
)]
class UpdateCommand extends AbstractBaseCommand
{
    public function __construct(
        private ApplicationContext $applicationContext,
        private ApplicationData $applicationData,
        private EnvironmentBuilder $builder,
        private ConfigurationFiles $configuration,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->addArgument(
            'environment',
            InputArgument::OPTIONAL,
            'Name of the environment to update',
            null,
            fn () => array_map(static fn ($environment) => $environment->getName(), (array) $this->applicationData->getAllEnvironments(true))
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new OrigamiStyle($input, $output);

        try {
            $this->applicationContext->loadEnvironment($input);
            $environment = $this->applicationContext->getActiveEnvironment();

            if ($environment->isActive()) {
                throw new InvalidEnvironmentException('Unable to update a running environment.');
            }

            $question = sprintf(
                'Are you sure you want to update the <options=bold>%s</> environment?',
                $environment->getName()
            );

            if ($io->confirm($question, false)) {
                $userInputs = $this->builder->prepare($io, $environment);
                $this->configuration->install($environment, $userInputs->getSettings());

                $io->success('Environment successfully updated.');
                $io->info('If you changed the database service, please consider using the "origami database:reset" command.');
            }
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
