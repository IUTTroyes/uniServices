<?php

namespace QuestionnaireBundle\Tests\Security;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\DepartmentPermissionChecker;
use App\Security\UserEffectivePermissionService;
use PHPUnit\Framework\TestCase;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSection;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSectionInstance;
use QuestionnaireBundle\Enum\QuestStatutEnum;
use QuestionnaireBundle\Security\QuestionnaireAccess;
use QuestionnaireBundle\Services\QuestionnaireReglages;

/** Les rôles de gestion ne valent que dans leur département ; auteur, référent qualité et destinataires ont des droits limités. */
final class QuestionnaireAccessTest extends TestCase
{
    private StructureDepartement $a;
    private StructureDepartement $b;

    protected function setUp(): void
    {
        $this->a = self::avecId(new StructureDepartement(), 1);
        $this->b = self::avecId(new StructureDepartement(), 2);
    }

    /** @param list<string> $rolesDansA */
    private function access(array $rolesDansA = ['ROLE_QUESTIONNAIRE_MANAGER'], bool $superAdmin = false): QuestionnaireAccess
    {
        $permissions = $this->createMock(UserEffectivePermissionService::class);
        $permissions->method('isSuperAdmin')->willReturn($superAdmin);
        $departements = $this->createMock(DepartmentPermissionChecker::class);
        $departements->method('checkPermission')->willReturnCallback(
            fn ($user, StructureDepartement $d, string $role) => $d === $this->a && \in_array($role, $rolesDansA, true)
        );

        return new QuestionnaireAccess($permissions, $departements, new QuestionnaireReglages());
    }

    private function personnel(int $id): Personnel
    {
        $p = self::avecId(new Personnel(), $id);
        foreach ([$this->a, $this->b] as $d) {
            $p->addDepartementPersonnel((new StructureDepartementPersonnel())->setPersonnel($p)->setDepartement($d));
        }

        return $p;
    }

    private function questionnaire(?StructureDepartement $d, ?Personnel $auteur = null, QuestStatutEnum $statut = QuestStatutEnum::DRAFT): Questionnaire
    {
        return (new Questionnaire())->setDepartement($d)->setAuthor($auteur)->setStatus($statut);
    }

    public function testGestionnaireLimiteASonDepartement(): void
    {
        $access = $this->access();
        $user = $this->personnel(10);

        self::assertTrue($access->peutGerer($user, $this->questionnaire($this->a)));
        self::assertFalse($access->peutVoir($user, $this->questionnaire($this->b)));
        self::assertFalse($access->peutVoir($user, $this->questionnaire(null)), 'Établissement : super administrateur seulement.');
        self::assertSame([1], $access->departementsGeres($user));
        self::assertTrue($access->peutCreer($user, $this->a));
        self::assertFalse($access->peutCreer($user, $this->b));
    }

    public function testRoleQualiteEgalementGestionnaire(): void
    {
        self::assertTrue($this->access(['ROLE_QUALITE'])->peutGerer($this->personnel(10), $this->questionnaire($this->a)));
        self::assertFalse($this->access(['ROLE_CHEF_DEPT'])->peutGerer($this->personnel(10), $this->questionnaire($this->a)));
    }

    public function testSuperAdminVoitTout(): void
    {
        $access = $this->access([], true);
        self::assertTrue($access->peutGerer($this->personnel(10), $this->questionnaire(null)));
        self::assertTrue($access->peutSupprimer($this->personnel(10), $this->questionnaire($this->b, null, QuestStatutEnum::PUBLISHED)));
    }

    public function testAuteurSansRole(): void
    {
        $access = $this->access([]);
        $auteur = $this->personnel(20);
        $brouillon = $this->questionnaire($this->b, $auteur);
        $publie = $this->questionnaire($this->b, $auteur, QuestStatutEnum::PUBLISHED);

        self::assertTrue($access->peutGerer($auteur, $publie));
        self::assertTrue($access->peutSupprimer($auteur, $brouillon));
        self::assertFalse($access->peutSupprimer($auteur, $publie), 'L\'auteur ne supprime que ses brouillons.');
        self::assertFalse($access->peutGerer($this->personnel(21), $brouillon));
    }

    public function testEtudiantSansAucunDroit(): void
    {
        $access = $this->access([], true);
        self::assertFalse($access->peutVoir(new Etudiant(), $this->questionnaire($this->a)));
    }

    public function testReferentQualiteConsulteSansGerer(): void
    {
        $referent = $this->personnel(30);
        $this->a->setOpt(['questionnaire' => ['responsable_qualite' => 30]]);
        $access = $this->access([]);
        $q = $this->questionnaire($this->a);

        self::assertTrue($access->peutVoir($referent, $q));
        self::assertFalse($access->peutGerer($referent, $q));
        self::assertSame([1], $access->departementsVisibles($referent));
    }

    public function testDestinataireDesResultatsVoitSesSeulesSectionsApresPublication(): void
    {
        $enseignant = $this->personnel(40);
        $access = $this->access([]);
        $q = $this->questionnaire($this->a, null, QuestStatutEnum::CLOSED);
        $sienne = self::avecId(new QuestionnaireSectionInstance($q, new QuestionnaireSection(), 1, 'Maths', 'matiere', '12'), 101);
        $autre = self::avecId(new QuestionnaireSectionInstance($q, new QuestionnaireSection(), 2, 'Physique', 'matiere', '13'), 102);
        $q->addQuestionnaireSectionInstance($sienne)->addQuestionnaireSectionInstance($autre);
        $sienne->ajouterDestinataire($enseignant, 'previsionnel');

        self::assertFalse($access->peutVoirSynthese($enseignant, $q), 'Pas avant la publication.');
        $q->publierResultats();
        self::assertTrue($access->peutVoirSynthese($enseignant, $q));
        self::assertSame([101], $access->sectionsVisibles($enseignant, $q), 'Seulement sa section.');
        self::assertFalse($access->peutVoir($enseignant, $q), 'Les résultats, pas le questionnaire.');
        self::assertNull($this->access()->sectionsVisibles($this->personnel(10), $q), 'L\'équipe voit toutes les sections.');
    }

    /** @template T of object @param T $objet @return T */
    private static function avecId(object $objet, int $id): object
    {
        $r = new \ReflectionProperty($objet, 'id');
        $r->setValue($objet, $id);

        return $objet;
    }
}
