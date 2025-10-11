<?php

namespace App\Tests\Trait;

use Symfony\Component\DomCrawler\Crawler;

Trait RequestTrait
{
    public function requestAsAdmin(string $method, string $uri, array $parameters = [], array $files = [], array $server = [], ?string $content = null, bool $changeHistory = true): Crawler
    {
        $token = $this->getAuthToken();

        return $this->client->request($method, $uri, $parameters, $files, array_merge($server, [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]), $content, $changeHistory);
    }

    private function getAuthToken(): string
    {
        if ($this->adminToken === null) {
            $this->client->request('POST', '/api/login_check', [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'username' => 'admin',
                'password' => 'admin'
            ]));
            
            $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
            $responseData = json_decode($this->client->getResponse()->getContent(), true);
            $this->adminToken = $responseData['token'];
        }
        
        return $this->adminToken;
    }
}
