<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One Steadfast area (police station) from GET /police_stations. `id` is Steadfast's id. */
class SteadfastPoliceStation extends Model
{
    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status'    => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    /** Name without Steadfast's Bangla suffix ("Teknaf,টেকনাফ" → "Teknaf"). */
    public function cleanName(): string
    {
        return self::clean($this->name);
    }

    /** District as a customer would write it: "Dhaka City" / "Dhaka Sub-Urban" are both Dhaka. */
    public function cleanDistrict(): string
    {
        $district = self::clean($this->district_name);

        return preg_match('/^dhaka\b/i', $district) ? 'Dhaka' : $district;
    }

    /** "Cox's Bazar Sadar, Cox's Bazar" — what is added to the end of the Steadfast address. */
    public function addressLabel(): string
    {
        $name = $this->cleanName();
        $district = $this->cleanDistrict();

        return mb_strtolower($name) === mb_strtolower($district) ? $name : $name . ', ' . $district;
    }

    public static function clean(?string $text): string
    {
        $text = (string) preg_replace('/,.*$/u', '', (string) $text);
        $text = (string) preg_replace('/[\p{Bengali}]+/u', '', $text);

        return trim(preg_replace('/\s+/u', ' ', $text), " \t\n\r\0\x0B-,");
    }
}
