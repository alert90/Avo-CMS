<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;

class UserStatusController extends Controller
{
    public function updateActivity(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => 0,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Update last activity timestamp
            $user->last_activity = now();
            $user->save();

            return response()->json([
                'status' => 1,
                'message' => 'Activity updated'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function checkOnlineStatus(Request $request)
    {
        try {
            $userIds = $request->input('user_ids', []);

            if (empty($userIds)) {
                return response()->json([
                    'status' => 1,
                    'data' => []
                ]);
            }

            $users = User::whereIn('id', $userIds)
                ->select('id', 'last_activity')
                ->get();

            $onlineStatus = [];
            $offlineThreshold = now()->subMinutes(5); // Users active in last 5 mins are online

            foreach ($users as $user) {
                $isOnline = $user->last_activity && $user->last_activity > $offlineThreshold;
                $onlineStatus[$user->id] = [
                    'online' => $isOnline,
                    'last_activity' => $user->last_activity
                ];
            }

            return response()->json([
                'status' => 1,
                'data' => $onlineStatus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
