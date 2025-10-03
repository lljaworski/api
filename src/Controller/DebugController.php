<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

class DebugController extends AbstractController
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[Route('/debug/env', name: 'debug_env', methods: ['GET'])]
    public function debugEnvironment(): JsonResponse
    {
        return new JsonResponse([
            'app_env' => $_ENV['APP_ENV'] ?? 'not_set',
            'database_url' => $_ENV['DATABASE_URL'] ?? 'not_set',
            'database_url_from_getenv' => getenv('DATABASE_URL') ?: 'not_set',
            'current_database' => $this->getCurrentDatabase(),
            'env_files_loaded' => $this->getLoadedEnvFiles(),
            'server_env_vars' => $this->getServerEnvVars(),
        ]);
    }

    #[Route('/debug/database', name: 'debug_database', methods: ['GET'])]
    public function debugDatabase(): JsonResponse
    {
        try {
            $currentDb = $this->connection->fetchOne('SELECT DATABASE()');
            $users = $this->connection->fetchAllAssociative('SELECT id, username, roles FROM users LIMIT 10');
            $userCount = $this->connection->fetchOne('SELECT COUNT(*) FROM users');

            return new JsonResponse([
                'current_database' => $currentDb,
                'user_count' => (int)$userCount,
                'sample_users' => $users,
                'connection_params' => $this->getConnectionParams(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'connection_params' => $this->getConnectionParams(),
            ], 500);
        }
    }

    #[Route('/debug/config', name: 'debug_config', methods: ['GET'])]
    public function debugConfig(): JsonResponse
    {
        return new JsonResponse([
            'project_dir' => $this->getParameter('kernel.project_dir'),
            'environment' => $this->getParameter('kernel.environment'),
            'debug_mode' => $this->getParameter('kernel.debug'),
            'database_url_parameter' => $this->getParameter('env(DATABASE_URL)'),
            'all_env_vars' => $this->getAllRelevantEnvVars(),
        ]);
    }

    private function getCurrentDatabase(): ?string
    {
        try {
            $result = $this->connection->fetchOne('SELECT DATABASE()');
            return $result ?: 'unknown';
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    private function getLoadedEnvFiles(): array
    {
        $envFiles = [];
        $projectDir = $this->getParameter('kernel.project_dir');
        
        $possibleEnvFiles = [
            '.env',
            '.env.local',
            '.env.dev',
            '.env.dev.local',
            '.env.test',
            '.env.test.local',
            '.env.prod',
            '.env.prod.local'
        ];
        
        foreach ($possibleEnvFiles as $file) {
            $fullPath = $projectDir . '/' . $file;
            if (file_exists($fullPath)) {
                $envFiles[] = [
                    'file' => $file,
                    'exists' => true,
                    'readable' => is_readable($fullPath),
                    'size' => filesize($fullPath),
                    'modified' => date('Y-m-d H:i:s', filemtime($fullPath))
                ];
            } else {
                $envFiles[] = [
                    'file' => $file,
                    'exists' => false
                ];
            }
        }
        
        return $envFiles;
    }

    private function getServerEnvVars(): array
    {
        $relevantVars = ['APP_ENV', 'APP_DEBUG', 'DATABASE_URL', 'JWT_SECRET_KEY', 'JWT_PUBLIC_KEY'];
        $serverVars = [];
        
        foreach ($relevantVars as $var) {
            $serverVars[$var] = [
                'from_env' => $_ENV[$var] ?? null,
                'from_server' => $_SERVER[$var] ?? null,
                'from_getenv' => getenv($var) ?: null,
            ];
        }
        
        return $serverVars;
    }

    private function getConnectionParams(): array
    {
        try {
            $params = $this->connection->getParams();
            
            // Don't expose sensitive information in production
            if ($this->getParameter('kernel.environment') !== 'dev') {
                $params['password'] = '***hidden***';
                $params['user'] = substr($params['user'] ?? '', 0, 3) . '***';
            }
            
            return $params;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getAllRelevantEnvVars(): array
    {
        $relevantPrefixes = ['APP_', 'DATABASE_', 'JWT_', 'CORS_', 'MESSENGER_'];
        $envVars = [];
        
        foreach ($_ENV as $key => $value) {
            foreach ($relevantPrefixes as $prefix) {
                if (str_starts_with($key, $prefix)) {
                    // Hide sensitive values in production
                    if ($this->getParameter('kernel.environment') !== 'dev' && 
                        (str_contains(strtolower($key), 'password') || 
                         str_contains(strtolower($key), 'secret') || 
                         str_contains(strtolower($key), 'key'))) {
                        $envVars[$key] = '***hidden***';
                    } else {
                        $envVars[$key] = $value;
                    }
                    break;
                }
            }
        }
        
        return $envVars;
    }
}