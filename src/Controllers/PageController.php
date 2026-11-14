<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Page;
use App\Services\PageService;
use App\Support\ApiResponse;
use App\Support\Paginator;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;

final class PageController extends Controller
{
    public function __construct(private readonly PageService $pages)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $query = Page::query()->orderBy('sort_order')->orderBy('id');

        if (!empty($params['search']) && is_string($params['search'])) {
            $like = Paginator::likeTerm(mb_strtolower($params['search']));
            $query->where(function ($q) use ($like): void {
                $q->whereRaw('LOWER(name) LIKE ?', [$like])->orWhereRaw('LOWER(path) LIKE ?', [$like]);
            });
        }

        [$items, $meta] = Paginator::paginate($query, $request);

        return ApiResponse::json($response, $items, 200, $meta);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        return ApiResponse::json($response, Page::query()->with('permissions')->findOrFail($this->id($args)));
    }

    public function store(Request $request, Response $response): Response
    {
        $data = Validator::validate($this->input($request), $this->rules(), nullable: ['description']);
        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;

        return ApiResponse::json($response, $this->pages->create($data), 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $page = Page::query()->findOrFail($this->id($args));
        $data = Validator::validate($this->input($request), $this->rules(), partial: true, nullable: ['description']);
        if (isset($data['sort_order'])) {
            $data['sort_order'] = (int) $data['sort_order'];
        }

        return ApiResponse::json($response, $this->pages->update($page, $data));
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $this->pages->delete(Page::query()->findOrFail($this->id($args)));

        return $response->withStatus(204);
    }

    /**
     * @return array<string, \Respect\Validation\Validatable>
     */
    private function rules(): array
    {
        return [
            'name' => v::stringType()->notEmpty()->length(1, 100),
            'path' => v::stringType()->regex('/^\/[a-z0-9\-\/]*$/')->length(1, 150),
            'slug' => v::optional(v::stringType()->regex('/^[a-z0-9_-]+$/')->length(1, 50)),
            'description' => v::optional(v::stringType()->length(0, 1000)),
            'sort_order' => v::optional(v::intVal()),
            'is_active' => v::optional(v::boolType()),
        ];
    }
}
