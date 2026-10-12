<?php

namespace App\Service\Etablissement;

use App\Entity\Etablissement;

class EtablissementSettingsService
{
    private const EDUSIGN_SCOPES = ['FI', 'FC'];

    public function getSettings(Etablissement $etablissement): array
    {
        return $etablissement->getSettings();
    }

    public function isEdusignEnabled(Etablissement $etablissement): bool
    {
        return $this->getSettings($etablissement)['integrations']['edusign']['enabled'] ?? false;
    }

    public function getEdusignScope(Etablissement $etablissement): array
    {
        $scope = $this->getSettings($etablissement)['integrations']['edusign']['scope'] ?? [];

        return array_values(array_intersect(self::EDUSIGN_SCOPES, $scope));
    }

    public function getEdusignApiKey(Etablissement $etablissement): ?string
    {
        return $this->getSettings($etablissement)['integrations']['edusign']['apiKey'] ?? null;
    }

    public function getEdusignApiUrl(Etablissement $etablissement): ?string
    {
        return $this->getSettings($etablissement)['integrations']['edusign']['apiUrl'] ?? null;
    }

    public function isOrebutEnabled(Etablissement $etablissement): bool
    {
        return $this->getSettings($etablissement)['integrations']['orebut']['enabled'] ?? false;
    }

    public function getOrebutApiKey(Etablissement $etablissement): ?string
    {
        return $this->getSettings($etablissement)['integrations']['orebut']['apiKey'] ?? null;
    }

    public function getOrebutApiUrl(Etablissement $etablissement): ?string
    {
        return $this->getSettings($etablissement)['integrations']['orebut']['apiUrl'] ?? null;
    }
}
