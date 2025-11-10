<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\InvoiceStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceStatusTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertEquals('draft', InvoiceStatus::DRAFT->value);
        $this->assertEquals('issued', InvoiceStatus::ISSUED->value);
        $this->assertEquals('paid', InvoiceStatus::PAID->value);
        $this->assertEquals('cancelled', InvoiceStatus::CANCELLED->value);
    }

    public function testLabels(): void
    {
        $this->assertEquals('Draft', InvoiceStatus::DRAFT->getLabel());
        $this->assertEquals('Issued', InvoiceStatus::ISSUED->getLabel());
        $this->assertEquals('Paid', InvoiceStatus::PAID->getLabel());
        $this->assertEquals('Cancelled', InvoiceStatus::CANCELLED->getLabel());
    }

    public function testValidTransitionsFromDraft(): void
    {
        $draft = InvoiceStatus::DRAFT;
        
        $this->assertTrue($draft->canTransitionTo(InvoiceStatus::ISSUED));
        $this->assertTrue($draft->canTransitionTo(InvoiceStatus::CANCELLED));
        $this->assertFalse($draft->canTransitionTo(InvoiceStatus::PAID));
        $this->assertFalse($draft->canTransitionTo(InvoiceStatus::DRAFT)); // Same status
    }

    public function testValidTransitionsFromIssued(): void
    {
        $issued = InvoiceStatus::ISSUED;
        
        $this->assertTrue($issued->canTransitionTo(InvoiceStatus::PAID));
        $this->assertTrue($issued->canTransitionTo(InvoiceStatus::CANCELLED));
        $this->assertFalse($issued->canTransitionTo(InvoiceStatus::DRAFT));
        $this->assertFalse($issued->canTransitionTo(InvoiceStatus::ISSUED)); // Same status
    }

    public function testNoTransitionsFromPaid(): void
    {
        $paid = InvoiceStatus::PAID;
        
        $this->assertFalse($paid->canTransitionTo(InvoiceStatus::DRAFT));
        $this->assertFalse($paid->canTransitionTo(InvoiceStatus::ISSUED));
        $this->assertFalse($paid->canTransitionTo(InvoiceStatus::CANCELLED));
        $this->assertFalse($paid->canTransitionTo(InvoiceStatus::PAID)); // Same status
    }

    public function testNoTransitionsFromCancelled(): void
    {
        $cancelled = InvoiceStatus::CANCELLED;
        
        $this->assertFalse($cancelled->canTransitionTo(InvoiceStatus::DRAFT));
        $this->assertFalse($cancelled->canTransitionTo(InvoiceStatus::ISSUED));
        $this->assertFalse($cancelled->canTransitionTo(InvoiceStatus::PAID));
        $this->assertFalse($cancelled->canTransitionTo(InvoiceStatus::CANCELLED)); // Same status
    }

    public function testEditableStates(): void
    {
        $this->assertTrue(InvoiceStatus::DRAFT->isEditable());
        $this->assertTrue(InvoiceStatus::ISSUED->isEditable()); // Changed: ISSUED invoices are now editable
        $this->assertFalse(InvoiceStatus::PAID->isEditable());
        $this->assertFalse(InvoiceStatus::CANCELLED->isEditable());
    }

    public function testDeletableStates(): void
    {
        $this->assertTrue(InvoiceStatus::DRAFT->isDeletable());
        $this->assertFalse(InvoiceStatus::ISSUED->isDeletable());
        $this->assertFalse(InvoiceStatus::PAID->isDeletable());
        $this->assertTrue(InvoiceStatus::CANCELLED->isDeletable());
    }

    public function testAllEnumCasesAreCovered(): void
    {
        $allCases = InvoiceStatus::cases();
        $this->assertCount(4, $allCases);
        
        $expectedValues = ['draft', 'issued', 'paid', 'cancelled'];
        $actualValues = array_map(fn($case) => $case->value, $allCases);
        
        $this->assertEquals($expectedValues, $actualValues);
    }

    public function testTransitionMatrix(): void
    {
        // Create a complete transition matrix for validation
        $transitions = [
            InvoiceStatus::DRAFT->value => [
                InvoiceStatus::DRAFT->value => false,
                InvoiceStatus::ISSUED->value => true,
                InvoiceStatus::PAID->value => false,
                InvoiceStatus::CANCELLED->value => true,
            ],
            InvoiceStatus::ISSUED->value => [
                InvoiceStatus::DRAFT->value => false,
                InvoiceStatus::ISSUED->value => false,
                InvoiceStatus::PAID->value => true,
                InvoiceStatus::CANCELLED->value => true,
            ],
            InvoiceStatus::PAID->value => [
                InvoiceStatus::DRAFT->value => false,
                InvoiceStatus::ISSUED->value => false,
                InvoiceStatus::PAID->value => false,
                InvoiceStatus::CANCELLED->value => false,
            ],
            InvoiceStatus::CANCELLED->value => [
                InvoiceStatus::DRAFT->value => false,
                InvoiceStatus::ISSUED->value => false,
                InvoiceStatus::PAID->value => false,
                InvoiceStatus::CANCELLED->value => false,
            ],
        ];

        foreach (InvoiceStatus::cases() as $fromStatus) {
            foreach (InvoiceStatus::cases() as $toStatus) {
                $expected = $transitions[$fromStatus->value][$toStatus->value];
                $actual = $fromStatus->canTransitionTo($toStatus);
                
                $this->assertEquals(
                    $expected,
                    $actual,
                    sprintf(
                        'Transition from %s to %s should be %s',
                        $fromStatus->value,
                        $toStatus->value,
                        $expected ? 'allowed' : 'forbidden'
                    )
                );
            }
        }
    }

    public function testBusinessLogicConsistency(): void
    {
        // Editable states should generally be deletable (except when issued)
        $this->assertTrue(InvoiceStatus::DRAFT->isEditable());
        $this->assertTrue(InvoiceStatus::DRAFT->isDeletable());

        // Non-editable states might still be deletable if cancelled
        $this->assertFalse(InvoiceStatus::CANCELLED->isEditable());
        $this->assertTrue(InvoiceStatus::CANCELLED->isDeletable());

        // Paid invoices should never be editable or deletable
        $this->assertFalse(InvoiceStatus::PAID->isEditable());
        $this->assertFalse(InvoiceStatus::PAID->isDeletable());

        // Issued invoices should be editable but not deletable (updated business rule)
        $this->assertTrue(InvoiceStatus::ISSUED->isEditable()); // Changed: ISSUED invoices are now editable
        $this->assertFalse(InvoiceStatus::ISSUED->isDeletable());
    }

    public function testFromString(): void
    {
        // Test enum creation from string values
        $this->assertEquals(InvoiceStatus::DRAFT, InvoiceStatus::from('draft'));
        $this->assertEquals(InvoiceStatus::ISSUED, InvoiceStatus::from('issued'));
        $this->assertEquals(InvoiceStatus::PAID, InvoiceStatus::from('paid'));
        $this->assertEquals(InvoiceStatus::CANCELLED, InvoiceStatus::from('cancelled'));
    }

    public function testFromStringInvalid(): void
    {
        $this->expectException(\ValueError::class);
        InvoiceStatus::from('invalid');
    }

    public function testTryFromString(): void
    {
        // Test safe enum creation from string values
        $this->assertEquals(InvoiceStatus::DRAFT, InvoiceStatus::tryFrom('draft'));
        $this->assertEquals(InvoiceStatus::ISSUED, InvoiceStatus::tryFrom('issued'));
        $this->assertEquals(InvoiceStatus::PAID, InvoiceStatus::tryFrom('paid'));
        $this->assertEquals(InvoiceStatus::CANCELLED, InvoiceStatus::tryFrom('cancelled'));
        $this->assertNull(InvoiceStatus::tryFrom('invalid'));
    }

    public function testWorkflowStates(): void
    {
        // Test typical workflow progression
        $draft = InvoiceStatus::DRAFT;
        $this->assertTrue($draft->canTransitionTo(InvoiceStatus::ISSUED));
        
        $issued = InvoiceStatus::ISSUED;
        $this->assertTrue($issued->canTransitionTo(InvoiceStatus::PAID));
        
        // Alternative workflow: draft -> cancelled
        $this->assertTrue($draft->canTransitionTo(InvoiceStatus::CANCELLED));
        
        // Alternative workflow: issued -> cancelled
        $this->assertTrue($issued->canTransitionTo(InvoiceStatus::CANCELLED));
    }

    public function testInvalidWorkflowPrevention(): void
    {
        // Prevent backwards transitions in main workflow
        $this->assertFalse(InvoiceStatus::ISSUED->canTransitionTo(InvoiceStatus::DRAFT));
        $this->assertFalse(InvoiceStatus::PAID->canTransitionTo(InvoiceStatus::ISSUED));
        $this->assertFalse(InvoiceStatus::PAID->canTransitionTo(InvoiceStatus::DRAFT));
        
        // Prevent transitions from final states
        $this->assertFalse(InvoiceStatus::PAID->canTransitionTo(InvoiceStatus::CANCELLED));
        $this->assertFalse(InvoiceStatus::CANCELLED->canTransitionTo(InvoiceStatus::PAID));
    }
}