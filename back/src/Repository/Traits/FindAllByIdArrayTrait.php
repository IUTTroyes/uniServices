<?php

namespace App\Repository\Traits;

trait FindAllByIdArrayTrait
{
    public function findAllByIdArray(): array
    {
        $datas = $this->findAll();
        $result = [];

        foreach ($datas as $data) {
            $result[$data->getId()] = $data;
        }

        return $result;
    }

    public function findAllByOldIdArray(): array
    {
        $datas = $this->findAll();
        $result = [];

        foreach ($datas as $data) {
            $result[$data->getOldId()] = $data;
        }

        return $result;
    }
}
