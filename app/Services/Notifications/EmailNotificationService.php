<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Message;

/**
 * EmailNotificationService
 * 
 * Handles email notifications with:
 * - HTML and plain text support
 * - Branded templates
 * - Delivery tracking
 */
class EmailNotificationService
{
    /**
     * Send email notification
     */
    public function send(string $to, array $content): array
    {
        try {
            $subject = $content['subject'] ?? 'Notification';
            $htmlBody = $content['body_html'] ?? null;
            $textBody = $content['body_text'] ?? strip_tags($htmlBody ?? '');

            Mail::send([], [], function (Message $message) use ($to, $subject, $htmlBody, $textBody) {
                $message->to($to)
                    ->subject($subject);

                if ($htmlBody) {
                    $wrappedHtml = $this->wrapInBrandedTemplate($htmlBody, $subject);
                    $message->html($wrappedHtml);
                }

                if ($textBody) {
                    $message->text($textBody);
                }
            });

            Log::info('Email sent successfully', [
                'to' => $to,
                'subject' => $subject,
            ]);

            return [
                'success' => true,
                'message_id' => null, // Would come from email provider
            ];

        } catch (\Exception $e) {
            Log::error('Email send failed', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Wrap content in branded email template
     */
    protected function wrapInBrandedTemplate(string $content, string $subject): string
    {
        $appName = config('app.name');
        $year = date('Y');
        $primaryColor = '#3b82f6';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$subject}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .email-header {
            background: linear-gradient(135deg, {$primaryColor} 0%, #1d4ed8 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .email-body {
            background: white;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .email-footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #666;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: {$primaryColor};
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 10px 0;
        }
        .tracking-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .tracking-number {
            font-family: monospace;
            font-size: 18px;
            color: {$primaryColor};
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>{$appName}</h1>
        </div>
        <div class="email-body">
            {$content}
        </div>
        <div class="email-footer">
            <p>&copy; {$year} {$appName}. All rights reserved.</p>
            <p>This email was sent to you because you have an account with us or are tracking a shipment.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Send raw HTML email (no wrapping)
     */
    public function sendRaw(string $to, string $subject, string $html): array
    {
        try {
            Mail::send([], [], function (Message $message) use ($to, $subject, $html) {
                $message->to($to)
                    ->subject($subject)
                    ->html($html);
            });

            return ['success' => true];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
