<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rayon extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'description', 'image_path'];

    public function produits()
    {
        return $this->hasMany(Produit::class);
    }
}
