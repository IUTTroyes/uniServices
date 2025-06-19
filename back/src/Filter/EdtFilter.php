<?php

                namespace App\Filter;

                use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
                use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
                use ApiPlatform\Metadata\ApiFilter;
                use ApiPlatform\Metadata\Operation;
                use App\Entity\Structure\StructureAnnee;
                use Doctrine\ORM\QueryBuilder;
                use Symfony\Component\PropertyInfo\Type;

                #[ApiFilter(EdtFilter::class)]
                class EdtFilter extends AbstractFilter
                {
                    protected function filterProperty(
                        string $property,
                               $value,
                        QueryBuilder $queryBuilder,
                        QueryNameGeneratorInterface $queryNameGenerator,
                        string $resourceClass,
                        ?Operation $operation = null,
                        array $context = []
                    ): void {
                        if (null === $value) {
                            return;
                        }

                        $alias = $queryBuilder->getRootAliases()[0];

                        if ('departement' === $property) {
                            $departementAlias = $queryNameGenerator->generateJoinAlias('departement');
                            $queryBuilder
                                ->leftJoin(sprintf('%s.semestre', $alias), 'semestre')
                                ->leftJoin('semestre.annee', 'annee')
                                ->leftJoin('annee.pn', 'pn')
                                ->leftJoin('pn.diplome', 'diplome')
                                ->leftJoin('diplome.departement', $departementAlias)
                                ->andWhere(sprintf('%s.id IS NOT NULL', $departementAlias))
                                ->andWhere(sprintf('%s.id = :departement', $departementAlias))
                                ->setParameter('departement', $value);
                        }

                        if ('anneeUniversitaire' === $property) {
                            $queryBuilder
                                ->leftJoin(sprintf('%s.anneeUniversitaire', $alias), 'anneeUniversitaire')
                                ->andWhere('anneeUniversitaire.id = :anneeUniversitaire')
                                ->setParameter('anneeUniversitaire', $value);
                        }

                        if ('semaineFormation' === $property) {
                            $queryBuilder
                                ->andWhere(sprintf('%s.semaineFormation = :semaineFormation', $alias))
                                ->setParameter('semaineFormation', $value);
                        }

                        if ('personnel' === $property) {
                            $queryBuilder
                                ->join(sprintf('%s.personnel', $alias), 'personnel')
                                ->andWhere('personnel.id = :personnel')
                                ->setParameter('personnel', $value);
                        }

                        if ('semestre' === $property) {
                                    $queryBuilder
                                        ->andWhere('semestre.id = :semestre')
                                        ->setParameter('semestre', $value);
                                }
                    }

                    public function getDescription(string $resourceClass): array
                    {
                        return [
                            'anneeUniversitaire' => [
                                'property' => 'anneeUniversitaire',
                                'type' => Type::BUILTIN_TYPE_INT,
                                'required' => false,
                                'openapi' => [
                                    'description' => 'Filter by année universitaire',
                                ],
                            ],
                            'semaineFormation' => [
                                'property' => 'semaineFormation',
                                'type' => Type::BUILTIN_TYPE_INT,
                                'required' => false,
                                'openapi' => [
                                    'description' => 'Filter by semaine de formation',
                                ],
                            ],
                            'personnel' => [
                                'property' => 'personnel',
                                'type' => Type::BUILTIN_TYPE_INT,
                                'required' => false,
                                'openapi' => [
                                    'description' => 'Filter by personnel',
                                ],
                            ],
                            'departement' => [
                                'property' => 'departement',
                                'type' => Type::BUILTIN_TYPE_INT,
                                'required' => false,
                                'openapi' => [
                                    'description' => 'Filter by department',
                                ],
                            ],
                            'semestre' => [
                                'property' => 'semestre',
                                'type' => Type::BUILTIN_TYPE_INT,
                                'required' => false,
                                'openapi' => [
                                    'description' => 'Filter by semester',
                                ],
                            ],
                        ];
                    }
                }
