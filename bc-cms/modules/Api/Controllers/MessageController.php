<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ChMessage;
use App\Models\ChFavorite;
use App\User;

class MessageController extends Controller
{
    public function getConversations(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->sendError(__("User not authenticated"));
            }

            // Get unique conversation partners
            $sentConversations = ChMessage::where('from_id', $user->id)
                ->select('to_id')
                ->distinct()
                ->with('toUser')
                ->get();

            $receivedConversations = ChMessage::where('to_id', $user->id)
                ->select('from_id')
                ->distinct()
                ->with('fromUser')
                ->get();

            $conversations = [];
            $processedUsers = [];

            // Process sent messages
            foreach ($sentConversations as $conv) {
                // Skip if user doesn't exist or already processed
                if (!$conv->toUser || in_array($conv->to_id, $processedUsers)) {
                    continue;
                }

                $latestMessage = ChMessage::where(function($q) use ($user, $conv) {
                    $q->where('from_id', $user->id)->where('to_id', $conv->to_id);
                })->orWhere(function($q) use ($user, $conv) {
                    $q->where('from_id', $conv->to_id)->where('to_id', $user->id);
                })->latest()->first();

                $unreadCount = ChMessage::where('from_id', $conv->to_id)
                    ->where('to_id', $user->id)
                    ->where('seen', 0)
                    ->count();

                $conversations[] = [
                    'user' => [
                        'id' => $conv->toUser->id,
                        'name' => $conv->toUser->getDisplayName(),
                        'avatar_url' => $conv->toUser->avatar_id ? get_file_url($conv->toUser->avatar_id) : null,
                    ],
                    'latest_message' => $latestMessage ? [
                        'body' => $latestMessage->body,
                        'created_at' => $latestMessage->created_at,
                        'from_id' => $latestMessage->from_id,
                    ] : null,
                    'unread_count' => $unreadCount,
                ];

                $processedUsers[] = $conv->to_id;
            }

            // Process received messages
            foreach ($receivedConversations as $conv) {
                // Skip if user doesn't exist or already processed
                if (!$conv->fromUser || in_array($conv->from_id, $processedUsers)) {
                    continue;
                }

                $latestMessage = ChMessage::where(function($q) use ($user, $conv) {
                    $q->where('from_id', $user->id)->where('to_id', $conv->from_id);
                })->orWhere(function($q) use ($user, $conv) {
                    $q->where('from_id', $conv->from_id)->where('to_id', $user->id);
                })->latest()->first();

                $unreadCount = ChMessage::where('from_id', $conv->from_id)
                    ->where('to_id', $user->id)
                    ->where('seen', 0)
                    ->count();

                $conversations[] = [
                    'user' => [
                        'id' => $conv->fromUser->id,
                        'name' => $conv->fromUser->getDisplayName(),
                        'avatar_url' => $conv->fromUser->avatar_id ? get_file_url($conv->fromUser->avatar_id) : null,
                    ],
                    'latest_message' => $latestMessage ? [
                        'body' => $latestMessage->body,
                        'created_at' => $latestMessage->created_at,
                        'from_id' => $latestMessage->from_id,
                    ] : null,
                    'unread_count' => $unreadCount,
                ];

                $processedUsers[] = $conv->from_id;
            }

            $userIds = array_column($conversations, 'user.id');

            if (!empty($userIds)) {
                $offlineThreshold = now()->subMinutes(5);

                $users = User::whereIn('id', $userIds)
                ->select('id', 'last_activity')
                ->get()
                ->keyBy('id');

                foreach ($conversations as &$conv) {
                    $userData = $users[$conv['user']['id']] ?? null;
                    $conv['user']['is_online'] = $userData && $userData->last_activity &&
                        $userData->last_activity > $offlineThreshold;
                }
            }

            // Sort by latest message
            usort($conversations, function($a, $b) {
                $timeA = $a['latest_message'] ? strtotime($a['latest_message']['created_at']) : 0;
                $timeB = $b['latest_message'] ? strtotime($b['latest_message']['created_at']) : 0;
                return $timeB - $timeA;
            });

            return response()->json([
                'status' => 1,
                'data' => $conversations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getConversation($id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => 0,
                    'message' => __("User not authenticated")
                ], 401);
            }

            $otherUser = User::find($id);

            if (!$otherUser) {
                return response()->json([
                    'status' => 0,
                    'message' => __("User not found")
                ], 404);
            }

            $messages = ChMessage::where(function($q) use ($user, $otherUser) {
                $q->where('from_id', $user->id)->where('to_id', $otherUser->id);
            })->orWhere(function($q) use ($user, $otherUser) {
                $q->where('from_id', $otherUser->id)->where('to_id', $user->id);
            })
            ->with(['fromUser', 'toUser'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($message) {
                // Skip messages where fromUser doesn't exist
                if (!$message->fromUser) {
                    return null;
                }

                return [
                    'id' => $message->id,
                    'body' => $message->body,
                    'from_id' => $message->from_id,
                    'to_id' => $message->to_id,
                    'seen' => $message->seen,
                    'created_at' => $message->created_at,
                    'from_user' => [
                        'id' => $message->fromUser->id,
                        'name' => $message->fromUser->getDisplayName(),
                        'avatar_url' => $message->fromUser->avatar_id ? get_file_url($message->fromUser->avatar_id) : null,
                    ]
                ];
            })
            ->filter() // Remove null values
            ->values(); // Reset array keys

            // Mark messages as read
            ChMessage::where('from_id', $otherUser->id)
                ->where('to_id', $user->id)
                ->where('seen', 0)
                ->update(['seen' => 1]);

            return response()->json([
                'status' => 1,
                'data' => [
                    'user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->getDisplayName(),
                        'avatar_url' => $otherUser->avatar_id ? get_file_url($otherUser->avatar_id) : null,
                    ],
                    'messages' => $messages
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function sendMessage(Request $request, $id)
    {
        try {
            $request->validate([
                'message' => 'required|string|max:1000'
            ]);

            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => 0,
                    'message' => __("User not authenticated")
                ], 401);
            }

            $toUser = User::find($id);

            if (!$toUser) {
                return response()->json([
                    'status' => 0,
                    'message' => __("User not found")
                ], 404);
            }

            $message = ChMessage::create([
                'from_id' => $user->id,
                'to_id' => $toUser->id,
                'body' => $request->message,
                'seen' => 0
            ]);

            return response()->json([
                'status' => 1,
                'data' => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'from_id' => $message->from_id,
                    'to_id' => $message->to_id,
                    'created_at' => $message->created_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function markAsRead($id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => 0,
                    'message' => __("User not authenticated")
                ], 401);
            }

            ChMessage::where('from_id', $id)
                ->where('to_id', $user->id)
                ->where('seen', 0)
                ->update(['seen' => 1]);

            return response()->json([
                'status' => 1,
                'message' => __('Messages marked as read')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
