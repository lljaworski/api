<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Validator\Constraints\InvoiceNumberFormat;
use App\Validator\InvoiceNumberFormatValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class InvoiceNumberFormatValidatorTest extends TestCase
{
    private InvoiceNumberFormatValidator $validator;
    private ExecutionContextInterface $context;
    private ConstraintViolationBuilderInterface $violationBuilder;

    protected function setUp(): void
    {
        $this->validator = new InvoiceNumberFormatValidator();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        
        $this->validator->initialize($this->context);
    }

    public function testValidFormat(): void
    {
        $constraint = new InvoiceNumberFormat();
        
        $this->context->expects($this->never())
            ->method('buildViolation');
        
        $this->validator->validate('FV/{year}/{month}/{number:4}', $constraint);
    }

    public function testValidFormatWithoutPadding(): void
    {
        $constraint = new InvoiceNumberFormat();
        
        $this->context->expects($this->never())
            ->method('buildViolation');
        
        $this->validator->validate('INV-{year}-{month}-{number}', $constraint);
    }

    public function testValidFormatWithCustomPrefix(): void
    {
        $constraint = new InvoiceNumberFormat();
        
        $this->context->expects($this->never())
            ->method('buildViolation');
        
        $this->validator->validate('INVOICE_{year}_{month}_{number:6}', $constraint);
    }

    public function testNullValueIsAllowed(): void
    {
        $constraint = new InvoiceNumberFormat();
        
        $this->context->expects($this->never())
            ->method('buildViolation');
        
        $this->validator->validate(null, $constraint);
    }

    public function testEmptyStringIsAllowed(): void
    {
        $constraint = new InvoiceNumberFormat();
        
        $this->context->expects($this->never())
            ->method('buildViolation');
        
        $this->validator->validate('', $constraint);
    }

    public function testMissingYearPlaceholder(): void
    {
        $constraint = new InvoiceNumberFormat();
        
        $this->violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ placeholder }}', '{year}')
            ->willReturnSelf();
        
        $this->violationBuilder->expects($this->once())
            ->method('addViolation');
        
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->missingPlaceholder)
            ->willReturn($this->violationBuilder);
        
        $this->validator->validate('FV/{month}/{number:4}', $constraint);
    }

    public function testMissingMonthPlaceholder(): void
    {
        $constraint = new InvoiceNumberFormat();
        
        $this->violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ placeholder }}', '{month}')
            ->willReturnSelf();
        
        $this->violationBuilder->expects($this->once())
            ->method('addViolation');
        
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->missingPlaceholder)
            ->willReturn($this->violationBuilder);
        
        $this->validator->validate('FV/{year}/{number:4}', $constraint);
    }

    public function testMissingNumberPlaceholder(): void
    {
        $constraint = new InvoiceNumberFormat();
        
        $this->violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ placeholder }}', '{number}')
            ->willReturnSelf();
        
        $this->violationBuilder->expects($this->once())
            ->method('addViolation');
        
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->missingPlaceholder)
            ->willReturn($this->violationBuilder);
        
        $this->validator->validate('FV/{year}/{month}', $constraint);
    }

    public function testValidFormatWithDifferentSeparators(): void
    {
        $constraint = new InvoiceNumberFormat();
        
        $this->context->expects($this->never())
            ->method('buildViolation');
        
        // Test various separator styles
        $validFormats = [
            'FV/{year}/{month}/{number:4}',
            'INV-{year}-{month}-{number}',
            'F_{year}_{month}_{number:5}',
            '{year}.{month}.{number}',
            '{year}{month}{number:3}',
        ];
        
        foreach ($validFormats as $format) {
            $this->validator->validate($format, $constraint);
        }
    }

    public function testValidFormatWithMultipleDigitPadding(): void
    {
        $constraint = new InvoiceNumberFormat();
        
        $this->context->expects($this->never())
            ->method('buildViolation');
        
        $validFormats = [
            'FV/{year}/{month}/{number:1}',
            'FV/{year}/{month}/{number:2}',
            'FV/{year}/{month}/{number:10}',
            'FV/{year}/{month}/{number:99}',
        ];
        
        foreach ($validFormats as $format) {
            $this->validator->validate($format, $constraint);
        }
    }
}
