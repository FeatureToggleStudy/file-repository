<?php declare(strict_types=1);

namespace App\Command\Authentication;

use App\Domain\Authentication\ActionHandler\TokenGenerationHandler;
use App\Domain\Authentication\Exception\ValidationException;
use App\Domain\Authentication\Factory\Context\SecurityContextFactory;
use App\Domain\Authentication\Form\AuthForm;
use App\Domain\Authentication\Form\TokenDetailsForm;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateTokenCommand extends Command
{
    public const NAME = 'auth:create-token';

    /**
     * @var TokenGenerationHandler
     */
    private $handler;

    /**
     * @var SecurityContextFactory
     */
    private $authFactory;

    public function __construct(TokenGenerationHandler $handler, SecurityContextFactory $authFactory)
    {
        $this->handler     = $handler;
        $this->authFactory = $authFactory;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('auth:create-token')
            ->setDescription('Creates an authentication token')
            ->addOption('roles', null, InputOption::VALUE_REQUIRED)
            ->addOption('tags', null, InputOption::VALUE_REQUIRED)
            ->addOption('mimes', null, InputOption::VALUE_REQUIRED)
            ->addOption('max-file-size', null, InputOption::VALUE_REQUIRED)
            ->addOption('expires', null, InputOption::VALUE_REQUIRED, 'Example: 2020-05-01 or +10 years')
            ->setHelp('Allows to generate a token you can use later to authenticate in application for a specific thing');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $form = new AuthForm();
        $form->data = new TokenDetailsForm();
        $form->data->tags               = $this->getMultipleValueOption($input, 'tags');
        $form->data->allowedMimeTypes   = $this->getMultipleValueOption($input, 'mimes');
        $form->data->maxAllowedFileSize = (int) $input->getOption('max-file-size');
        $form->expires                  = $input->getOption('expires');
        $form->roles                    = $this->getMultipleValueOption($input, 'roles');

        $this->debug('========================', $output);
        $this->debug('Form:', $output);

        foreach ($form->data->tags as $tag) {
            $this->debug(' [Tag] -> ' . $tag, $output);
        }

        foreach ($form->data->allowedMimeTypes as $mimes) {
            $this->debug(' [Mime] -> ' . $mimes, $output);
        }

        foreach ($form->roles as $role) {
            $this->debug(' [Role] -> ' . $role, $output);
        }

        try {
            $response = $this->handler->handle(
                $form,
                $this->authFactory->createShellContext()
            );
        } catch (ValidationException $validationException) {
            $this->debug('========================', $output);
            $this->debug('Validation error:', $output);

            foreach ($validationException->getFields() as $field => $errors) {
                $this->debug(' Field "' . $field . '":', $output);

                foreach ($errors as $error) {
                    $this->debug(' - ' . $error, $output);
                }
            }
        }

        $this->debug("\nResponse:", $output);
        $this->debug('========================', $output);
        $this->debug(json_encode($response, JSON_PRETTY_PRINT), $output);

        if (!$output->isVerbose()) {
            $output->writeln($response['tokenId']);
        }
    }

    private function debug(string $message, OutputInterface $output): void
    {
        if (!$output->isVerbose()) {
            return;
        }

        $output->writeln($message);
    }

    private function getMultipleValueOption(InputInterface $input, string $optionName): array
    {
        $values = explode(',', $input->getOption($optionName) ?: '');
        $values = array_filter($values);

        return $values;
    }
}
