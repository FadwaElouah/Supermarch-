<?php

namespace App\Http\Controllers;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $notifications
        ]);
    }

    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);

        if ($notification->user_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Non autorisé'
            ], 403);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'L\'avis a été mis à jour'
        ]);
    }
}
