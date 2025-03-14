<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Notifications\StockBas;
use Illuminate\Support\Facades\Notification;
use App\Models\User;

class StockController extends Controller
{
    /**
     * Afficher tous les stocks
     */
    public function index()
    {
        $stocks = Stock::with('produit')->get();
        return response()->json([
            'status' => 'success',
            'data' => $stocks
        ]);
    }

    /**
     * Mettre à jour le stock d'un produit
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantite' => 'required|integer|min:0',
            'seuil_alerte' => 'sometimes|required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $stock = Stock::where('produit_id', $id)->first();

        if (!$stock) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stock non trouvé pour ce produit'
            ], 404);
        }

        $ancienneQuantite = $stock->quantite;
        $stock->quantite = $request->quantite;

        if ($request->has('seuil_alerte')) {
            $stock->seuil_alerte = $request->seuil_alerte;
        }

        $stock->save();

        // Vérifier si le stock est bas et envoyer une notification si nécessaire
        $this->verifierStockBas($stock);

        return response()->json([
            'status' => 'success',
            'message' => 'Stock mis à jour avec succès',
            'data' => $stock->load('produit'),
            'mouvement' => [
                'avant' => $ancienneQuantite,
                'apres' => $stock->quantite,
                'difference' => $stock->quantite - $ancienneQuantite
            ]
        ]);
    }

    /**
     * Ajouter ou retirer des quantités du stock
     */
    public function ajusterQuantite(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantite' => 'required|integer',
            'type' => 'required|in:ajout,retrait'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $stock = Stock::where('produit_id', $id)->first();

        if (!$stock) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stock non trouvé pour ce produit'
            ], 404);
        }

        $ancienneQuantite = $stock->quantite;

        if ($request->type === 'ajout') {
            $stock->quantite += $request->quantite;
        } else {
            // Vérifier que le retrait ne rend pas le stock négatif
            if ($stock->quantite < $request->quantite) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Quantité insuffisante en stock'
                ], 400);
            }
            $stock->quantite -= $request->quantite;
        }

        $stock->save();

        // Vérifier si le stock est bas et envoyer une notification si nécessaire
        $this->verifierStockBas($stock);

        return response()->json([
            'status' => 'success',
            'message' => 'Stock ajusté avec succès',
            'data' => $stock->load('produit'),
            'mouvement' => [
                'avant' => $ancienneQuantite,
                'apres' => $stock->quantite,
                'difference' => $stock->quantite - $ancienneQuantite
            ]
        ]);
    }

    /**
     * Afficher tous les produits dont le stock est bas
     */
    public function stocksBas()
    {
        $stocksBas = Stock::whereRaw('quantite <= seuil_alerte')
            ->with('produit')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $stocksBas
        ]);
    }

    /**
     * Vérifier si le stock est bas et envoyer une notification aux admins
     */
    private function verifierStockBas(Stock $stock)
    {
        if ($stock->quantite <= $stock->seuil_alerte) {
            // Récupérer tous les administrateurs
            $admins = User::where('role', 'admin')->get();

            // Envoyer une notification à tous les administrateurs
            // Notification::send($admins, new StockBas($stock));

            return true;
        }

        return false;
    }
}
