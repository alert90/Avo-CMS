<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            $notifications = $user->notifications()
                ->orderBy('created_at', 'desc')
                ->paginate($request->query('per_page', 15));
                
            $data = $notifications->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                ];
            });
            
            return $this->sendSuccess([
                'data' => $data,
                'total' => $notifications->total(),
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total_pages' => $notifications->lastPage(),
                'unread_count' => $user->unreadNotifications()->count()
            ]);
            
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
    
    public function markAsRead($id)
    {
        try {
            $user = Auth::user();
            $notification = $user->notifications()->where('id', $id)->first();
            
            if ($notification) {
                $notification->markAsRead();
                return $this->sendSuccess(['message' => __('Notification marked as read')]);
            }
            
            return $this->sendError(__('Notification not found'));
            
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
    
    public function markAllAsRead(Request $request)
    {
        try {
            $user = Auth::user();
            $user->unreadNotifications()->update(['read_at' => now()]);
            
            return $this->sendSuccess(['message' => __('All notifications marked as read')]);
            
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}