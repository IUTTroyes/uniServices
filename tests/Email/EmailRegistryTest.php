<?php

namespace App\Tests\Email;

use App\Entity\Email\EmailTemplate;
use App\Entity\Structure\StructureDepartement;
use App\Enum\ModeNotificationEnum;
use App\Notification\NotificationEmailDefinition;
use App\Notification\NotificationType;
use App\Notification\NotificationTypeProviderInterface;
use App\Notification\NotificationTypeRegistry;
use App\Repository\Email\EmailTemplateRepository;
use App\Service\Email\AbstractEmailDefinition;
use App\Service\Email\EmailRegistry;
use App\Service\Email\EmailTemplateResolver;
use PHPUnit\Framework\TestCase;

/** Chaque notification devient un e-mail paramétrable, qui hérite du modèle générique tant qu'il n'est pas personnalisé. */
class EmailRegistryTest extends TestCase
{
    private function registre(AbstractEmailDefinition ...$explicites): EmailRegistry
    {
        $fournisseur = new class implements NotificationTypeProviderInterface {
            public function getTypes(): iterable
            {
                yield new NotificationType('demo.valide', 'demo', 'Démo', 'Ma demande est validée', 'Description', 'Rubrique', NotificationType::AUDIENCE_ETUDIANT, ModeNotificationEnum::IMMEDIAT);
                yield new NotificationType('demo.dedie', 'demo', 'Démo', 'Avec e-mail dédié', 'Description', 'Rubrique', NotificationType::AUDIENCE_ETUDIANT, ModeNotificationEnum::IMMEDIAT);
            }
        };

        return new EmailRegistry($explicites, new NotificationTypeRegistry([$fournisseur]));
    }

    private function dedie(): AbstractEmailDefinition
    {
        return new class extends AbstractEmailDefinition {
            public function getKey(): string { return 'demo.dedie'; }
            public function getLabel(): string { return 'E-mail dédié'; }
            public function getDefaultSubject(): string { return 'Dédié'; }
            public function getHtmlTemplatePath(): string { return 'emails/demo/dedie.html.twig'; }
            public function getAvailableVariables(): array { return ['nom' => 'Nom']; }
        };
    }

    public function testChaqueNotificationSansEmailDedieDevientParametrable(): void
    {
        $registre = $this->registre($this->dedie());

        $auto = $registre->get('demo.valide');
        self::assertInstanceOf(NotificationEmailDefinition::class, $auto);
        self::assertSame('Ma demande est validée', $auto->getLabel());
        self::assertSame('notification.immediate', $auto->getParentKey());
        self::assertSame('Demo', $auto->getBundle(), 'regroupé avec les autres e-mails du package');

        self::assertNotInstanceOf(NotificationEmailDefinition::class, $registre->get('demo.dedie'), 'l\'e-mail dédié du package est conservé');
        self::assertCount(2, $registre->all());
    }

    public function testResolutionDepartementEtablissementParentPuisDefaut(): void
    {
        $registre = $this->registre();
        $departement = new StructureDepartement();
        $generique = new EmailTemplate('notification.immediate', 'Générique', '<p>g</p>');
        $etablissement = new EmailTemplate('demo.valide', 'Établissement', '<p>e</p>');
        $local = new EmailTemplate('demo.valide', 'Département', '<p>d</p>');

        $resoudre = function (array $globaux, array $locaux, ?StructureDepartement $d) use ($registre) {
            $repo = $this->createMock(EmailTemplateRepository::class);
            $repo->method('findGlobal')->willReturnCallback(fn (string $cle) => $globaux[$cle] ?? null);
            $repo->method('findByKeyAndDepartement')->willReturnCallback(fn (string $cle) => $locaux[$cle] ?? null);

            return (new EmailTemplateResolver($repo, $registre))->resolve('demo.valide', $d);
        };

        self::assertTrue($resoudre([], [], null)->isFromTwigFile(), 'rien de personnalisé : gabarit du package');
        self::assertSame($generique, $resoudre(['notification.immediate' => $generique], [], null)->dbTemplate, 'hérite du générique');
        self::assertSame($etablissement, $resoudre(['notification.immediate' => $generique, 'demo.valide' => $etablissement], [], null)->dbTemplate);
        self::assertSame($local, $resoudre(['demo.valide' => $etablissement], ['demo.valide' => $local], $departement)->dbTemplate, 'le département prime');

        $resolu = $resoudre(['demo.valide' => $etablissement], [], null);
        self::assertSame('demo.valide', $resolu->definition?->getKey(), 'la définition reste connue pour le repli');
    }
}
