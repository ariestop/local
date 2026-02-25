<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Reference
{
    public function __construct(
        private PDO $db
    ) {}

    public function getActions(): array
    {
        return $this->fetchAll("SELECT id, name FROM action ORDER BY id");
    }

    public function getObjects(): array
    {
        return $this->fetchAll("SELECT id, name FROM objectsale ORDER BY id");
    }

    public function getCities(): array
    {
        return $this->fetchAll("SELECT id, name FROM city ORDER BY id");
    }

    public function getAreas(?int $cityId = null): array
    {
        if ($cityId) {
            $stmt = $this->db->prepare("SELECT id, name FROM area WHERE city_id = ? ORDER BY name");
            $stmt->execute([$cityId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $this->fetchAll("SELECT id, city_id, name FROM area ORDER BY city_id, name");
    }

    private function fetchAll(string $sql): array
    {
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
