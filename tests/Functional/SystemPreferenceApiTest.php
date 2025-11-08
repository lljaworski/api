<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\SystemPreference;
use App\Enum\PreferenceKey;
use App\Tests\Trait\DatabaseTestTrait;
use App\Tests\Trait\RequestTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SystemPreferenceApiTest extends WebTestCase
{
    use DatabaseTestTrait;
    use RequestTrait;
    
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Ensure admin user exists for testing
        $this->ensureTestAdmin();
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $this->cleanupTestPreferences();
        parent::tearDown();
    }

    public function testGetSystemPreferencesCollectionRequiresAuth(): void
    {
        $this->client->request(Request::METHOD_GET, '/api/system-preferences');
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testGetSystemPreferencesCollectionWithAuth(): void
    {
        $this->requestAsAdmin(Request::METHOD_GET, '/api/system-preferences');
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('member', $responseData);
        $this->assertIsArray($responseData['member']);
    }

    public function testCreateSystemPreference(): void
    {
        $preferenceData = [
            'preferenceKey' => PreferenceKey::SITE_NAME->value,
            'value' => ['name' => 'My Test Site']
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(PreferenceKey::SITE_NAME->value, $responseData['preferenceKey']);
        $this->assertEquals(['name' => 'My Test Site'], $responseData['value']);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('createdAt', $responseData);
        $this->assertArrayHasKey('updatedAt', $responseData);
        
        // Verify preference was created in database
        $preference = $this->entityManager->getRepository(SystemPreference::class)->find($responseData['id']);
        $this->assertNotNull($preference);
        $this->assertEquals(PreferenceKey::SITE_NAME, $preference->getPreferenceKey());
        $this->assertEquals(['name' => 'My Test Site'], $preference->getValue());
    }

    public function testCreateSystemPreferenceWithStringValue(): void
    {
        $preferenceData = [
            'preferenceKey' => PreferenceKey::DEFAULT_LANGUAGE->value,
            'value' => 'en'
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('en', $responseData['value']);
    }

    public function testCreateSystemPreferenceWithNumberValue(): void
    {
        $preferenceData = [
            'preferenceKey' => PreferenceKey::ITEMS_PER_PAGE->value,
            'value' => 30
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(30, $responseData['value']);
    }

    public function testCreateSystemPreferenceWithBooleanValue(): void
    {
        $preferenceData = [
            'preferenceKey' => PreferenceKey::MAINTENANCE_MODE->value,
            'value' => true
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['value']);
    }

    public function testCreateSystemPreferenceWithInvalidKey(): void
    {
        $preferenceData = [
            'preferenceKey' => 'invalid_key',
            'value' => 'some value'
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateSystemPreferenceWithMissingKey(): void
    {
        $preferenceData = [
            'value' => 'some value'
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        // Missing required field returns 400 Bad Request
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateSystemPreferenceWithMissingValue(): void
    {
        $preferenceData = [
            'preferenceKey' => PreferenceKey::SITE_NAME->value
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        // Missing required field returns 400 Bad Request
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateDuplicateSystemPreference(): void
    {
        // Create first preference
        $preferenceData = [
            'preferenceKey' => PreferenceKey::SITE_URL->value,
            'value' => 'https://example.com'
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        
        // Try to create duplicate
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testGetSystemPreference(): void
    {
        // Create a preference first
        $preference = $this->createTestPreference(
            PreferenceKey::DEFAULT_TIMEZONE,
            'America/New_York'
        );
        
        $this->requestAsAdmin(Request::METHOD_GET, '/api/system-preferences/' . $preference->getId());
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($preference->getId(), $responseData['id']);
        $this->assertEquals(PreferenceKey::DEFAULT_TIMEZONE->value, $responseData['preferenceKey']);
        $this->assertEquals('America/New_York', $responseData['value']);
    }

    public function testGetNonExistentSystemPreference(): void
    {
        $this->requestAsAdmin(Request::METHOD_GET, '/api/system-preferences/99999');
        
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdateSystemPreferenceValue(): void
    {
        // Create a preference first
        $preference = $this->createTestPreference(
            PreferenceKey::MAX_UPLOAD_SIZE,
            10
        );
        
        $preferenceId = $preference->getId();
        
        $updateData = [
            'value' => 20
        ];
        
        $this->requestAsAdmin(
            Request::METHOD_PATCH, 
            '/api/system-preferences/' . $preferenceId, 
            [], 
            [], 
            [
                'CONTENT_TYPE' => 'application/merge-patch+json',
            ], 
            json_encode($updateData)
        );
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(20, $responseData['value']);
        
        // Verify in database - refetch from database
        $updatedPreference = $this->entityManager->getRepository(SystemPreference::class)->find($preferenceId);
        $this->assertEquals(20, $updatedPreference->getValue());
    }

    public function testUpdateSystemPreferenceKey(): void
    {
        // Create a preference first
        $preference = $this->createTestPreference(
            PreferenceKey::ENABLE_REGISTRATION,
            true
        );
        
        $preferenceId = $preference->getId();
        
        $updateData = [
            'preferenceKey' => PreferenceKey::ENABLE_API->value
        ];
        
        $this->requestAsAdmin(
            Request::METHOD_PATCH, 
            '/api/system-preferences/' . $preferenceId, 
            [], 
            [], 
            [
                'CONTENT_TYPE' => 'application/merge-patch+json',
            ], 
            json_encode($updateData)
        );
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(PreferenceKey::ENABLE_API->value, $responseData['preferenceKey']);
        
        // Verify in database - refetch from database
        $updatedPreference = $this->entityManager->getRepository(SystemPreference::class)->find($preferenceId);
        $this->assertEquals(PreferenceKey::ENABLE_API, $updatedPreference->getPreferenceKey());
    }

    public function testUpdateSystemPreferenceBothKeyAndValue(): void
    {
        // Create a preference first
        $preference = $this->createTestPreference(
            PreferenceKey::SITE_DESCRIPTION,
            'Old description'
        );
        
        $updateData = [
            'preferenceKey' => PreferenceKey::SITE_NAME->value,
            'value' => 'New Site Name'
        ];
        
        $this->requestAsAdmin(
            Request::METHOD_PATCH, 
            '/api/system-preferences/' . $preference->getId(), 
            [], 
            [], 
            [
                'CONTENT_TYPE' => 'application/merge-patch+json',
            ], 
            json_encode($updateData)
        );
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(PreferenceKey::SITE_NAME->value, $responseData['preferenceKey']);
        $this->assertEquals('New Site Name', $responseData['value']);
    }

    public function testDeleteSystemPreference(): void
    {
        // Create a preference first
        $preference = $this->createTestPreference(
            PreferenceKey::SITE_URL,
            'https://test.com'
        );
        
        $preferenceId = $preference->getId();
        
        $this->requestAsAdmin(Request::METHOD_DELETE, '/api/system-preferences/' . $preferenceId);
        
        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
        
        // Verify preference was deleted from database
        $deletedPreference = $this->entityManager->getRepository(SystemPreference::class)->find($preferenceId);
        $this->assertNull($deletedPreference);
    }

    public function testDeleteNonExistentSystemPreference(): void
    {
        $this->requestAsAdmin(Request::METHOD_DELETE, '/api/system-preferences/99999');
        
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testGetSystemPreferencesCollectionPagination(): void
    {
        // Create multiple preferences
        $this->createTestPreference(PreferenceKey::SITE_NAME, 'Site 1');
        $this->createTestPreference(PreferenceKey::SITE_DESCRIPTION, 'Description 1');
        $this->createTestPreference(PreferenceKey::SITE_URL, 'https://site1.com');
        
        $this->requestAsAdmin(Request::METHOD_GET, '/api/system-preferences');
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertGreaterThanOrEqual(3, $responseData['pagination']['total'] ?? count($responseData['member']));
    }

    /**
     * Helper method to create a test preference
     */
    private function createTestPreference(PreferenceKey $key, mixed $value): SystemPreference
    {
        $preference = new SystemPreference($key, $value);
        
        $this->entityManager->persist($preference);
        $this->entityManager->flush();
        
        return $preference;
    }

    /**
     * Helper method to cleanup test preferences
     */
    private function cleanupTestPreferences(): void
    {
        try {
            $preferenceRepository = $this->entityManager->getRepository(SystemPreference::class);
            $preferences = $preferenceRepository->findAll();
            
            foreach ($preferences as $preference) {
                $this->entityManager->remove($preference);
            }
            
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Ignore errors during cleanup
        }
    }
}
