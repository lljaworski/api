<?php

declare(strict_types=1);

namespace App\Demo\Command;

use App\Demo\Service\CompanyGenerator;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'demo:generate-companies',
    description: 'Generate demo companies for testing and presentation purposes',
    aliases: ['demo:companies']
)]
class GenerateCompaniesCommand extends Command
{
    public function __construct(
        private readonly CompanyGenerator $companyGenerator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('count', InputArgument::REQUIRED, 'Number of companies to generate')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be created without actually creating companies')
            ->addOption('max-attempts', null, InputOption::VALUE_OPTIONAL, 'Maximum attempts to generate unique company name', 10)
            ->setHelp(
                <<<'EOF'
The <info>demo:generate-companies</info> command creates demo company records with realistic Polish business data.

<info>Usage examples:</info>

  # Generate 25 demo companies
  <info>php bin/console demo:generate-companies 25</info>

  # Preview what would be created (dry run)
  <info>php bin/console demo:generate-companies 10 --dry-run</info>

  # Generate with custom retry attempts
  <info>php bin/console demo:generate-companies 50 --max-attempts=5</info>

<info>Features:</info>
- Realistic Polish company names using Faker
- Valid Polish NIP (tax ID) numbers with proper checksum
- Polish addresses with valid postal codes
- Complete company data including contact information
- EU VAT numbers and EORI numbers for international companies
- Progress bar showing creation status
- Automatic retry on company name conflicts
- Continues on duplicate names (doesn't fail)
- Uses CQRS CreateCompanyCommand for proper integration

<info>Generated company examples:</info>
- Kowalski Solutions Sp. z o.o.
- Nowak Industries S.A.
- TechPol Services Sp. j.

<info>Generated data includes:</info>
- Company identification (NIP, EORI, VAT numbers)
- Primary and correspondence addresses
- Contact information (email, phone)
- Tax compliance fields (taxpayer status, markers)
- Role and share percentage information
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $count = (int) $input->getArgument('count');
        $isDryRun = $input->getOption('dry-run');
        $maxAttempts = (int) ($input->getOption('max-attempts') ?? 10);

        // Validate input
        if ($count <= 0) {
            $io->error('Count must be a positive number.');
            return Command::FAILURE;
        }

        if ($count > 500) {
            $io->error('Maximum 500 companies can be generated at once for safety.');
            return Command::FAILURE;
        }

        // Show summary
        $io->title('Demo Company Generator');
        $io->section('Configuration');
        $io->definitionList(
            ['Companies to create' => $count],
            ['Data locale' => 'Polish (pl_PL)'],
            ['Features' => 'Realistic names, valid NIP numbers, Polish addresses'],
            ['Mode' => $isDryRun ? 'DRY RUN (no actual creation)' : 'CREATE COMPANIES'],
            ['Max retry attempts' => $maxAttempts]
        );

        if ($isDryRun) {
            $io->note('This is a dry run. No companies will actually be created.');
            $this->showDryRunPreview($io, $count);
            return Command::SUCCESS;
        }

        if (!$io->confirm('Do you want to proceed with company creation?', true)) {
            $io->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        // Create companies with progress bar
        $io->section('Creating Companies');
        $progressBar = new ProgressBar($output, $count);
        $progressBar->setFormat('verbose');
        $progressBar->start();

        $created = 0;
        $skipped = 0;
        $failed = 0;
        $createdCompanies = [];

        for ($i = 0; $i < $count; $i++) {
            $attempt = 0;
            $companyCreated = false;

            while ($attempt < $maxAttempts && !$companyCreated) {
                try {
                    $result = $this->companyGenerator->createCompany();
                    
                    $createdCompanies[] = $result;
                    $created++;
                    $companyCreated = true;
                    
                } catch (UniqueConstraintViolationException $e) {
                    $attempt++;
                    if ($attempt >= $maxAttempts) {
                        $skipped++;
                        break;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $io->newLine();
                    $io->warning("Failed to create company: " . $e->getMessage());
                    break;
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        // Show results
        $this->showResults($io, $created, $skipped, $failed, $createdCompanies);

        return Command::SUCCESS;
    }

    private function showDryRunPreview(SymfonyStyle $io, int $count): void
    {
        $io->section('Preview (first 5 companies)');
        
        $previewCount = min(5, $count);
        $rows = [];
        
        for ($i = 0; $i < $previewCount; $i++) {
            $companyData = $this->companyGenerator->generateCompanyData();
            
            $rows[] = [
                $i + 1,
                $companyData['name'],
                $companyData['taxId'],
                $companyData['email'],
                $companyData['addressLine1'] ?? 'N/A',
                $companyData['phoneNumber'] ?? 'N/A'
            ];
        }
        
        $io->table(['#', 'Company Name', 'NIP', 'Email', 'Address', 'Phone'], $rows);
        
        if ($count > 5) {
            $io->note(sprintf('... and %d more companies would be created', $count - 5));
        }

        // Show sample of additional fields that would be generated
        $io->section('Sample Additional Data Fields');
        $sampleData = $this->companyGenerator->generateCompanyData();
        $io->definitionList(
            ['EORI Number' => $sampleData['eoriNumber'] ?? 'Not generated'],
            ['EU VAT Number' => $sampleData['vatRegNumberEu'] ?? 'Not generated'],
            ['Taxpayer Status' => $sampleData['taxpayerStatus']],
            ['Role' => $sampleData['role'] ?? 'Not assigned'],
            ['Share Percentage' => $sampleData['sharePercentage'] ? $sampleData['sharePercentage'] . '%' : 'Not set'],
            ['Correspondence Address' => $sampleData['correspondenceAddressLine1'] ?? 'Same as primary'],
            ['GLN Number' => $sampleData['gln'] ?? 'Not generated']
        );
    }

    private function showResults(SymfonyStyle $io, int $created, int $skipped, int $failed, array $createdCompanies): void
    {
        $io->section('Results');
        
        $io->definitionList(
            ['✅ Successfully created' => $created],
            ['⏭️  Skipped (duplicate name)' => $skipped],
            ['❌ Failed' => $failed]
        );

        if ($created > 0) {
            $io->success(sprintf('Successfully created %d demo companies!', $created));
            
            // Show sample of created companies
            if (count($createdCompanies) > 0) {
                $io->section('Sample Created Companies');
                $sampleCompanies = array_slice($createdCompanies, 0, 5);
                $rows = [];
                
                foreach ($sampleCompanies as $company) {
                    $rows[] = [
                        $company['id'],
                        $company['name'],
                        $company['taxId'],
                        $company['email']
                    ];
                }
                
                $io->table(['ID', 'Company Name', 'NIP', 'Email'], $rows);
                
                if (count($createdCompanies) > 5) {
                    $io->note(sprintf('... and %d more companies were created', count($createdCompanies) - 5));
                }
            }
        }

        if ($skipped > 0) {
            $io->warning(sprintf('%d companies were skipped due to name conflicts after maximum retry attempts.', $skipped));
        }

        if ($failed > 0) {
            $io->error(sprintf('%d companies failed to create due to errors.', $failed));
        }

        if ($created > 0) {
            $io->section('Next Steps');
            $io->text([
                'You can now:',
                '• View companies via API: <info>GET /api/companies</info>',
                '• Test company operations with realistic Polish business data',
                '• Use the generated data for development and testing purposes'
            ]);
        }
    }
}