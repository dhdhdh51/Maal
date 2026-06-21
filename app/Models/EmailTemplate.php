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
     * Render the template body by replacing {{ var }} merge tags.
     *
     * @param  array<string, string>  $vars
     */
    public function render(array $vars = []): string
    {
        $body = $this->body_html;

        foreach ($vars as $key => $value) {
            $body = str_replace(['{{'.$key.'}}', '{{ '.$key.' }}'], (string) $value, $body);
        }

        return $body;
    }
}
