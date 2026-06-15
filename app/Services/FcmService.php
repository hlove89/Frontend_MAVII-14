<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Models\User;

class FcmService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')));
        $this->messaging = $factory->createMessaging();
    }

    public function sendNotification(string $fcmToken, string $title, string $body, array $data = [])
    {
        try {
            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification(Notification::create($title, $body))
                ->withData($data)
                ->withHighPriority();
            $this->messaging->send($message);
        } catch (\Exception $e) {
            \Log::error("FCM gagal dikirim ke token {$fcmToken}: " . $e->getMessage());
            throw $e;
        }
    }

    public function sendToUser(User $user, string $title, string $body, array $data = [])
    {
        $tokens = $user->deviceTokens->pluck('fcm_token')->toArray();
        if (empty($tokens)) {
            \Log::warning("Tidak ada device token untuk user {$user->id}");
            return;
        }

        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body))
            ->withData($data)
            ->withHighPriority();

        foreach (array_chunk($tokens, 500) as $chunk) {
            try {
                $this->messaging->sendMulticast($message, $chunk);
            } catch (\Exception $e) {
                \Log::error("FCM multicast gagal untuk user {$user->id}: " . $e->getMessage());
            }
        }
    }
}