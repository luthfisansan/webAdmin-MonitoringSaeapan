<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JawabanKuesioner extends Model
{
    protected $table = 'jawaban_kuesioner_t';

    protected $primaryKey = 'jawaban_kuesioner_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'hasil_kuesioner_id',
        'soal_id',
        'opsi_jawaban_id',
        'benar',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'benar' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<HasilKuesioner, $this>
     */
    public function hasilKuesioner(): BelongsTo
    {
        return $this->belongsTo(HasilKuesioner::class, 'hasil_kuesioner_id', 'hasil_kuesioner_id');
    }

    /**
     * @return BelongsTo<Soal, $this>
     */
    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class, 'soal_id', 'soal_id');
    }

    /**
     * @return BelongsTo<OpsiJawaban, $this>
     */
    public function opsiJawaban(): BelongsTo
    {
        return $this->belongsTo(OpsiJawaban::class, 'opsi_jawaban_id', 'opsi_jawaban_id');
    }
}
