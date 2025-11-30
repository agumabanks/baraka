<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // Shipment Created
            [
                'code' => 'shipment_created',
                'name' => 'Shipment Created',
                'category' => 'shipment',
                'description' => 'Sent when a new shipment is created',
                'email_subject' => 'Your shipment {tracking_number} has been created',
                'email_body_html' => $this->getEmailTemplate('created'),
                'email_body_text' => 'Your shipment {tracking_number} has been created and is being processed. Track your shipment at: {tracking_url}',
                'sms_body' => '{company_name}: Your shipment {tracking_number} is confirmed! Track at {tracking_url}',
                'push_title' => 'Shipment Created',
                'push_body' => 'Your shipment {tracking_number} has been created',
                'available_variables' => ['tracking_number', 'status', 'origin', 'destination', 'recipient_name', 'eta', 'tracking_url', 'company_name'],
            ],
            // Picked Up
            [
                'code' => 'shipment_picked_up',
                'name' => 'Shipment Picked Up',
                'category' => 'shipment',
                'description' => 'Sent when shipment is picked up from sender',
                'email_subject' => 'Your shipment {tracking_number} has been picked up',
                'email_body_html' => $this->getEmailTemplate('picked_up'),
                'email_body_text' => 'Your shipment {tracking_number} has been picked up and is on its way. Track at: {tracking_url}',
                'sms_body' => '{company_name}: Shipment {tracking_number} picked up! On the way to {destination}. Track: {tracking_url}',
                'push_title' => 'Package Picked Up',
                'push_body' => 'Your shipment {tracking_number} has been picked up',
                'available_variables' => ['tracking_number', 'status', 'origin', 'destination', 'recipient_name', 'eta', 'tracking_url', 'company_name'],
            ],
            // In Transit
            [
                'code' => 'shipment_in_transit',
                'name' => 'Shipment In Transit',
                'category' => 'shipment',
                'description' => 'Sent when shipment is in transit',
                'email_subject' => 'Your shipment {tracking_number} is on the move',
                'email_body_html' => $this->getEmailTemplate('in_transit'),
                'email_body_text' => 'Your shipment {tracking_number} is in transit to {destination}. Expected delivery: {eta}',
                'sms_body' => '{company_name}: {tracking_number} in transit! ETA: {eta}. Track: {tracking_url}',
                'push_title' => 'Package In Transit',
                'push_body' => 'Your shipment is on its way. ETA: {eta}',
                'available_variables' => ['tracking_number', 'status', 'origin', 'destination', 'recipient_name', 'eta', 'tracking_url', 'company_name', 'current_location'],
            ],
            // Out for Delivery
            [
                'code' => 'shipment_out_for_delivery',
                'name' => 'Out for Delivery',
                'category' => 'shipment',
                'description' => 'Sent when shipment is out for delivery',
                'email_subject' => 'Your shipment {tracking_number} is out for delivery!',
                'email_body_html' => $this->getEmailTemplate('out_for_delivery'),
                'email_body_text' => 'Great news! Your shipment {tracking_number} is out for delivery and will arrive today.',
                'sms_body' => '{company_name}: {tracking_number} out for delivery! Arriving today. Track: {tracking_url}',
                'push_title' => 'Out for Delivery! 🚚',
                'push_body' => 'Your package {tracking_number} will arrive today!',
                'available_variables' => ['tracking_number', 'status', 'origin', 'destination', 'recipient_name', 'eta', 'tracking_url', 'company_name', 'driver_name'],
            ],
            // Delivered
            [
                'code' => 'shipment_delivered',
                'name' => 'Shipment Delivered',
                'category' => 'shipment',
                'description' => 'Sent when shipment is delivered',
                'email_subject' => 'Your shipment {tracking_number} has been delivered!',
                'email_body_html' => $this->getEmailTemplate('delivered'),
                'email_body_text' => 'Your shipment {tracking_number} has been delivered. Thank you for using {company_name}!',
                'sms_body' => '{company_name}: {tracking_number} DELIVERED! Thank you for shipping with us.',
                'push_title' => 'Package Delivered! ✅',
                'push_body' => 'Your shipment {tracking_number} has been delivered',
                'available_variables' => ['tracking_number', 'status', 'origin', 'destination', 'recipient_name', 'delivered_at', 'tracking_url', 'company_name', 'signature_name'],
            ],
            // Exception/Delay
            [
                'code' => 'shipment_exception',
                'name' => 'Shipment Exception',
                'category' => 'shipment',
                'description' => 'Sent when there is an issue with the shipment',
                'email_subject' => 'Alert: Issue with your shipment {tracking_number}',
                'email_body_html' => $this->getEmailTemplate('exception'),
                'email_body_text' => 'There is an issue with your shipment {tracking_number}. Please contact us for more information.',
                'sms_body' => '{company_name}: Issue with {tracking_number}. Please check {tracking_url} for details.',
                'push_title' => 'Shipment Alert ⚠️',
                'push_body' => 'There is an issue with your shipment {tracking_number}',
                'available_variables' => ['tracking_number', 'status', 'exception_type', 'exception_notes', 'tracking_url', 'company_name', 'support_phone'],
            ],
            // Cancelled
            [
                'code' => 'shipment_cancelled',
                'name' => 'Shipment Cancelled',
                'category' => 'shipment',
                'description' => 'Sent when shipment is cancelled',
                'email_subject' => 'Your shipment {tracking_number} has been cancelled',
                'email_body_html' => $this->getEmailTemplate('cancelled'),
                'email_body_text' => 'Your shipment {tracking_number} has been cancelled. If you have questions, please contact support.',
                'sms_body' => '{company_name}: Shipment {tracking_number} cancelled. Contact support for help.',
                'push_title' => 'Shipment Cancelled',
                'push_body' => 'Your shipment {tracking_number} has been cancelled',
                'available_variables' => ['tracking_number', 'cancellation_reason', 'tracking_url', 'company_name', 'support_phone'],
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['code' => $template['code']],
                $template
            );
        }

        $this->command->info('Notification templates seeded successfully!');
    }

    protected function getEmailTemplate(string $type): string
    {
        $templates = [
            'created' => <<<HTML
<h2>Shipment Confirmed!</h2>
<p>Hello {recipient_name},</p>
<p>Your shipment has been created and is being processed.</p>

<div class="tracking-box">
    <p><strong>Tracking Number:</strong></p>
    <p class="tracking-number">{tracking_number}</p>
</div>

<p><strong>From:</strong> {origin}</p>
<p><strong>To:</strong> {destination}</p>
<p><strong>Expected Delivery:</strong> {eta}</p>

<p style="text-align: center;">
    <a href="{tracking_url}" class="btn">Track Your Shipment</a>
</p>

<p>You will receive updates as your shipment progresses.</p>
HTML,

            'picked_up' => <<<HTML
<h2>Package Picked Up!</h2>
<p>Hello {recipient_name},</p>
<p>Great news! Your package has been picked up and is on its way.</p>

<div class="tracking-box">
    <p><strong>Tracking Number:</strong></p>
    <p class="tracking-number">{tracking_number}</p>
</div>

<p><strong>Status:</strong> {status}</p>
<p><strong>Destination:</strong> {destination}</p>
<p><strong>Expected Delivery:</strong> {eta}</p>

<p style="text-align: center;">
    <a href="{tracking_url}" class="btn">Track Your Shipment</a>
</p>
HTML,

            'in_transit' => <<<HTML
<h2>Your Package is On the Move!</h2>
<p>Hello {recipient_name},</p>
<p>Your shipment is currently in transit.</p>

<div class="tracking-box">
    <p><strong>Tracking Number:</strong></p>
    <p class="tracking-number">{tracking_number}</p>
</div>

<p><strong>Status:</strong> In Transit</p>
<p><strong>Destination:</strong> {destination}</p>
<p><strong>Expected Delivery:</strong> {eta}</p>

<p style="text-align: center;">
    <a href="{tracking_url}" class="btn">Track Your Shipment</a>
</p>
HTML,

            'out_for_delivery' => <<<HTML
<h2>🚚 Out for Delivery Today!</h2>
<p>Hello {recipient_name},</p>
<p>Exciting news! Your package is out for delivery and will arrive today.</p>

<div class="tracking-box">
    <p><strong>Tracking Number:</strong></p>
    <p class="tracking-number">{tracking_number}</p>
</div>

<p><strong>Status:</strong> Out for Delivery</p>
<p>Please ensure someone is available to receive the package.</p>

<p style="text-align: center;">
    <a href="{tracking_url}" class="btn">Track Live</a>
</p>
HTML,

            'delivered' => <<<HTML
<h2>✅ Package Delivered!</h2>
<p>Hello {recipient_name},</p>
<p>Your package has been successfully delivered.</p>

<div class="tracking-box">
    <p><strong>Tracking Number:</strong></p>
    <p class="tracking-number">{tracking_number}</p>
</div>

<p><strong>Delivered:</strong> {delivered_at}</p>

<p>Thank you for choosing {company_name}!</p>

<p style="text-align: center;">
    <a href="{tracking_url}" class="btn">View Delivery Details</a>
</p>
HTML,

            'exception' => <<<HTML
<h2>⚠️ Shipment Alert</h2>
<p>Hello {recipient_name},</p>
<p>We need to inform you about an issue with your shipment.</p>

<div class="tracking-box">
    <p><strong>Tracking Number:</strong></p>
    <p class="tracking-number">{tracking_number}</p>
</div>

<p><strong>Issue:</strong> {exception_type}</p>
<p><strong>Details:</strong> {exception_notes}</p>

<p>Our team is working to resolve this as quickly as possible.</p>

<p style="text-align: center;">
    <a href="{tracking_url}" class="btn">View Details</a>
</p>

<p>If you have questions, please contact our support team.</p>
HTML,

            'cancelled' => <<<HTML
<h2>Shipment Cancelled</h2>
<p>Hello {recipient_name},</p>
<p>Your shipment has been cancelled.</p>

<div class="tracking-box">
    <p><strong>Tracking Number:</strong></p>
    <p class="tracking-number">{tracking_number}</p>
</div>

<p><strong>Reason:</strong> {cancellation_reason}</p>

<p>If you have questions or need assistance, please contact our support team.</p>
HTML,
        ];

        return $templates[$type] ?? '<p>{status}</p>';
    }
}
