<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'email_subject',
        'email_body_html',
        'email_body_text',
        'sms_body',
        'push_title',
        'push_body',
        'whatsapp_template_id',
        'whatsapp_variables',
        'available_variables',
        'is_active',
    ];

    protected $casts = [
        'whatsapp_variables' => 'array',
        'available_variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Render template with variables
     */
    public function render(string $channel, array $variables): array
    {
        $result = [];

        switch ($channel) {
            case 'email':
                $result['subject'] = $this->replaceVariables($this->email_subject, $variables);
                $result['body_html'] = $this->replaceVariables($this->email_body_html, $variables);
                $result['body_text'] = $this->replaceVariables($this->email_body_text, $variables);
                break;

            case 'sms':
                $result['body'] = $this->replaceVariables($this->sms_body, $variables);
                break;

            case 'push':
                $result['title'] = $this->replaceVariables($this->push_title, $variables);
                $result['body'] = $this->replaceVariables($this->push_body, $variables);
                break;

            case 'whatsapp':
                $result['template_id'] = $this->whatsapp_template_id;
                $result['variables'] = array_map(
                    fn($var) => $variables[$var] ?? '',
                    $this->whatsapp_variables ?? []
                );
                break;
        }

        return $result;
    }

    /**
     * Replace variables in template string
     */
    protected function replaceVariables(?string $template, array $variables): ?string
    {
        if (!$template) {
            return null;
        }

        foreach ($variables as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
            $template = str_replace("{{ $key }}", $value, $template);
        }

        return $template;
    }

    /**
     * Check if channel is configured
     */
    public function hasChannel(string $channel): bool
    {
        return match ($channel) {
            'email' => !empty($this->email_subject) && !empty($this->email_body_html),
            'sms' => !empty($this->sms_body),
            'push' => !empty($this->push_title) && !empty($this->push_body),
            'whatsapp' => !empty($this->whatsapp_template_id),
            default => false,
        };
    }
}
