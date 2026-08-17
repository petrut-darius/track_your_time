<?php

namespace App\Command;

use App\Message\TypeActionMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

#[AsCommand(
    name: 'thepdi:type-action',
    description: 'When this command is called it scrapes the types, and created or updated entities based on what it scrapes.',
)]
class TypeActionCommand extends Command
{
    public function __construct(private MessageBusInterface $bus)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('delay', InputArgument::OPTIONAL, 'Delay in minutes before running the scraper')
            ->addOption('hour', null, InputOption::VALUE_NONE, 'The delay arg to be calculated in hours.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $delay = $input->getArgument("delay");
        
        if ($input->getOption('hour')) {
            $delay = $delay * 60;
        }

        if ($delay) {
            $io->note(sprintf('Your scraper will be called in %s minute(s) from now!', $delay));
        }else{
            $delay = 0;
        }

        $this->bus->dispatch(new TypeActionMessage, [ new DelayStamp($delay * 60 * 1000) ]);
        

        return Command::SUCCESS;
    }
}
