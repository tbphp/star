<?php

namespace App\Http\Controllers;

use App\Models\Child;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ChildController extends Controller
{
    /**
     * Get all children
     */
    public function index(): JsonResponse
    {
        $children = Child::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $children->map(fn($child) => [
                'id' => $child->id,
                'name' => $child->name,
                'birthday' => $child->birthday->format('Y-m-d'),
                'age' => $child->age,
                'gender' => $child->gender,
                'avatar' => $child->avatar ? Storage::url($child->avatar) : null,
                'star_count' => $child->star_count,
            ]),
        ]);
    }

    /**
     * Get single child with details
     */
    public function show(Child $child): JsonResponse
    {
        // Load recent 20 star records with reward info
        $child->load([
            'starRecords' => function ($query) {
                $query->with('reward:id,name,image')
                    ->orderBy('created_at', 'desc')
                    ->limit(20);
            }
        ]);

        // Load rewards this child is participating in
        $child->load(['rewards' => function ($query) {
            $query->with('children:id,name,gender,star_count');
        }]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $child->id,
                'name' => $child->name,
                'birthday' => $child->birthday->format('Y-m-d'),
                'age' => $child->age,
                'gender' => $child->gender,
                'avatar' => $child->avatar ? Storage::url($child->avatar) : null,
                'star_count' => $child->star_count,
                'star_records' => $child->starRecords->map(fn($record) => [
                    'id' => $record->id,
                    'amount' => $record->amount,
                    'type' => $record->type,
                    'reason' => $record->reason,
                    'reward' => $record->reward ? [
                        'id' => $record->reward->id,
                        'name' => $record->reward->name,
                        'image' => $record->reward->image ? Storage::url($record->reward->image) : null,
                    ] : null,
                    'created_at' => $record->created_at->format('Y-m-d H:i'),
                ]),
                'rewards' => $child->rewards->map(fn($reward) => [
                    'id' => $reward->id,
                    'name' => $reward->name,
                    'image' => $reward->image ? Storage::url($reward->image) : null,
                    'star_cost' => $reward->star_cost,
                    'is_redeemed' => $reward->is_redeemed,
                    'children' => $reward->children->map(fn($child) => [
                        'id' => $child->id,
                        'name' => $child->name,
                        'gender' => $child->gender,
                        'star_count' => $child->star_count,
                    ]),
                    'total_stars' => $reward->children->sum('star_count'),
                    'is_achieved' => $reward->children->sum('star_count') >= $reward->star_cost,
                ]),
            ],
        ]);
    }

    /**
     * Create new child
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'birthday' => 'required|date',
            'gender' => 'required|in:male,female',
            'avatar' => 'nullable|image|max:102400',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $child = Child::create($data);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $child->id,
                'name' => $child->name,
                'birthday' => $child->birthday->format('Y-m-d'),
                'age' => $child->age,
                'gender' => $child->gender,
                'avatar' => $child->avatar ? Storage::url($child->avatar) : null,
                'star_count' => $child->star_count,
            ],
        ], 201);
    }

    /**
     * Update child
     */
    public function update(Request $request, Child $child): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'birthday' => 'sometimes|date',
            'gender' => 'sometimes|in:male,female',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($child->avatar) {
                Storage::disk('public')->delete($child->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $child->update($data);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $child->id,
                'name' => $child->name,
                'birthday' => $child->birthday->format('Y-m-d'),
                'age' => $child->age,
                'gender' => $child->gender,
                'avatar' => $child->avatar ? Storage::url($child->avatar) : null,
                'star_count' => $child->star_count,
            ],
        ]);
    }

    /**
     * Delete child
     */
    public function destroy(Child $child): JsonResponse
    {
        // Delete avatar
        if ($child->avatar) {
            Storage::disk('public')->delete($child->avatar);
        }

        $child->delete();

        return response()->json([
            'success' => true,
            'message' => 'Child deleted successfully',
        ]);
    }
}
