<?php

namespace HelpdeskBundle\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use HelpdeskBundle\Entity\HelpdeskTicket;
use Doctrine\ORM\EntityManagerInterface;


class TicketProcessor implements ProcessorInterface
{

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ( !$data instanceof HelpdeskTicket) {
            return $data;
        }

        $request=$context['request']?? null;
        $files=$request->files->all() ?? [];

        dd($files);

        $this->em->persist($data);
        $this->em->flush();
        return $data;
    }
}