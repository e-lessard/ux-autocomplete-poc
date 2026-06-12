<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-products',
    description: 'Add a short description for your command',
)]
class LoadProductsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $category = new Category();
        $category->setCode('Validé');
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        for ($i = 0; $i < 500; $i++) {
            $product = new Product();
            $product->setLibelle('PROD-'.$i);
            $product->setName('Produit test numéro '.$i.' avec caractères spéciaux é,à,ù');
            $product->setCategory($category);
            $this->entityManager->persist($product);

            if ($i % 500 === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $category = $this->entityManager->find(Category::class, $category->getCode());
            }
        }

        $this->entityManager->flush();

        $io->success('Products loaded successfully.');

        return Command::SUCCESS;
    }
}
