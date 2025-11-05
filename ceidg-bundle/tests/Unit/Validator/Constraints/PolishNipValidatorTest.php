<?php

declare(strict_types=1);

namespace LukaszJaworski\CeidgBundle\Tests\Unit\Validator\Constraints;

use LukaszJaworski\CeidgBundle\Validator\Constraints\PolishNip;
use LukaszJaworski\CeidgBundle\Validator\Constraints\PolishNipValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Unit tests for Polish NIP validator.
 */
class PolishNipValidatorTest extends TestCase
{
    private PolishNipValidator $validator;
    private ExecutionContextInterface $context;
    private ConstraintViolationBuilderInterface $violationBuilder;
    private PolishNip $constraint;

    protected function setUp(): void
    {
        $this->validator = new PolishNipValidator();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->constraint = new PolishNip();
        
        $this->validator->initialize($this->context);
    }

    public function testValidNip(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('1234567890', $this->constraint);
    }

    public function testValidNipWithSpaces(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('123 456 78 90', $this->constraint);
    }

    public function testValidNipWithDashes(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('123-456-78-90', $this->constraint);
    }

    public function testNullValueIsValid(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(null, $this->constraint);
    }

    public function testEmptyStringIsValid(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('', $this->constraint);
    }

    public function testInvalidNipTooShort(): void
    {
        $this->violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', $this->anything())
            ->willReturnSelf();
        
        $this->violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($this->violationBuilder);

        $this->validator->validate('123456789', $this->constraint);
    }

    public function testInvalidNipTooLong(): void
    {
        $this->violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', $this->anything())
            ->willReturnSelf();
        
        $this->violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($this->violationBuilder);

        $this->validator->validate('12345678901', $this->constraint);
    }

    public function testInvalidNipContainsLetters(): void
    {
        $this->violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', $this->anything())
            ->willReturnSelf();
        
        $this->violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($this->violationBuilder);

        $this->validator->validate('123ABC7890', $this->constraint);
    }

    public function testInvalidNipSpecialCharacters(): void
    {
        // Special characters that aren't separators should make it invalid
        $this->violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', $this->anything())
            ->willReturnSelf();
        
        $this->violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($this->violationBuilder);

        $this->validator->validate('123@456#89', $this->constraint);
    }

    #[DataProvider('validNipProvider')]
    public function testValidNipFormats(string $nip): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($nip, $this->constraint);
    }

    public static function validNipProvider(): array
    {
        return [
            'plain 10 digits' => ['1234567890'],
            'with spaces' => ['123 456 78 90'],
            'with dashes' => ['123-456-78-90'],
            'mixed separators' => ['123-456 78 90'],
            'leading zeros' => ['0000000001'],
            'all same digit' => ['1111111111'],
        ];
    }

    #[DataProvider('invalidNipProvider')]
    public function testInvalidNipFormats(string $nip): void
    {
        $this->violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', $this->anything())
            ->willReturnSelf();
        
        $this->violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($this->violationBuilder);

        $this->validator->validate($nip, $this->constraint);
    }

    public static function invalidNipProvider(): array
    {
        return [
            'too short' => ['123456789'],
            'too long' => ['12345678901'],
            'contains letters' => ['123ABC7890'],
            'only letters' => ['ABCDEFGHIJ'],
            'way too short' => ['123'],
            'empty after cleanup' => ['---'],
            '9 digits' => ['123456789'],
            '11 digits' => ['12345678901'],
        ];
    }
}
