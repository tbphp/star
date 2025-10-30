<?php

namespace App\Http\Controllers;

use App\Models\Reward;
use App\Models\Child;
use App\Models\StarRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class RewardController extends Controller
{
    /**
     * Get all rewards with participants and progress
     */
    public function index(): JsonResponse
    {
        $rewards = Reward::with('children:id,name,star_count,avatar')
            ->orderBy('is_redeemed')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rewards->map(function ($reward) {
                $totalStars = $reward->children->sum('star_count');
                $isAchieved = $totalStars >= $reward->star_cost;

                return [
                    'id' => $reward->id,
                    'name' => $reward->name,
                    'image' => $reward->image ? Storage::url($reward->image) : null,
                    'star_cost' => $reward->star_cost,
                    'is_redeemed' => $reward->is_redeemed,
                    'redeemed_at' => $reward->redeemed_at?->format('Y-m-d H:i'),
                    'children' => $reward->children->map(fn($child) => [
                        'id' => $child->id,
                        'name' => $child->name,
                        'star_count' => $child->star_count,
                        'avatar' => $child->avatar ? Storage::url($child->avatar) : null,
                    ]),
                    'total_stars' => $totalStars,
                    'is_achieved' => $isAchieved,
                ];
            }),
        ]);
    }

    /**
     * Create new reward
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'star_cost' => 'required|integer|min:1',
            'image' => 'nullable|image|max:2048',
            'child_ids' => 'required|array|min:1',
            'child_ids.*' => 'exists:children,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, &$reward) {
                $data = [
                    'name' => $request->input('name'),
                    'star_cost' => $request->input('star_cost'),
                ];

                // Handle image upload
                if ($request->hasFile('image')) {
                    $data['image'] = $request->file('image')->store('rewards', 'public');
                }

                $reward = Reward::create($data);

                // Attach children to this reward
                $reward->children()->attach($request->input('child_ids'));
            });

            $reward->load('children:id,name,star_count');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $reward->id,
                    'name' => $reward->name,
                    'image' => $reward->image ? Storage::url($reward->image) : null,
                    'star_cost' => $reward->star_cost,
                    'children' => $reward->children,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create reward',
            ], 500);
        }
    }

    /**
     * Update reward
     */
    public function update(Request $request, Reward $reward): JsonResponse
    {
        // Don't allow updating redeemed rewards
        if ($reward->is_redeemed) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update redeemed reward',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'star_cost' => 'sometimes|required|integer|min:1',
            'image' => 'nullable|image|max:2048',
            'child_ids' => 'sometimes|required|array|min:1',
            'child_ids.*' => 'exists:children,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $reward) {
                $data = [];

                if ($request->has('name')) {
                    $data['name'] = $request->input('name');
                }
                if ($request->has('star_cost')) {
                    $data['star_cost'] = $request->input('star_cost');
                }

                // Handle image upload
                if ($request->hasFile('image')) {
                    // Delete old image
                    if ($reward->image) {
                        Storage::disk('public')->delete($reward->image);
                    }
                    $data['image'] = $request->file('image')->store('rewards', 'public');
                }

                // Update reward data
                if (!empty($data)) {
                    $reward->update($data);
                }

                // Update children if provided
                if ($request->has('child_ids')) {
                    $reward->children()->sync($request->input('child_ids'));
                }
            });

            $reward->load('children:id,name,star_count,avatar');
            $totalStars = $reward->children->sum('star_count');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $reward->id,
                    'name' => $reward->name,
                    'image' => $reward->image ? url(Storage::url($reward->image)) : null,
                    'star_cost' => $reward->star_cost,
                    'is_redeemed' => $reward->is_redeemed,
                    'children' => $reward->children->map(fn($child) => [
                        'id' => $child->id,
                        'name' => $child->name,
                        'star_count' => $child->star_count,
                        'avatar' => $child->avatar ? url(Storage::url($child->avatar)) : null,
                    ]),
                    'total_stars' => $totalStars,
                    'is_achieved' => $totalStars >= $reward->star_cost,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reward',
            ], 500);
        }
    }

    /**
     * Redeem reward
     */
    public function redeem(Request $request, Reward $reward): JsonResponse
    {
        // Check if already redeemed
        if ($reward->is_redeemed) {
            return response()->json([
                'success' => false,
                'message' => 'Reward already redeemed',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'deductions' => 'required|array',
            'deductions.*.child_id' => 'required|exists:children,id',
            'deductions.*.amount' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $deductions = $request->input('deductions');

        // Validate total deduction >= star_cost
        $totalDeduction = collect($deductions)->sum('amount');
        if ($totalDeduction < $reward->star_cost) {
            return response()->json([
                'success' => false,
                'message' => 'Total deduction is less than required stars',
            ], 400);
        }

        // Validate each child has enough stars
        foreach ($deductions as $deduction) {
            $child = Child::find($deduction['child_id']);
            if ($child->star_count < $deduction['amount']) {
                return response()->json([
                    'success' => false,
                    'message' => "Child {$child->name} doesn't have enough stars",
                ], 400);
            }
        }

        try {
            DB::transaction(function () use ($reward, $deductions) {
                foreach ($deductions as $deduction) {
                    $child = Child::find($deduction['child_id']);
                    $amount = $deduction['amount'];

                    // Create star record (redemption type)
                    StarRecord::create([
                        'child_id' => $child->id,
                        'amount' => -$amount,
                        'type' => 'redeem',
                        'reward_id' => $reward->id,
                    ]);

                    // Update child's star count
                    $child->decrement('star_count', $amount);

                    // Update deduction amount in pivot table
                    $reward->children()->updateExistingPivot($child->id, [
                        'deduction_amount' => $amount,
                    ]);
                }

                // Mark reward as redeemed
                $reward->update([
                    'is_redeemed' => true,
                    'redeemed_at' => now(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Reward redeemed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to redeem reward',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete reward
     */
    public function destroy(Reward $reward): JsonResponse
    {
        // Don't allow deleting redeemed rewards
        if ($reward->is_redeemed) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete redeemed reward',
            ], 400);
        }

        // Delete image
        if ($reward->image) {
            Storage::disk('public')->delete($reward->image);
        }

        $reward->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reward deleted successfully',
        ]);
    }
}
