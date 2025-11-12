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

class InvoiceNumberFormatValidationTest extends WebTestCase
{
    use DatabaseTestTrait;
    use RequestTrait;
    
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Ensure admin user exists for testing
        $this->ensureTestAdmin();
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $this->cleanupTestData();
        $this->cleanupTestPreferences();
        parent::tearDown();
    }

    public function testCreateInvoiceNumberFormatWithValidFormat(): void
    {
        $preferenceData = [
            'preferenceKey' => PreferenceKey::INVOICE_NUMBER_FORMAT->value,
            'value' => '{year}/{month}/{number}'
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(PreferenceKey::INVOICE_NUMBER_FORMAT->value, $responseData['preferenceKey']);
        $this->assertEquals('{year}/{month}/{number}', $responseData['value']);
    }

    public function testCreateInvoiceNumberFormatWithComplexValidFormat(): void
    {
        $preferenceData = [
            'preferenceKey' => PreferenceKey::INVOICE_NUMBER_FORMAT->value,
            'value' => 'INV-{year}-{month}-{number}'
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('INV-{year}-{month}-{number}', $responseData['value']);
    }

    public function testCreateInvoiceNumberFormatWithCustomTextAndValidFormat(): void
    {
        $preferenceData = [
            'preferenceKey' => PreferenceKey::INVOICE_NUMBER_FORMAT->value,
            'value' => 'INVOICE/{year}/{month}/NO/{number}/END'
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateInvoiceNumberFormatMissingYear(): void
    {
        $preferenceData = [
            'preferenceKey' => PreferenceKey::INVOICE_NUMBER_FORMAT->value,
            'value' => '{month}/{number}'
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('year', strtolower($responseData['detail'] ?? ''));
    }

    public function testCreateInvoiceNumberFormatMissingMonth(): void
    {
        $preferenceData = [
            'preferenceKey' => PreferenceKey::INVOICE_NUMBER_FORMAT->value,
            'value' => '{year}/{number}'
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('month', strtolower($responseData['detail'] ?? ''));
    }

    public function testCreateInvoiceNumberFormatMissingNumber(): void
    {
        $preferenceData = [
            'preferenceKey' => PreferenceKey::INVOICE_NUMBER_FORMAT->value,
            'value' => '{year}/{month}'
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('number', strtolower($responseData['detail'] ?? ''));
    }

    public function testCreateInvoiceNumberFormatMissingYearAndMonth(): void
    {
        $preferenceData = [
            'preferenceKey' => PreferenceKey::INVOICE_NUMBER_FORMAT->value,
            'value' => 'INV-{number}'
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateInvoiceNumberFormatMissingAllPlaceholders(): void
    {
        $preferenceData = [
            'preferenceKey' => PreferenceKey::INVOICE_NUMBER_FORMAT->value,
            'value' => 'INVOICE'
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateInvoiceNumberFormatWithEmptyValue(): void
    {
        $preferenceData = [
            'preferenceKey' => PreferenceKey::INVOICE_NUMBER_FORMAT->value,
            'value' => ''
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateInvoiceNumberFormatWithNullValue(): void
    {
        $preferenceData = [
            'preferenceKey' => PreferenceKey::INVOICE_NUMBER_FORMAT->value,
            'value' => null
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        // This should fail with 400 Bad Request due to NotNull constraint
        $this->assertContains(
            $this->client->getResponse()->getStatusCode(),
            [Response::HTTP_BAD_REQUEST, Response::HTTP_UNPROCESSABLE_ENTITY]
        );
    }

    public function testCreateInvoiceNumberFormatWithNonStringValue(): void
    {
        $preferenceData = [
            'preferenceKey' => PreferenceKey::INVOICE_NUMBER_FORMAT->value,
            'value' => 12345
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdateInvoiceNumberFormatWithValidFormat(): void
    {
        // Create initial preference
        $preference = $this->createTestPreference(
            PreferenceKey::INVOICE_NUMBER_FORMAT,
            '{year}/{month}/{number}'
        );
        
        $updateData = [
            'value' => '{year}-{month}-{number}'
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
        $this->assertEquals('{year}-{month}-{number}', $responseData['value']);
    }

    public function testUpdateInvoiceNumberFormatWithInvalidFormat(): void
    {
        // Create initial preference
        $preference = $this->createTestPreference(
            PreferenceKey::INVOICE_NUMBER_FORMAT,
            '{year}/{month}/{number}'
        );
        
        $updateData = [
            'value' => '{year}/{number}'  // Missing {month}
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
        
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testOtherPreferenceKeysNotAffectedByValidation(): void
    {
        // Test that other preference keys can still use any value format
        $preferenceData = [
            'preferenceKey' => PreferenceKey::SITE_NAME->value,
            'value' => 'My Site Name without any placeholders'
        ];
        
        $this->requestAsAdmin(Request::METHOD_POST, '/api/system-preferences', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($preferenceData));
        
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
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
