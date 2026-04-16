<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\RecyclableCategoryResource;
use App\Models\RecyclableCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecyclableCategoryController extends Controller
{
    use ApiResponse;

    /**
     * List all categories.
     */
    public function index(Request $request): JsonResponse
    {
        $query = RecyclableCategory::with('children');

        if ($request->boolean('active_only', false)) {
            $query->active();
        }

        if ($request->boolean('root_only', false)) {
            $query->rootCategories();
        }

        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $categories = $query->orderBy('sort_order')->orderBy('name')->get();

        return $this->success(RecyclableCategoryResource::collection($categories));
    }

    /**
     * Store a new category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category = RecyclableCategory::create($data);

        return $this->created(
            new RecyclableCategoryResource($category),
            'Category created successfully'
        );
    }

    /**
     * Show a specific category.
     */
    public function show(RecyclableCategory $category): JsonResponse
    {
        $category->load(['parent', 'children']);

        return $this->success(new RecyclableCategoryResource($category));
    }

    /**
     * Update a category.
     */
    public function update(UpdateCategoryRequest $request, RecyclableCategory $category): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        return $this->success(
            new RecyclableCategoryResource($category->fresh()->load('children')),
            'Category updated successfully'
        );
    }

    /**
     * Delete a category (soft delete).
     */
    public function destroy(RecyclableCategory $category): JsonResponse
    {
        $category->delete();

        return $this->success(message: 'Category deleted successfully');
    }
}
