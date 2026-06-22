<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key', 'name', 'subject', 'body_html', 'body_text',
        'available_variables', 'channel', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'available_variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Render the template body, replacing {var}, {{var}} and {{ var }} merge tags.
     *
     * @param  array<string, string>  $vars
     */
    public function render(array $vars = []): string
    {
        return $this->replaceTags($this->body_html, $vars);
    }

    /**
     * Render the subject line with the same merge tags.
     *
     * @param  array<string, string>  $vars
     */
    public function renderSubject(array $vars = []): string
    {
        return $this->replaceTags($this->subject, $vars);
    }

    /**
     * @param  array<string, string>  $vars
     */
    protected function replaceTags(string $text, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $text = str_replace(
                ['{{ '.$key.' }}', '{{'.$key.'}}', '{'.$key.'}'],
                (string) $value,
                $text,
            );
        }

        return $text;
    }
}
