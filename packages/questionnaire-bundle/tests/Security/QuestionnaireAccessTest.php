<?php

namespace QuestionnaireBundle\Tests\Security;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\DepartmentPermissionChecker;
use App\Security\UserEffectivePermissionService;
use PHPUnit\Framework\TestCase;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Security\QuestionnaireAccess;
use QuestionnaireBundle\Security\QuestionnaireVoter;
use QuestionnaireBundle\Services\QuestionnaireReglages;

/** Les services entrants doivent appliquer les règles du voter local, sans exception liée à l'auteur. */
final class QuestionnaireAccessTest extends TestCase
{
    private function access(?StructureDepartement $autorise = null): QuestionnaireAccess
    {
        $permissions = $this->createMock(UserEffectivePermissionService::class);
        $permissions->method('hasAnyPermissionInDepartment')->willReturnCallback(
            fn ($user, array $roles, $departement) => $autorise !== null && $departement === $autorise && in_array('ROLE_CHEF_DEPT', $roles, true)
        );
        return new QuestionnaireAccess($permissions, $this->createMock(DepartmentPermissionChecker::class), new QuestionnaireVoter($permissions), new QuestionnaireReglages());
    }

    public function testChefDeDepartementPeutModifierMaisPasSupprimer(): void
    {
        $a = new StructureDepartement();
        $b = new StructureDepartement();
        $access = $this->access($a);
        $user = new Personnel();
        self::assertTrue($access->peutGerer($user, (new Questionnaire())->setDepartement($a)));
        self::assertFalse($access->peutSupprimer($user, (new Questionnaire())->setDepartement($a)));
        self::assertFalse($access->peutGerer($user, (new Questionnaire())->setDepartement($b)));
        self::assertTrue($access->peutCreer($user, $a));
        self::assertFalse($access->peutCreer($user, $b));
    }

    public function testAuteurSansHabilitationNePeutPasGererOuExporter(): void
    {
        $user = new Personnel();
        $q = (new Questionnaire())->setAuthor($user)->setDepartement(new StructureDepartement());
        $access = $this->access();
        self::assertFalse($access->peutGerer($user, $q));
        self::assertFalse($access->peutSupprimer($user, $q));
        self::assertFalse($access->peutVoirSynthese($user, $q));
        self::assertSame([], $access->sectionsDestinataire($user, $q));
    }

    public function testLectureEtudianteLocaleConserveeSansDroitDEcriture(): void
    {
        $user = new Etudiant();
        $q = new Questionnaire();
        self::assertTrue($this->access()->peutVoir($user, $q));
        self::assertFalse($this->access()->peutGerer($user, $q));
        self::assertFalse($this->access()->peutVoirSynthese($user, $q));
    }
}
