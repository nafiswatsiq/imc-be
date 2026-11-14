<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ServerRequestInterface as Request;

final class Paginator
{
    private const MAX_PER_PAGE = 100;

    /**
     * @return array{0: \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model>, 1: array{page: int, per_page: int, total: int}}
     */
    public static function paginate(Builder $query, Request $request, int $defaultPerPage = 10): array
    {
        $params = $request->getQueryParams();
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = (int) ($params['per_page'] ?? $defaultPerPage);
        $perPage = min(self::MAX_PER_PAGE, max(1, $perPage));

        $total = (clone $query)->toBase()->getCountForPagination();
        $items = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        return [$items, ['page' => $page, 'per_page' => $perPage, 'total' => $total]];
    }

    public static function likeTerm(string $term): string
    {
        return '%' . addcslashes($term, '\\%_') . '%';
    }
}
