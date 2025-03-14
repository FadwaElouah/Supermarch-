<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Produit;
use App\Models\User;
use App\Models\Notification;

class CheckLowStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {

        $lowStockProducts = Produit::whereHas('stock', function ($query) {
            $query->whereRaw('quantite <= seuil_alerte');
        })->with(['stock'])->get();


        $admins = User::where('role', 'admin')->get();


        foreach ($lowStockProducts as $product) {
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'title' => 'Alerte de stock faible',
                    'message' => "Le produit {$product->nom} doit être réapprovisionné",
                    'type' => 'low_stock',
                    'data' => [
                        'product_id' => $product->id,
                        'current_quantity' => $product->stock->quantite,
                        'threshold' => $product->stock->seuil_alerte
                    ]
                ]);
            }
        }
    }
}
