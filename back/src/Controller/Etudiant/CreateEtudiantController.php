<?php

declare(strict_types=1);

namespace App\Controller\Etudiant;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Users\Etudiant;
use App\Repository\EtudiantRepository;
use App\Repository\Structure\StructureAnneeRepository;
use App\Repository\Structure\StructureAnneeUniversitaireRepository;
use App\Repository\Structure\StructureSemestreRepository;
use App\ValueObject\Adresse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateEtudiantController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EtudiantRepository $etudiantRepository,
        private readonly StructureAnneeRepository $structureAnneeRepository,
        private readonly StructureSemestreRepository $structureSemestreRepository,
        private readonly StructureAnneeUniversitaireRepository $structureAnneeUniversitaireRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('/api/etudiants/new', methods: ['POST'], name: 'create_etudiant')]
    public function create(Request $request): Response
    {
        // Récupérer les données JSON envoyées dans la requête
        $data = $request->toArray();
        $fileContent = $data['fileContent'] ?? null;
        $anneeUniv = $this->structureAnneeUniversitaireRepository->findOneBy(['id' => $data['anneeUniversitaireId']]);

        if (!$fileContent) {
            return new JsonResponse(['message' => 'No file content provided'], Response::HTTP_BAD_REQUEST);
        }

        // Traiter le contenu du fichier CSV
        $lines = explode("\n", $fileContent);

        // Vérifier si le fichier a au moins deux lignes (en-tête + données)
        if (count($lines) < 2) {
            return new JsonResponse(['message' => 'Le fichier CSV ne contient pas de données'], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer les en-têtes
        $headers = str_getcsv($lines[0], ';');

        $createdEtudiants = [];
        $errors = [];
        $processedLines = []; // Tableau pour stocker les informations sur chaque ligne traitée

        // Traiter chaque ligne de données (à partir de la deuxième ligne)
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) {
                continue; // Ignorer les lignes vides
            }

            $values = str_getcsv($line, ';');

            // Initialiser l'entrée pour cette ligne
            $lineInfo = [
                'nom' => '',
                'prenom' => '',
                'status' => 'error', // Par défaut, on considère qu'il y a une erreur
                'message' => ''
            ];

            // Vérifier que le nombre de valeurs correspond au nombre d'en-têtes
            if (count($values) !== count($headers)) {
                $errorMsg = "Le nombre de valeurs ne correspond pas au nombre d'en-têtes";
                $errors[] = "Ligne " . ($i + 1) . ": " . $errorMsg;
                $lineInfo['message'] = $errorMsg;
                $processedLines[] = $lineInfo;
                continue;
            }

            // Créer un tableau associatif avec les en-têtes comme clés
            $rowData = array_combine($headers, $values);

            // Récupérer le nom et prénom pour l'information de ligne
            $lineInfo['nom'] = $rowData['nom'] ?? '';
            $lineInfo['prenom'] = $rowData['prenom'] ?? '';

            $annee = $this->structureAnneeRepository->findOneBy(['apogeeCodeEtape' => $rowData['annee_code_etape']]);
            if (!$annee) {
                throw new \InvalidArgumentException('Année non trouvée pour le code étape fourni');
            }

            try {
                // Vérifier si l'étudiant existe déjà
                $existingEtudiant = null;
                if (!empty($rowData['numero_etudiant'])) {
                    $existingEtudiant = $this->etudiantRepository->findOneBy(['num_etudiant' => $rowData['numero_etudiant']]);
                }

                if (!$existingEtudiant && !empty($rowData['numero_ine'])) {
                    $existingEtudiant = $this->etudiantRepository->findOneBy(['num_ine' => $rowData['numero_ine']]);
                }

                if (!$existingEtudiant && !empty($rowData['nom']) && !empty($rowData['prenom'])) {
                    // Générer un username basé sur le prénom et le nom comme dans createEtudiantFromData
                    $username = strtolower($rowData['prenom'] . '.' . $rowData['nom']);
                    $username = preg_replace('/[^a-z0-9.]/', '', $username);
                    $existingEtudiant = $this->etudiantRepository->findOneBy(['username' => $username]);
                }

                if ($existingEtudiant) {
                    // L'étudiant existe déjà
                    $lineInfo['status'] = 'existant';
                    $lineInfo['message'] = 'Étudiant déjà inscrit';
                } else {
                    // Créer un nouvel étudiant
                    $etudiant = $this->createEtudiantFromData($rowData, $anneeUniv);
                    $this->entityManager->persist($etudiant);
                    $etudiantSco = $this->createEtudiantScolariteFromData($etudiant, $anneeUniv, $annee);
                    $this->entityManager->persist($etudiantSco);
                    $etudiantScoSemestre = $this->createEtudiantScolariteSemestreFromData($etudiantSco, $annee);
                    if ($etudiantScoSemestre) {
                        $this->entityManager->persist($etudiantScoSemestre);
                    }
                    $createdEtudiants[] = $etudiant->getId();
                    $lineInfo['status'] = 'créé';
                    $lineInfo['message'] = 'Étudiant créé avec succès';
                }
            } catch (\Exception $e) {
                $errors[] = "Ligne " . ($i + 1) . ": " . $e->getMessage();
                $lineInfo['status'] = 'erreur';
                $lineInfo['message'] = $e->getMessage();
            }

            $processedLines[] = $lineInfo;
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Import terminé',
            'created' => count($createdEtudiants),
            'errors' => $errors,
            'processedLines' => $processedLines // Ajouter le tableau des lignes traitées à la réponse
        ], $errors ? Response::HTTP_PARTIAL_CONTENT : Response::HTTP_OK);
    }

    private function createEtudiantFromData(array $data, StructureAnneeUniversitaire $anneeUniv): Etudiant
    {
        $etudiant = new Etudiant();

        // Vérifier et définir les champs obligatoires
        if (empty($data['nom']) || empty($data['prenom'])) {
            throw new \InvalidArgumentException('Le nom et le prénom sont obligatoires');
        }

        // Définir les propriétés de base
        $etudiant->setNom($data['nom']);
        $etudiant->setPrenom($data['prenom']);

        // Générer un username basé sur le prénom et le nom
        $username = strtolower($data['prenom'] . '.' . $data['nom']);
        $username = preg_replace('/[^a-z0-9.]/', '', $username); // Supprimer les caractères spéciaux
        $etudiant->setUsername($username);

        // Générer un email universitaire
        $mailUniv = $username . '@etudiant.univ-reims.fr';
        $etudiant->setMailUniv($mailUniv);

        // Définir un mot de passe par défaut
        // todo: À changer en production
        $plainPassword = 'test';
        $hashedPassword = $this->passwordHasher->hashPassword($etudiant, $plainPassword);
        $etudiant->setPassword($hashedPassword);

        // Définir les rôles
        $etudiant->setRoles(['ROLE_ETUDIANT']);

        // Ajouter l'image par défaut
        $etudiant->setPhotoName('noimage.png');

        // Définir les applications par défaut
        $etudiant->setApplications(['UniTranet']);

        // Définir les autres propriétés si elles existent dans les données
        if (!empty($data['numero_etudiant'])) {
            $etudiant->setNumEtudiant($data['numero_etudiant']);
        }

        if (!empty($data['numero_ine'])) {
            $etudiant->setNumIne($data['numero_ine']);
        }

        if (!empty($data['date_naissance'])) {
            try {
                $dateNaissance = \DateTime::createFromFormat('d/m/Y', $data['date_naissance']);
                if ($dateNaissance) {
                    $etudiant->setDateNaissance($dateNaissance);
                }
            } catch (\Exception $e) {
                // Ignorer si la date n'est pas valide
            }
        }

        if (!empty($data['annee_promotion(aaaa)'])) {
            $etudiant->setPromotion((int) $data['annee_promotion(aaaa)']);
        }

        if (!empty($data['annee_bac(aaaa)'])) {
            $etudiant->setAnneeBac((int) $data['annee_bac(aaaa)']);
        }

        if (!empty($data['telephone'])) {
            $etudiant->setTel1($data['telephone']);
        }

        // Par défaut, on considère que l'étudiant n'est pas boursier
        $etudiant->setBoursier(false);

        // Créer l'adresse étudiante si les champs nécessaires sont présents
        if (!empty($data['LIB_AD1']) || !empty($data['ville']) || !empty($data['codepostal'])) {
            $adresse = new Adresse(
                $data['LIB_AD1'] ?? '',
                $data['LIB_AD2'] ?? '',
                $data['LIB_AD3'] ?? '',
                $data['ville'] ?? '',
                $data['codepostal'] ?? '',
                'France' // Pays par défaut
            );
            $etudiant->setAdresseEtudiante($adresse);
        }

        return $etudiant;
    }

    public function createEtudiantScolariteFromData(Etudiant $etudiant, StructureAnneeUniversitaire $anneeUniv, StructureAnnee $annee): EtudiantScolarite
    {
        // Créer l'objet EtudiantScolarite
        $etudiantSco = new EtudiantScolarite();
        $etudiantSco->setAnneeUniversitaire($anneeUniv);
        $etudiantSco->setEtudiant($etudiant);
        $etudiantSco->setDepartement($annee->getDepartement());
        $etudiantSco->addAnnee($annee);
        $etudiantSco->setUuid();
        if ($anneeUniv->isActif()) {
            $etudiantSco->setActif(true);
        } else {
            $etudiantSco->setActif(false);
        }
        return $etudiantSco;
    }

    public function createEtudiantScolariteSemestreFromData(EtudiantScolarite $etudiantScolarite, StructureAnnee $annee): mixed
    {
        $semestres = $this->structureSemestreRepository->findBy(['annee' => $annee]);

        if (count($semestres) === 2) {
            foreach ($semestres as $semestre) {
                $etudiantScoSemestre = new EtudiantScolariteSemestre();
                $etudiantScoSemestre->setScolarite($etudiantScolarite);
                $etudiantScoSemestre->setSemestre($semestre);
                $groupes = $semestre->getGroupes();
                foreach ($groupes as $groupe) {
                    $etudiantScoSemestre->addGroupe($groupe);
                }
            }
        } else {
            return null;
        }
        return $etudiantScoSemestre;
    }
}
