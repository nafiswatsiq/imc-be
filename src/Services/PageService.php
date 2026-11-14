<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Page;
use App\Models\Permission;
use App\Exceptions\ValidationException;
use Illuminate\Database\Capsule\Manager as DB;

class PageService
{
    public function __construct(private readonly PermissionService $permissions)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Page
    {
        $data['slug'] = $data['slug'] ?? $this->slugFromPath($data['path']);
        $this->assertUnique($data);

        return DB::connection()->transaction(function () use ($data): Page {
            $page = Page::query()->create($data);

            foreach (Permission::ACTIONS as $action) {
                $page->permissions()->create(['action' => $action, 'name' => $page->slug . '.' . $action]);
            }

            return $page->load('permissions');
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Page $page, array $data): Page
    {
        $this->assertUnique($data, $page->id);

        return DB::connection()->transaction(function () use ($page, $data): Page {
            $page->fill($data)->save();

            if ($page->wasChanged('slug')) {
                foreach ($page->permissions as $permission) {
                    $permission->update(['name' => $page->slug . '.' . $permission->action]);
                }
                $this->permissions->flush();
            }

            return $page->load('permissions');
        });
    }

    public function delete(Page $page): void
    {
        $page->delete();
        $this->permissions->flush();
    }

    public function slugFromPath(string $path): string
    {
        return trim(str_replace('/', '-', trim($path, '/')), '-');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertUnique(array $data, ?int $ignoreId = null): void
    {
        $errors = [];
        foreach (['path' => 'Path', 'slug' => 'Slug'] as $field => $label) {
            if (!isset($data[$field])) {
                continue;
            }

            $query = Page::query()->where($field, $data[$field]);
            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }
            if ($query->exists()) {
                $errors[$field] = [$label . ' is already used.'];
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
