<?php
declare(strict_types=1);

namespace Strata\Data;

interface DataInterface
{
    public function getOne(string $endpoint, $identifier, array $options = []);
    public function list(string $endpoint, array $options);
    public function hasResults(): bool;
    public function getPagination(int $page, int $limit, ResponseInterface $response) : Pagination;
}