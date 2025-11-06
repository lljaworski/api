<?php

declare(strict_types=1);

namespace LukaszJaworski\CeidgBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use LukaszJaworski\CeidgBundle\State\CeidgCompanyProvider;
use LukaszJaworski\CeidgBundle\Validator\Constraints\PolishNip;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * API Resource for fetching company data from CEIDG (Polish Business Registry).
 * 
 * This resource allows authenticated users to retrieve company information
 * by Polish NIP (Tax Identification Number).
 */
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/ceidg/companies/{nip}',
            requirements: ['nip' => '\d{10}'],
            security: "is_granted('ROLE_USER')",
            provider: CeidgCompanyProvider::class,
            normalizationContext: [
                'groups' => ['ceidg:read'],
                'skip_null_values' => false
            ],
            name: 'get_ceidg_company',
            description: 'Get company data from CEIDG by NIP (10-digit Polish Tax ID)'
        )
    ],
    shortName: 'CeidgCompany',
    description: 'CEIDG company data from Polish Central Register and Information on Business'
)]
class CeidgCompany
{
    #[Groups(['ceidg:read'])]
    #[PolishNip]
    public string $nip;

    #[Groups(['ceidg:read'])]
    public string $nazwa;

    #[Groups(['ceidg:read'])]
    public string $dataRozpoczeciaDzialalnosci;

    #[Groups(['ceidg:read'])]
    public string $dataPowstania;

    #[Groups(['ceidg:read'])]
    public ?string $status = null;

    #[Groups(['ceidg:read'])]
    public ?string $dataZawieszeniaDzialalnosci = null;

    #[Groups(['ceidg:read'])]
    public ?string $dataWznowieniaDzialalnosci = null;

    #[Groups(['ceidg:read'])]
    public ?string $dataZakonczeniaDzialalnosci = null;

    /**
     * @var array<string, mixed>|null Primary business activity address
     */
    #[Groups(['ceidg:read'])]
    public ?array $adresDzialalnosci = null;

    /**
     * @var array<string, mixed>|null Correspondence/mailing address
     */
    #[Groups(['ceidg:read'])]
    public ?array $adresKorespondencyjny = null;

    /**
     * @var array<int, array<string, mixed>> Additional business activity addresses
     */
    #[Groups(['ceidg:read'])]
    public array $adresyDzialalnosciDodatkowe = [];

    #[Groups(['ceidg:read'])]
    public ?string $telefon = null;

    #[Groups(['ceidg:read'])]
    public ?string $email = null;

    #[Groups(['ceidg:read'])]
    public ?string $www = null;

    #[Groups(['ceidg:read'])]
    public ?string $adresDoreczenElektronicznych = null;

    #[Groups(['ceidg:read'])]
    public ?string $innaFormaKonaktu = null;

    /**
     * @param array<string, mixed>|null $adresDzialalnosci
     * @param array<string, mixed>|null $adresKorespondencyjny
     * @param array<int, array<string, mixed>> $adresyDzialalnosciDodatkowe
     */
    public function __construct(
        string $nip,
        string $nazwa,
        string $dataRozpoczeciaDzialalnosci,
        string $dataPowstania,
        ?string $status = null,
        ?string $dataZawieszeniaDzialalnosci = null,
        ?string $dataWznowieniaDzialalnosci = null,
        ?string $dataZakonczeniaDzialalnosci = null,
        ?array $adresDzialalnosci = null,
        ?array $adresKorespondencyjny = null,
        array $adresyDzialalnosciDodatkowe = [],
        ?string $telefon = null,
        ?string $email = null,
        ?string $www = null,
        ?string $adresDoreczenElektronicznych = null,
        ?string $innaFormaKonaktu = null,
    ) {
        $this->nip = $nip;
        $this->nazwa = $nazwa;
        $this->dataRozpoczeciaDzialalnosci = $dataRozpoczeciaDzialalnosci;
        $this->dataPowstania = $dataPowstania;
        $this->status = $status;
        $this->dataZawieszeniaDzialalnosci = $dataZawieszeniaDzialalnosci;
        $this->dataWznowieniaDzialalnosci = $dataWznowieniaDzialalnosci;
        $this->dataZakonczeniaDzialalnosci = $dataZakonczeniaDzialalnosci;
        $this->adresDzialalnosci = $adresDzialalnosci;
        $this->adresKorespondencyjny = $adresKorespondencyjny;
        $this->adresyDzialalnosciDodatkowe = $adresyDzialalnosciDodatkowe;
        $this->telefon = $telefon;
        $this->email = $email;
        $this->www = $www;
        $this->adresDoreczenElektronicznych = $adresDoreczenElektronicznych;
        $this->innaFormaKonaktu = $innaFormaKonaktu;
    }
}
