<?php

declare(strict_types=1);

namespace LukaszJaworski\CeidgBundle\Model;

/**
 * Data Transfer Object for CEIDG company (Firma) data.
 * Contains essential fields from Polish business registry.
 */
final readonly class FirmaCeidgDTO
{
    public function __construct(
        public string $nip,
        public string $nazwa,
        public \DateTimeInterface $dataRozpoczeciaDzialalnosci,
        public \DateTimeInterface $dataPowstania,
        public ?string $status = null,
        public ?\DateTimeInterface $dataZawieszeniaDzialalnosci = null,
        public ?\DateTimeInterface $dataWznowieniaDzialalnosci = null,
        public ?\DateTimeInterface $dataZakonczeniaDzialalnosci = null,
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
        );
    }
}
