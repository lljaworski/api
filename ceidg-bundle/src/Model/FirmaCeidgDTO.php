<?php

declare(strict_types=1);

namespace LukaszJaworski\CeidgBundle\Model;

/**
 * Data Transfer Object for CEIDG company (Firma) data.
 * Contains essential fields from Polish business registry including addresses.
 */
final readonly class FirmaCeidgDTO
{
    /**
     * @param array<int, AdresDTO> $adresyDzialalnosciDodatkowe Additional business activity addresses
     */
    public function __construct(
        public string $nip,
        public string $nazwa,
        public \DateTimeInterface $dataRozpoczeciaDzialalnosci,
        public \DateTimeInterface $dataPowstania,
        public ?string $status = null,
        public ?\DateTimeInterface $dataZawieszeniaDzialalnosci = null,
        public ?\DateTimeInterface $dataWznowieniaDzialalnosci = null,
        public ?\DateTimeInterface $dataZakonczeniaDzialalnosci = null,
        public ?AdresDTO $adresDzialalnosci = null,
        public ?AdresDTO $adresKorespondencyjny = null,
        public array $adresyDzialalnosciDodatkowe = [],
    ) {}

    /**
     * Create DTO from CEIDG API response data.
     *
     * @param array<string, mixed> $data Raw API response data
     * @return self
     */
    public static function fromApiResponse(array $data): self
    {
        // NIP is nested in wlasciciel (owner) object
        $nip = $data['wlasciciel']['nip'] ?? '';
        
        // Parse additional business addresses
        $adresyDodatkowe = [];
        if (isset($data['adresyDzialalnosciDodatkowe']) && is_array($data['adresyDzialalnosciDodatkowe'])) {
            foreach ($data['adresyDzialalnosciDodatkowe'] as $adresData) {
                $adres = AdresDTO::fromApiResponse($adresData);
                if ($adres !== null) {
                    $adresyDodatkowe[] = $adres;
                }
            }
        }
        
        return new self(
            nip: $nip,
            nazwa: $data['nazwa'] ?? '',
            dataRozpoczeciaDzialalnosci: isset($data['dataRozpoczecia']) 
                ? new \DateTimeImmutable($data['dataRozpoczecia']) 
                : new \DateTimeImmutable(),
            dataPowstania: isset($data['dataRozpoczecia']) 
                ? new \DateTimeImmutable($data['dataRozpoczecia']) 
                : new \DateTimeImmutable(),
            status: $data['status'] ?? null,
            dataZawieszeniaDzialalnosci: isset($data['dataZawieszenia']) 
                ? new \DateTimeImmutable($data['dataZawieszenia']) 
                : null,
            dataWznowieniaDzialalnosci: isset($data['dataWznowienia']) 
                ? new \DateTimeImmutable($data['dataWznowienia']) 
                : null,
            dataZakonczeniaDzialalnosci: isset($data['dataZakonczenia']) 
                ? new \DateTimeImmutable($data['dataZakonczenia']) 
                : null,
            adresDzialalnosci: AdresDTO::fromApiResponse($data['adresDzialalnosci'] ?? null),
            adresKorespondencyjny: AdresDTO::fromApiResponse($data['adresKorespondencyjny'] ?? null),
            adresyDzialalnosciDodatkowe: $adresyDodatkowe,
        );
    }

    public function toArray(): array
    {
        return [
            'nip' => $this->nip,
            'nazwa' => $this->nazwa,
            'dataRozpoczeciaDzialalnosci' => $this->dataRozpoczeciaDzialalnosci->format('Y-m-d'),
            'dataPowstania' => $this->dataPowstania->format('Y-m-d'),
            'status' => $this->status,
            'dataZawieszeniaDzialalnosci' => $this->dataZawieszeniaDzialalnosci?->format('Y-m-d'),
            'dataWznowieniaDzialalnosci' => $this->dataWznowieniaDzialalnosci?->format('Y-m-d'),
            'dataZakonczeniaDzialalnosci' => $this->dataZakonczeniaDzialalnosci?->format('Y-m-d'),
            'adresDzialalnosci' => $this->adresDzialalnosci?->toArray(),
            'adresKorespondencyjny' => $this->adresKorespondencyjny?->toArray(),
            'adresyDzialalnosciDodatkowe' => array_map(
                fn(AdresDTO $adres) => $adres->toArray(),
                $this->adresyDzialalnosciDodatkowe
            ),
        ];
    }
}
