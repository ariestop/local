<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Reference;

class ReferenceRepository
{
    public function __construct(
        private Reference $model
    ) {}

    public function getActions(): array
    {
        return $this->model->getActions();
    }

    public function getObjects(): array
    {
        return $this->model->getObjects();
    }

    public function getCities(): array
    {
        return $this->model->getCities();
    }

    public function getAreas(?int $cityId = null): array
    {
        return $this->model->getAreas($cityId);
    }

    public function getAreasByCity(): array
    {
        $areas = $this->model->getAreas();
        $result = [];
        foreach ($areas as $a) {
            $cid = (int) ($a['city_id'] ?? 0);
            if ($cid && !isset($result[$cid])) {
                $result[$cid] = [];
            }
            if ($cid) {
                $result[$cid][] = $a;
            }
        }
        return $result;
    }
}
