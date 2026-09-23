<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Ensure user has admin privileges.
     */
    protected function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && $user->isAdmin(), 403, 'Unauthorized admin access.');
    }

    /**
     * List all seller accounts for moderation.
     */
    public function sellers(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $status = $request->query('status');

        $query = SellerProfile::with('user')->latest('id');

        if ($status) {
            $query->where('verification_status', $status);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * Approve or reject a seller application.
     */
    public function updateSellerStatus(Request $request, SellerProfile $seller): JsonResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected,pending'],
        ]);

        $seller->update([
            'verification_status' => $validated['status'],
        ]);

        return response()->json([
            'message' => "Seller application status updated to {$validated['status']}.",
            'seller' => $seller->fresh('user'),
        ]);
    }

    /**
     * Platform-wide marketplace analytics.
     */
    public function analytics(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $totalGmv = Order::where('status', '!=', 'cancelled')->sum('total_amount');
        $totalOrders = Order::count();
        $activeSellers = SellerProfile::where('verification_status', 'approved')->count();
        $pendingSellers = SellerProfile::where('verification_status', 'pending')->count();
        $totalBuyers = User::where('role', 'buyer')->count();

        $recentOrders = Order::with(['user', 'items'])
            ->latest('id')
            ->take(10)
            ->get();

        return response()->json([
            'overview' => [
                'total_gmv' => $totalGmv,
                'formatted_gmv' => '$'.number_format($totalGmv / 100, 2),
                'total_orders' => $totalOrders,
                'active_sellers' => $activeSellers,
                'pending_sellers' => $pendingSellers,
                'total_buyers' => $totalBuyers,
            ],
            'recent_orders' => $recentOrders,
        ]);
    }
}
