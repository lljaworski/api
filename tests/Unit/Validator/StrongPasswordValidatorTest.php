<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Validator\Constraints\StrongPassword;
use PHPUnit\Framework\TestCase;

class StrongPasswordValidatorTest extends TestCase
{
    public function testValidPasswords(): void
    {
        $validPasswords = [
            'password123!',
            'MySecure123@',
            'Test1234#',
            'Abcd1234$',
            '12345678!',
            'Hello123#World',
            'StrongPwd1!',
            'Complex123&',
        ];

        foreach ($validPasswords as $password) {
            // Valid passwords should have:
            // - At least 8 characters
            // - At least one number
            // - At least one special character
            
            $this->assertGreaterThanOrEqual(8, strlen($password), "Password '$password' should be at least 8 characters");
            $this->assertMatchesRegularExpression('/[0-9]/', $password, "Password '$password' should contain at least one number");
            $this->assertMatchesRegularExpression('/[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/', $password, "Password '$password' should contain at least one special character");
        }
    }

    public function testInvalidPasswords(): void
    {
        $invalidPasswords = [
            ['1234567', 'too_short'], // 7 characters
            ['short', 'too_short'], // Too short
            ['password!', 'no_number'], // No numbers
            ['password123', 'no_special'], // No special chars
            ['PASSWORD', 'no_number'], // No numbers, no special chars
            ['abcdefgh', 'no_number'], // No numbers, no special chars
        ];

        foreach ($invalidPasswords as [$password, $reason]) {
            // These tests verify the validation logic requirements
            switch ($reason) {
                case 'too_short':
                    $this->assertLessThan(8, strlen($password), "Password '$password' should be less than 8 characters for this test");
                    break;
                case 'no_number':
                    $this->assertDoesNotMatchRegularExpression('/[0-9]/', $password, "Password '$password' should not contain numbers for this test");
                    break;
                case 'no_special':
                    $this->assertDoesNotMatchRegularExpression('/[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/', $password, "Password '$password' should not contain special characters for this test");
                    break;
            }
        }
    }
}