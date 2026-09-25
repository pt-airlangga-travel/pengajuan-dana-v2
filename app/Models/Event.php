<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Institution;
use App\Models\ProposalDraft;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
// child dari institution - slug auto di model (sama v2 Controller::createSlug)
class Event extends Model
{
    use HasFactory;
    protected $primaryKey = 'event_defined_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'event_defined_id',
        'event_name',
        'event_short_name',
        'event_name_slug',
        'event_date', 'event_month', 'event_year' , 'event_started_at', 'event_finished_at',
        'event_availability',
        'id_institution'
    ];

    protected $appends = [
        'event_started_at_formatted',
        'event_finished_at_formatted',
    ];

    // Tambahkan accessor untuk tanggal mulai
    public function getEventStartedAtFormattedAttribute()
    {
        return $this->event_started_at 
            ? Carbon::parse($this->event_started_at)->translatedFormat('d M Y') 
            : null;
    }

    // Tambahkan accessor untuk tanggal selesai
    public function getEventFinishedAtFormattedAttribute()
    {
        return $this->event_finished_at 
            ? Carbon::parse($this->event_finished_at)->translatedFormat('d M Y') 
            : null;
    }

    protected static function booted(): void
    {
        // Sama v2: Controller::createSlug — auto slug dari short_name saat create/update jika kosong
        static::saving(function (Event $event) {
            if (empty($event->event_name_slug) && !empty($event->event_short_name)) {
                $slug = Str::slug($event->event_short_name);
                // v2 slug max 12, bersihkan non-alphanum sudah via Str::slug
                $slug = substr($slug, 0, 12);
                // pastikan unique (tambahkan suffix jika collision, max 12)
                $base = $slug;
                $i = 1;
                while (static::where('event_name_slug', $slug)->where('event_defined_id', '!=', $event->event_defined_id ?? '')->exists()) {
                    $suffix = '-' . $i;
                    $slug = substr($base, 0, 12 - strlen($suffix)) . $suffix;
                    $i++;
                }
                $event->event_name_slug = $slug;
            }
        });
    }

    public function institution(){
        return $this->belongsTo(Institution::class,'id_institution','institution_defined_id');
    }

    // Reuseable untuk Table & Infolist: 2026/bem/sponsorship/september/11-30
    public function getEventIdentityAttribute(): string
    {
        return \App\Services\ProposalHelper::formatEventIdentity($this);
    }
    
 
    /**
     * Get all of the ProposalDrafts for the Event
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ProposalDrafts()
    {
        return $this->hasMany(ProposalDraft::class, 'id_event', 'event_defined_id');
    }

    /**
     * Cache event list untuk SelectFilter (346 events, avoid query every request)
     */
    public static function getCachedSelectOptions(): array
    {
        $cached = cache()->get('event_select_options');
        if (is_array($cached)) {
            return $cached;
        }

        if ($cached !== null) {
            cache()->forget('event_select_options');
        }

        $options = self::orderByDesc('created_at')
            ->get()
            ->mapWithKeys(fn ($e) => [
                $e->event_defined_id => \App\Services\ProposalHelper::formatDefinedId($e->event_defined_id) . ' | ' . $e->event_name,
            ])
            ->toArray();

        cache()->put('event_select_options', $options, 3600);

        return $options;
    }

    /**
     * Cache event by defined_id untuk heading/description
     */
    public static function getCachedEvent(string $id): ?self
    {
        $cached = cache()->get("event_defined_id:{$id}");
        if ($cached instanceof self) {
            return $cached;
        }

        if ($cached !== null) {
            cache()->forget("event_defined_id:{$id}");
        }

        $event = self::with('institution')->where('event_defined_id', $id)->first();
        if ($event) {
            cache()->put("event_defined_id:{$id}", $event, 3600);
        }

        return $event;
    }

}
