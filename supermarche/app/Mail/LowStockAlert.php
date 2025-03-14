<?php
// app/Mail/LowStockAlert.php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowStockAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $product;

    public function __construct($product)
    {
        $this->product = $product;
    }

    public function build()
    {
        return $this->subject('Alerte de stock faible')
                    ->view('emails.low_stock')
                    ->with([
                        'productName' => $this->product->nom,
                        'currentQuantity' => $this->product->stock->quantite,
                        'threshold' => $this->product->stock->seuil_alerte
                    ]);
    }
}
