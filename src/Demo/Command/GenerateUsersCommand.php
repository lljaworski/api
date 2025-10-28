<?php

declare(strict_types=1);

namespace App\Demo\Command;

use App\Demo\Service\UserGenerator;
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
    name: 'demo:generate-users',
    description: 'Generate demo users for testing and presentation purposes',
    aliases: ['demo:users']
)]
class GenerateUsersCommand extends Command
{
    public function __construct(
        private readonly UserGenerator $userGenerator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('count', InputArgument::REQUIRED, 'Number of users to generate')
            ->addOption('role', 'r', InputOption::VALUE_OPTIONAL, 'Specific role to assign (ROLE_USER, ROLE_ADMIN)', null)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be created without actually creating users')
            ->addOption('max-attempts', null, InputOption::VALUE_OPTIONAL, 'Maximum attempts to generate unique username', 10)
            ->setHelp(
                <<<'EOF'
The <info>demo:generate-users</info> command creates demo user accounts with random usernames and roles.

<info>Usage examples:</info>

  # Generate 50 demo users with random roles
  <info>php bin/console demo:generate-users 50</info>

  # Generate 20 users with specific role
  <info>php bin/console demo:generate-users 20 --role=ROLE_USER</info>

  # Preview what would be created (dry run)
  <info>php bin/console demo:generate-users 10 --dry-run</info>

<info>Features:</info>
- Random usernames using adjective + animal + number pattern
- Fixed password: admin123!
- Progress bar showing creation status
- Automatic retry on username conflicts
- Continues on duplicate usernames (doesn't fail)
- Uses CQRS CreateUserCommand for proper integration

<info>Generated usernames examples:</info>
- happy_tiger_123
- brave_eagle_456
- swift_wolf_789
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $count = (int) $input->getArgument('count');
        $specificRole = $input->getOption('role');
        $isDryRun = $input->getOption('dry-run');
        $maxAttempts = (int) ($input->getOption('max-attempts') ?? 10);

        // Validate input
        if ($count <= 0) {
            $io->error('Count must be a positive number.');
            return Command::FAILURE;
        }

        if ($count > 1000) {
            $io->error('Maximum 1000 users can be generated at once for safety.');
            return Command::FAILURE;
        }

        if ($specificRole && !in_array($specificRole, ['ROLE_USER', 'ROLE_ADMIN'], true)) {
            $io->error('Role must be either ROLE_USER or ROLE_ADMIN.');
            return Command::FAILURE;
        }

        // Show summary
        $io->title('Demo User Generator');
        $io->section('Configuration');
        $io->definitionList(
            ['Users to create' => $count],
            ['Password' => 'admin123!'],
            ['Role assignment' => $specificRole ?? 'Random (80% ROLE_USER, 20% ROLE_ADMIN)'],
            ['Mode' => $isDryRun ? 'DRY RUN (no actual creation)' : 'CREATE USERS'],
            ['Max retry attempts' => $maxAttempts]
        );

        if ($isDryRun) {
            $io->note('This is a dry run. No users will actually be created.');
            $this->showDryRunPreview($io, $count, $specificRole);
            return Command::SUCCESS;
        }

        if (!$io->confirm('Do you want to proceed with user creation?', true)) {
            $io->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        // Create users with progress bar
        $io->section('Creating Users');
        $progressBar = new ProgressBar($output, $count);
        $progressBar->setFormat('verbose');
        $progressBar->start();

        $created = 0;
        $skipped = 0;
        $failed = 0;
        $createdUsers = [];

        for ($i = 0; $i < $count; $i++) {
            $attempt = 0;
            $userCreated = false;

            while ($attempt < $maxAttempts && !$userCreated) {
                try {
                    $username = $this->userGenerator->generateUsername();
                    $roles = $specificRole ? [$specificRole] : $this->userGenerator->generateRoles();
                    
                    $result = $this->userGenerator->createUser($username, $roles);
                    
                    $createdUsers[] = $result;
                    $created++;
                    $userCreated = true;
                    
                } catch (UniqueConstraintViolationException $e) {
                    $attempt++;
                    if ($attempt >= $maxAttempts) {
                        $skipped++;
                        break;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $io->newLine();
                    $io->warning("Failed to create user: " . $e->getMessage());
                    break;
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        // Show results
        $this->showResults($io, $created, $skipped, $failed, $createdUsers);

        return Command::SUCCESS;
    }

    private function showDryRunPreview(SymfonyStyle $io, int $count, ?string $specificRole): void
    {
        $io->section('Preview (first 5 users)');
        
        $previewCount = min(5, $count);
        $rows = [];
        
        for ($i = 0; $i < $previewCount; $i++) {
            $username = $this->userGenerator->generateUsername();
            $roles = $specificRole ? [$specificRole] : $this->userGenerator->generateRoles();
            
            $rows[] = [
                $i + 1,
                $username,
                'admin123!',
                implode(', ', $roles)
            ];
        }
        
        $io->table(['#', 'Username', 'Password', 'Roles'], $rows);
        
        if ($count > 5) {
            $io->note(sprintf('... and %d more users would be created', $count - 5));
        }
    }

    private function showResults(SymfonyStyle $io, int $created, int $skipped, int $failed, array $createdUsers): void
    {
        $io->section('Results');
        
        $io->definitionList(
            ['✅ Successfully created' => $created],
            ['⏭️  Skipped (duplicate username)' => $skipped],
            ['❌ Failed' => $failed]
        );

        if ($created > 0) {
            $io->success(sprintf('Successfully created %d demo users!', $created));
            
            // Show sample of created users
            if (count($createdUsers) > 0) {
                $io->section('Sample Created Users');
                $sampleUsers = array_slice($createdUsers, 0, 5);
                $rows = [];
                
                foreach ($sampleUsers as $index => $user) {
                    $rows[] = [
                        $user['id'],
                        $user['username'],
                        implode(', ', $user['roles'])
                    ];
                }
                
                $io->table(['ID', 'Username', 'Roles'], $rows);
                
                if (count($createdUsers) > 5) {
                    $io->note(sprintf('... and %d more users were created', count($createdUsers) - 5));
                }
            }
        }

        if ($skipped > 0) {
            $io->warning(sprintf('%d users were skipped due to username conflicts after maximum retry attempts.', $skipped));
        }

        if ($failed > 0) {
            $io->error(sprintf('%d users failed to create due to errors.', $failed));
        }
    }
}