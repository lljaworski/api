<?php

declare(strict_types=1);

namespace LukaszJaworski\CeidgBundle\Model;

/**
 * Data Transfer Object for CEIDG address (Adres) data.
 * Represents a Polish address with all fields from CEIDG registry.
 */
final readonly class AdresDTO
{
    public function __construct(
        public ?string $ulica = null,
        public ?string $budynek = null,
        public ?string $lokal = null,
        public ?string $miasto = null,
        public ?string $wojewodztwo = null,
        public ?string $powiat = null,
        public ?string $gmina = null,
        public ?string $kraj = null,
        public ?string $kod = null,
        public ?string $skrytkaPocztowa = null,
        public ?string $opisNietypowegoMiejsca = null,
        public ?string $adresat = null,
        public ?string $terc = null,
        public ?string $simc = null,
        public ?string $ulic = null,
    ) {}

    /**
     * Create DTO from CEIDG API address data.
     *
     * @param array<string, mixed>|null $data Raw API address data
     * @return self|null Returns address DTO if data provided, null otherwise
     */
    public static function fromApiResponse(?array $data): ?self
    {
        if (empty($data)) {
            return null;
        }

        return new self(
            ulica: $data['ulica'] ?? null,
            budynek: $data['budynek'] ?? null,
            lokal: $data['lokal'] ?? null,
            miasto: $data['miasto'] ?? null,
            wojewodztwo: $data['wojewodztwo'] ?? null,
            powiat: $data['powiat'] ?? null,
            gmina: $data['gmina'] ?? null,
            kraj: $data['kraj'] ?? null,
            kod: $data['kod'] ?? null,
            skrytkaPocztowa: $data['skrytkaPocztowa'] ?? null,
            opisNietypowegoMiejsca: $data['opisNietypowegoMiejsca'] ?? null,
            adresat: $data['adresat'] ?? null,
            terc: $data['terc'] ?? null,
            simc: $data['simc'] ?? null,
            ulic: $data['ulic'] ?? null,
        );
    }

    /**
     * Convert address to array representation.
     *
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'ulica' => $this->ulica,
            'budynek' => $this->budynek,
            'lokal' => $this->lokal,
            'miasto' => $this->miasto,
            'wojewodztwo' => $this->wojewodztwo,
            'powiat' => $this->powiat,
            'gmina' => $this->gmina,
            'kraj' => $this->kraj,
            'kod' => $this->kod,
            'skrytkaPocztowa' => $this->skrytkaPocztowa,
            'opisNietypowegoMiejsca' => $this->opisNietypowegoMiejsca,
            'adresat' => $this->adresat,
            'terc' => $this->terc,
            'simc' => $this->simc,
            'ulic' => $this->ulic,
        ];
    }

    /**
     * Get formatted single-line address string.
     * 
     * @return string Formatted address (e.g., "ul. Marszałkowska 1/2, 00-001 Warszawa")
     */
    public function getFormattedAddress(): string
    {
        $parts = [];

        // Street part
        if ($this->ulica) {
            $street = 'ul. ' . $this->ulica;
            if ($this->budynek) {
                $street .= ' ' . $this->budynek;
                if ($this->lokal) {
                    $street .= '/' . $this->lokal;
                }
            }
            $parts[] = $street;
        } elseif ($this->budynek) {
            // Address without street name
            $building = $this->budynek;
            if ($this->lokal) {
                $building .= '/' . $this->lokal;
            }
            $parts[] = $building;
        }

        // City part with postal code
        if ($this->kod && $this->miasto) {
            $parts[] = $this->kod . ' ' . $this->miasto;
        } elseif ($this->miasto) {
            $parts[] = $this->miasto;
        }

        // Non-standard location description
        if ($this->opisNietypowegoMiejsca) {
            $parts[] = $this->opisNietypowegoMiejsca;
        }

        return implode(', ', array_filter($parts));
    }
}
