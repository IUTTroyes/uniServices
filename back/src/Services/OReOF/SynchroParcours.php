<?php

namespace App\Services\OReOF;

class SynchroParcours
{
    public function __construct(
        protected \Symfony\Contracts\HttpClient\HttpClientInterface $httpClient,
        protected \Doctrine\ORM\EntityManagerInterface $entityManager,
        protected \App\Repository\Structure\StructureDiplomeRepository $structureDiplomeRepository,
        protected SynchroDiplome $synchroDiplome
    )
    {
    }

    public function sync(int $parcoursId)
    {
        // Récupérer les données via HttpClient sur l'URL de l'API ORéOF
        try {
            $response = $this->httpClient->request('GET', 'https://oreof.univ-reims.fr/parcours/'.$parcoursId.'/maquette/validee_cfvu/export-json', [
                'timeout' => 600,
            ]);

            $maquette = json_decode($response->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Error decoding JSON response: ' . json_last_error_msg());
            }

            // Vérifier si le parcours est lié à un diplôme existant
            $diplome = $this->structureDiplomeRepository->findOneBy(['cleOreof' => $parcoursId]);

            if (!$diplome) {
                // Si le diplôme n'existe pas, le créer
                $diplome = new \App\Entity\Structure\StructureDiplome();
                $diplome->setLibelle($maquette['libelle'] ?? 'Parcours '.$parcoursId);
                $diplome->setCleOreof($parcoursId);
                $diplome->setActif(true);

                $this->entityManager->persist($diplome);
                $this->entityManager->flush();
            }

            // Utiliser le service SynchroDiplome pour synchroniser les données
            $this->synchroDiplome->sync($diplome);

            return true;
        } catch (\Exception $e) {
            // Gérer les erreurs
            throw new \Exception('Erreur lors de la synchronisation du parcours: ' . $e->getMessage());
        }
    }
}
