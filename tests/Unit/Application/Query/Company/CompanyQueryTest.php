<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Query\Company;

use App\Application\Query\Company\GetCompanyQuery;
use App\Application\Query\Company\GetCompaniesQuery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyQueryTest extends TestCase
{
    public function testGetCompanyQueryCanBeCreatedWithId(): void
    {
        $query = new GetCompanyQuery(id: 123);

        $this->assertEquals(123, $query->id);
    }

    public function testGetCompaniesQueryCanBeCreatedWithDefaultValues(): void
    {
        $query = new GetCompaniesQuery(page: 1, itemsPerPage: 30);

        $this->assertEquals(1, $query->page);
        $this->assertEquals(30, $query->itemsPerPage);
        $this->assertNull($query->search);
    }

    public function testGetCompaniesQueryCanBeCreatedWithSearch(): void
    {
        $query = new GetCompaniesQuery(
            page: 2,
            itemsPerPage: 50,
            search: 'Test Company'
        );

        $this->assertEquals(2, $query->page);
        $this->assertEquals(50, $query->itemsPerPage);
        $this->assertEquals('Test Company', $query->search);
    }
}