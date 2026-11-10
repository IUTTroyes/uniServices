<?php

namespace App\Service\Ldap;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Ldap\Exception\LdapException;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\LdapInterface;

/**
 * Interrogation de l'annuaire LDAP pour retrouver un personnel à partir de son code RH (supannEmpId).
 */
class LdapPersonnelService
{
    public function __construct(
        #[Autowire(env: 'LDAP_HOST')] private readonly string $host,
        #[Autowire(env: 'LDAP_LOGIN')] private readonly string $login,
        #[Autowire(env: 'LDAP_PASSWORD')] private readonly string $password,
        #[Autowire(env: 'LDAP_BASE_DN')] private readonly string $baseDn,
    ) {
    }

    public function isConfigured(): bool
    {
        return '' !== $this->host && '' !== $this->baseDn && extension_loaded('ldap');
    }

    /**
     * @return array{login: ?string, mail: ?string, nom: ?string, prenom: ?string}|null null si aucun résultat unique
     *
     * @throws LdapException si l'annuaire est injoignable ou mal configuré
     */
    public function getInfoPersonnel(string $numeroHarpege): ?array
    {
        $ldap = Ldap::create('ext_ldap', ['connection_string' => $this->host, 'version' => 3]);
        $ldap->bind($this->login, $this->password);

        $filtre = sprintf('(supannEmpId=%s)', $ldap->escape($numeroHarpege, '', LdapInterface::ESCAPE_FILTER));
        $resultats = $ldap->query($this->baseDn, $filtre, ['filter' => ['uid', 'mail', 'sn', 'givenName']])
            ->execute()
            ->toArray();

        if (1 !== count($resultats)) {
            return null;
        }

        $entree = $resultats[0];

        return [
            'login' => $entree->getAttribute('uid')[0] ?? null,
            'mail' => $entree->getAttribute('mail')[0] ?? null,
            'nom' => $entree->getAttribute('sn')[0] ?? null,
            'prenom' => $entree->getAttribute('givenName')[0] ?? null,
        ];
    }
}
