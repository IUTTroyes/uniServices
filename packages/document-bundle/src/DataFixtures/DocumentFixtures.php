<?php

namespace DocumentBundle\DataFixtures;

use DocumentBundle\Entity\Document;
use DocumentBundle\Entity\DocumentCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DocumentFixtures extends Fixture implements OrderedFixtureInterface, FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['document', 'default'];
    }

    public function getOrder(): int
    {
        return 20;
    }

    public function load(ObjectManager $manager): void
    {
        $categoriesData = [
            [
                'libelle' => 'Ressources Humaines',
                'icon' => '👥',
                'color' => 'bg-blue-500',
                'packageKey' => 'intranet',
                'isSystem' => true,
                'children' => [
                    ['libelle' => 'Contrats', 'icon' => '📋', 'color' => 'bg-blue-400', 'packageKey' => 'intranet', 'isSystem' => true],
                    ['libelle' => 'Formations', 'icon' => '🎓', 'color' => 'bg-blue-400', 'packageKey' => 'intranet', 'isSystem' => true],
                ],
            ],
            [
                'libelle' => 'Finance & Comptabilité',
                'icon' => '💰',
                'color' => 'bg-green-500',
                'packageKey' => 'intranet',
                'isSystem' => true,
                'children' => [
                    ['libelle' => 'Budgets Prévisionnels', 'icon' => '📈', 'color' => 'bg-green-400', 'packageKey' => 'intranet', 'isSystem' => true],
                    ['libelle' => 'Factures & Remboursements', 'icon' => '📊', 'color' => 'bg-green-400', 'packageKey' => 'intranet', 'isSystem' => true],
                ],
            ],
            [
                'libelle' => 'Technique & Documentation',
                'icon' => '⚙️',
                'color' => 'bg-purple-500',
                'packageKey' => null,
                'isSystem' => false,
                'children' => [
                    ['libelle' => 'Documentation API', 'icon' => '🔧', 'color' => 'bg-purple-400', 'packageKey' => null, 'isSystem' => false],
                    ['libelle' => 'Guides Utilisateur', 'icon' => '📖', 'color' => 'bg-purple-400', 'packageKey' => null, 'isSystem' => false],
                ],
            ],
            [
                'libelle' => 'Stages & Alternances',
                'icon' => '💼',
                'color' => 'bg-teal-500',
                'packageKey' => 'stage',
                'isSystem' => true,
                'children' => [
                    ['libelle' => 'Modèles de Convention', 'icon' => '📝', 'color' => 'bg-teal-400', 'packageKey' => 'stage', 'isSystem' => true],
                    ['libelle' => 'Fiches d\'Offres de Stage', 'icon' => '📄', 'color' => 'bg-teal-400', 'packageKey' => 'stage', 'isSystem' => true],
                ],
            ],
        ];

        $documentTypes = ['pdf', 'excel', 'word', 'powerpoint', 'image', 'video', 'audio', 'text', 'archive'];
        $titles = [
            'Rapport Annuel 2024', 'Guide d\'Accueil Étudiant', 'Contrat de Travail Type',
            'Présentation Institutionnelle', 'Analyse Budgétaire MMI', 'Documentation Technique API',
            'Procédure Qualité & Sécurité', 'Budget Prévisionnel 2025', 'Cahier des Charges Projet',
            'Manuel d\'utilisation Portail', 'Guide de Rédaction Mémoire', 'Chartes Informatique IUT',
            'Modèle de Convention de Stage', 'Guide de Recherche de Stage', 'Fiche d\'Offre Développeur Web'
        ];
        $authors = ['Marie Dubois', 'Pierre Martin', 'Sophie Leroy', 'Jean Dupont', 'Claire Bernard', 'Service Scolarité'];
        $tagsList = ['important', 'urgent', 'validé', 'stage', 'RH', 'brouillon', 'public', 'finance'];

        $createdCategories = [];

        foreach ($categoriesData as $catData) {
            $parentCat = new DocumentCategory();
            $parentCat->setLibelle($catData['libelle'])
                ->setIcon($catData['icon'])
                ->setColor($catData['color'])
                ->setPackageKey($catData['packageKey'])
                ->setIsSystem($catData['isSystem']);

            $manager->persist($parentCat);
            $createdCategories[] = $parentCat;

            if (isset($catData['children'])) {
                foreach ($catData['children'] as $childData) {
                    $childCat = new DocumentCategory();
                    $childCat->setLibelle($childData['libelle'])
                        ->setIcon($childData['icon'])
                        ->setColor($childData['color'])
                        ->setPackageKey($childData['packageKey'])
                        ->setIsSystem($childData['isSystem'])
                        ->setParent($parentCat);

                    $manager->persist($childCat);
                    $createdCategories[] = $childCat;
                }
            }
        }

        $docIndex = 1;
        foreach ($createdCategories as $category) {
            $numDocs = rand(2, 5);
            for ($i = 0; $i < $numDocs; $i++) {
                $type = $documentTypes[array_rand($documentTypes)];
                $title = $titles[array_rand($titles)] . ' (' . $docIndex . ')';
                $author = $authors[array_rand($authors)];
                $size = rand(10240, 15728640);
                $isFav = (rand(1, 10) > 7);
                $tags = array_intersect_key($tagsList, array_flip((array) array_rand($tagsList, rand(1, 3))));

                $doc = new Document();
                $doc->setTitre($title)
                    ->setDescription('Document d\'exemple pour la catégorie ' . $category->getLibelle())
                    ->setFilename('document_' . $docIndex . '.' . ($type === 'excel' ? 'xlsx' : ($type === 'word' ? 'docx' : $type)))
                    ->setMimeType($type === 'pdf' ? 'application/pdf' : 'application/octet-stream')
                    ->setFileSize($size)
                    ->setType($type)
                    ->setAuthor($author)
                    ->setVersion('v' . rand(1, 3) . '.' . rand(0, 9))
                    ->setTags(array_values($tags))
                    ->setIsFavorite($isFav)
                    ->setVisibility('PUBLIC')
                    ->setCategory($category);

                $manager->persist($doc);
                $docIndex++;
            }
        }

        $manager->flush();
    }
}
