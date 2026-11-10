<?php

namespace UnifolioBundle\Service\Migration\V1;

use Doctrine\DBAL\Connection;

/**
 * Lecture seule de la base UniFolio v1 (noms de tables et de colonnes par défaut de Doctrine, d'après les entités v1).
 * Aucune écriture n'est jamais émise sur cette connexion.
 */
class V1Reader
{
    /** Tables et colonnes nécessaires ; vérifiées par verifierSchema() avant tout import. */
    private const ATTENDU = [
        'etudiant' => ['id', 'username', 'mail_univ', 'prenom', 'nom'],
        'enseignant' => ['id', 'username', 'mail_univ', 'prenom', 'nom'],
        'annee_universitaire' => ['id', 'libelle', 'annee'],
        'annee' => ['id', 'libelle', 'ordre', 'diplome_id'],
        'bibliotheque' => ['id', 'etudiant_id', 'annee_universitaire_id', 'annee_id'],
        'trace' => ['id', 'bibliotheque_id', 'type_trace', 'contenu', 'titre', 'description', 'legende', 'date_realisation', 'contexte', 'conception_ia', 'realisation_ia', 'date_creation', 'date_modification'],
        'validation' => ['id', 'trace_id', 'enseignant_id', 'etat', 'apc_niveau_id', 'date_creation', 'date_modification'],
        'apc_niveau' => ['id', 'ordre', 'libelle', 'competences_id'],
        'competence' => ['id', 'libelle', 'nom_court'],
        'commentaire' => ['id', 'trace_id', 'portfolio_id', 'enseignant_id', 'contenu', 'commentaire_parent', 'date_creation'],
        'portfolio' => ['id', 'etudiant_id', 'annee_id', 'cv_id', 'intitule', 'description', 'visibilite', 'banniere', 'opt_search', 'date_creation', 'date_modification'],
        'page' => ['id', 'intitule', 'description', 'ordre'],
        'page_portfolio' => ['page_id', 'portfolio_id'],
        'page_trace' => ['page_id', 'trace_id'],
        'ordre_page' => ['page_id', 'portfolio_id', 'ordre'],
        'ordre_trace' => ['trace_id', 'page_id', 'ordre'],
        'cv' => ['id', 'etudiant_id', 'intitule', 'description', 'langues', 'soft_skills', 'hard_skills', 'reseaux', 'poste', 'date_modification'],
        'experience' => ['id', 'poste', 'entreprise', 'date_debut', 'date_fin', 'activite'],
        'formation' => ['id', 'diplome', 'etablissement', 'date_debut', 'date_fin', 'activite'],
        'cv_experience' => ['cv_id', 'experience_id'],
        'cv_formation' => ['cv_id', 'formation_id'],
    ];

    public function __construct(private readonly Connection $v1)
    {
    }

    /** @return list<string> écarts entre le schéma attendu et la base v1 (vide = compatible) */
    public function verifierSchema(): array
    {
        $manques = [];
        $sm = $this->v1->createSchemaManager();
        $tables = array_map('strtolower', $sm->listTableNames());
        foreach (self::ATTENDU as $table => $colonnes) {
            if (!\in_array($table, $tables, true)) {
                $manques[] = sprintf('Table « %s » absente.', $table);
                continue;
            }
            $presentes = array_map(static fn ($c) => strtolower($c->getName()), $sm->listTableColumns($table));
            foreach (array_diff($colonnes, $presentes) as $colonne) {
                $manques[] = sprintf('Colonne « %s.%s » absente.', $table, $colonne);
            }
        }

        return $manques;
    }

    /** @return array<int, array<string, mixed>> */
    public function etudiants(): array
    {
        return $this->indexe('SELECT id, username, mail_univ, prenom, nom FROM etudiant');
    }

    /** @return array<int, array<string, mixed>> */
    public function enseignants(): array
    {
        return $this->indexe('SELECT id, username, mail_univ, prenom, nom FROM enseignant');
    }

    /** @return array<int, array<string, mixed>> */
    public function anneesUniversitaires(): array
    {
        return $this->indexe('SELECT id, libelle, annee FROM annee_universitaire');
    }

    /** @return array<int, array<string, mixed>> */
    public function annees(): array
    {
        return $this->indexe('SELECT id, libelle, ordre, diplome_id FROM annee');
    }

