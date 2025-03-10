<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    use HasFactory;

    protected $fillable = [
        'rayon_id', 'categorie_id', 'nom', 'description', 'prix', 'image_path',
        'est_populaire', 'est_en_promotion', 'prix_promotion'
    ];

    public function rayon()
    {
        return $this->belongsTo(Rayon::class);
    }

    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }

    public function stock()
    {
        return $this->hasOne(Stock::class);
    }
}