    /** @return array<int, array<string, mixed>> niveaux avec leur compétence */
    public function niveaux(): array
    {
        return $this->indexe('SELECT n.id, n.ordre, n.libelle, c.nom_court, c.libelle AS competence_libelle FROM apc_niveau n LEFT JOIN competence c ON c.id = n.competences_id');
    }

    /**
     * @param list<int>|null $etudiantIds
     *
     * @return list<array<string, mixed>>
     */
    public function traces(?array $etudiantIds = null): array
    {
        return $this->lignes(
            'SELECT t.*, b.etudiant_id, b.annee_universitaire_id, b.annee_id FROM trace t JOIN bibliotheque b ON b.id = t.bibliotheque_id'
            .($etudiantIds !== null ? ' WHERE b.etudiant_id IN (:ids)' : '').' ORDER BY t.id',
            $etudiantIds !== null ? ['ids' => $etudiantIds] : []
        );
    }

    /** @return list<array<string, mixed>> */
    public function validations(): array
    {
        return $this->lignes('SELECT * FROM validation ORDER BY id');
    }

    /** @return list<array<string, mixed>> */
    public function commentaires(): array
    {
        return $this->lignes('SELECT * FROM commentaire ORDER BY id');
    }

    /** @return list<array<string, mixed>> */
    public function portfolios(?array $etudiantIds = null): array
    {
        return $this->lignes('SELECT * FROM portfolio'.($etudiantIds !== null ? ' WHERE etudiant_id IN (:ids)' : '').' ORDER BY id', $etudiantIds !== null ? ['ids' => $etudiantIds] : []);
    }

    /**
     * Pages d'un portfolio dans l'ordre (OrdrePage, à défaut l'identifiant), avec leurs traces dans l'ordre (OrdreTrace).
     *
     * @return list<array{id: int, intitule: ?string, description: ?string, traces: list<int>}>
     */
    public function pagesDuPortfolio(int $portfolioId): array
    {
        $pages = $this->lignes(
            'SELECT p.id, p.intitule, p.description FROM page p JOIN page_portfolio pp ON pp.page_id = p.id LEFT JOIN ordre_page op ON op.page_id = p.id AND op.portfolio_id = :pf WHERE pp.portfolio_id = :pf ORDER BY COALESCE(op.ordre, 999999), p.id',
            ['pf' => $portfolioId]
        );
        foreach ($pages as &$page) {
            $page['traces'] = array_map('intval', $this->v1->fetchFirstColumn(
                'SELECT pt.trace_id FROM page_trace pt LEFT JOIN ordre_trace ot ON ot.trace_id = pt.trace_id AND ot.page_id = pt.page_id WHERE pt.page_id = :p ORDER BY COALESCE(ot.ordre, 999999), pt.trace_id',
                ['p' => $page['id']]
            ));
        }

        return $pages;
    }

    /** @return list<array<string, mixed>> */
    public function cvs(?array $etudiantIds = null): array
    {
        $cvs = $this->lignes('SELECT * FROM cv'.($etudiantIds !== null ? ' WHERE etudiant_id IN (:ids)' : '').' ORDER BY id', $etudiantIds !== null ? ['ids' => $etudiantIds] : []);
        foreach ($cvs as &$cv) {
            $cv['experiences'] = $this->lignes('SELECT e.* FROM experience e JOIN cv_experience ce ON ce.experience_id = e.id WHERE ce.cv_id = :cv ORDER BY e.id', ['cv' => $cv['id']]);
            $cv['formations'] = $this->lignes('SELECT f.* FROM formation f JOIN cv_formation cf ON cf.formation_id = f.id WHERE cf.cv_id = :cv ORDER BY f.id', ['cv' => $cv['id']]);
        }

        return $cvs;
    }

    /** @return array<int, array<string, mixed>> */
    private function indexe(string $sql): array
    {
        $resultat = [];
        foreach ($this->lignes($sql) as $ligne) {
            $resultat[(int) $ligne['id']] = $ligne;
        }

        return $resultat;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return list<array<string, mixed>>
     */
    private function lignes(string $sql, array $params = []): array
    {
        $types = [];
        foreach ($params as $nom => $valeur) {
            if (\is_array($valeur)) {
                $types[$nom] = \Doctrine\DBAL\ArrayParameterType::INTEGER;
            }
        }

        return $this->v1->fetchAllAssociative($sql, $params, $types);
    }
}
