<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Models\Produit;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\LowStockAlert;




class ProduitController extends Controller
{
   /**
 * Récupérer les produits par rayon
 */


 /**
 * Récupérer les produits populaires ou en promotion d'un rayon spécifique
 */
public function getSpecialProductsByRayon(Request $request, $rayon_id)
{
    // Vérifier si le rayon existe
    $rayon = \App\Models\Rayon::find($rayon_id);
    if (!$rayon) {
        return response()->json([
            'status' => 'error',
            'message' => 'Rayon non trouvé'
        ], 404);
    }

    // Préparer la requête de base
    $query = Produit::where('rayon_id', $rayon_id);

    // Filtrer selon le type demandé (populaires ou promotions)
    if ($request->has('type')) {
        if ($request->type === 'populaires') {
            $query->where('est_populaire', true);
        } elseif ($request->type === 'promotions') {
            $query->where('est_en_promotion', true);
        }
    } else {
        // Si aucun type spécifié, retourner les deux types
        $query->where(function($q) {
            $q->where('est_populaire', true)
              ->orWhere('est_en_promotion', true);
        });
    }

    // Récupérer les produits avec leurs relations
    $produits = $query->with(['categorie', 'stock'])->get();

    return response()->json([
        'status' => 'success',
        'data' => [
            'rayon' => $rayon->nom,
            'produits' => $produits
        ]
    ]);
}
    public function getProduitsByRayon($rayon_id)
{
    // Vérifier si le rayon existe
    $rayon = \App\Models\Rayon::find($rayon_id);
    if (!$rayon) {
        return response()->json([
            'status' => 'error',
            'message' => 'Rayon non trouvé'
        ], 404);
    }

    // Récupérer les produits de ce rayon avec leur stock
    $produits = Produit::where('rayon_id', $rayon_id)
        ->with(['categorie', 'stock'])
        ->get();

    return response()->json([
        'status' => 'success',
        'data' => [
            'rayon' => $rayon->nom,
            'produits' => $produits
        ]
    ]);
}


/**
 * Recherche de produits par nom ou catégorie
 */
public function search(Request $request)
{
    // Vérification de la validité des données saisies
    $validator = Validator::make($request->all(), [
        'search' => 'required|string|min:2',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Veuillez entrer un terme de recherche valide (au moins deux caractères)',
            'errors' => $validator->errors()
        ], 422);
    }

    $searchTerm = $request->search;

    // Recherche dans la base de données
    $produits = Produit::where(function ($query) use ($searchTerm) {
        // Recherche par nom du produit
        $query->where('nom', 'like', '%' . $searchTerm . '%')
              ->orWhere('description', 'like', '%' . $searchTerm . '%');
    })
    ->orWhereHas('categorie', function ($query) use ($searchTerm) {
        // Recherche par nom de la catégorie
        $query->where('nom', 'like', '%' . $searchTerm . '%');
    })
    ->with(['rayon', 'categorie', 'stock'])
    ->get();

    return response()->json([
        'status' => 'success',
        'count' => count($produits),
        'data' => $produits
    ]);
}



    /**
     * Afficher la liste de tous les produits
     */
    public function index()
    {
        $produits = Produit::with(['rayon', 'categorie', 'stock'])->get();
        return response()->json([
            'status' => 'success',
            'data' => $produits
        ]);
    }




    /**
     * Enregistrer un nouveau produit avec son stock
     */

        public function store(Request $request)
        {

            $validator = Validator::make($request->all(), [

                'rayon_id' => 'required|exists:rayons,id',
                'categorie_id' => 'required|exists:categories,id',
                'nom' => 'required|string|max:255',
                'description' => 'nullable|string',
                'prix' => 'required|numeric|min:0',
                'est_populaire' => 'boolean',
                'est_en_promotion' => 'boolean',
                'prix_promotion' => 'nullable|numeric|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'quantite' => 'required|integer|min:0',
                'seuil_alerte' => 'required|integer|min:1'
            ]);


            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation échouée',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();
            try {
                $produitData = $request->only([
                    'rayon_id', 'categorie_id', 'nom', 'description',
                    'prix', 'est_populaire', 'est_en_promotion', 'prix_promotion'
                ]);


                if ($request->hasFile('image')) {
                    $path = $request->file('image')->store('produits', 'public');
                    $produitData['image_path'] = $path;
                }


                $produit = Produit::create($produitData);


                Stock::create([
                    'produit_id' => $produit->id,
                    'quantite' => $request->quantite,
                    'seuil_alerte' => $request->seuil_alerte
                ]);


                DB::commit();


                return response()->json([
                    'status' => 'success',
                    'message' => 'Produit créé avec succès',
                    'data' => $produit->load(['rayon', 'categorie', 'stock'])
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur lors de la création du produit',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

    /**
     * Afficher un produit spécifique
     */
    public function show($id)
    {
        $produit = Produit::with(['rayon', 'categorie', 'stock'])->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $produit
        ]);
    }

    /**
     * Mettre à jour un produit
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            "rayon_id" => "sometimes|exists:rayons,id",
            "categorie_id" => "sometimes|exists:categories,id",
            "nom" => "sometimes|string|max:255",
            "description" => "nullable|string",
            "prix" => "sometimes|numeric|min:0",
            "est_populaire" => "boolean",
            "est_en_promotion" => "boolean",
            "prix_promotion" => "nullable|numeric|min:0",
            "image" => "nullable|image|mimes:jpeg,png,jpg,gif|max:2048",
            "quantite" => "sometimes|integer|min:0",
            "seuil_alerte" => "sometimes|integer|min:1"
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $produit = Produit::findOrFail($id);
            $produitData = $request->only([
                'rayon_id', 'categorie_id', 'nom', 'description',
                'prix', 'est_populaire', 'est_en_promotion', 'prix_promotion'
            ]);

            if ($request->hasFile('image')) {
                if ($produit->image_path) {
                    Storage::disk('public')->delete($produit->image_path);
                }
                $path = $request->file('image')->store('produits', 'public');
                $produitData['image_path'] = $path;
            }

            $produit->update($produitData);

            if ($request->has('quantite') || $request->has('seuil_alerte')) {
                $stock = $produit->stock;
                if (!$stock) {
                    $stock = new Stock(['produit_id' => $produit->id]);
                }

                if ($request->has('quantite')) {
                    $stock->quantite = $request->quantite;
                }

                if ($request->has('seuil_alerte')) {
                    $stock->seuil_alerte = $request->seuil_alerte;
                }

                $stock->save();
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Produit mis à jour avec succès',
                'data' => $produit->load(['rayon', 'categorie', 'stock'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour du produit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un produit
     */
    public function destroy($id)
    {
        $produit = Produit::findOrFail($id);

        // Supprimer l'image si elle existe
        if ($produit->image_path) {
            Storage::disk('public')->delete($produit->image_path);
        }

        $produit->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Produit supprimé avec succès'
        ]);
    }

    /**
     * Obtenir les produits populaires
     */
    public function getPopulaires()
    {
        $produits = Produit::where('est_populaire', true)
            ->with(['rayon', 'categorie', 'stock'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $produits
        ]);
    }

    /**
     * Obtenir les produits en promotion
     */
    public function getPromotions()
    {
        $produits = Produit::where('est_en_promotion', true)
            ->with(['rayon', 'categorie', 'stock'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $produits
        ]);
    }

    /**
     * Obtenir les produits avec un stock faible
     */
    public function getStocksFaibles()
    {
        $produits = Produit::whereHas('stock', function ($query) {
                $query->whereRaw('quantite <= seuil_alerte');
            })
            ->with(['rayon', 'categorie', 'stock'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $produits
        ]);
    }


    public function checkLowStockProducts()
    {
        try {
            $lowStockProducts = Produit::whereHas('stock', function ($query) {
                $query->whereRaw('quantite <= seuil_alerte');
            })->with(['stock'])->get();

            $admins = User::where('role', 'admin')->get();

            foreach ($lowStockProducts as $product) {
                foreach ($admins as $admin) {
                    // Créer une notification dans la base de données
                    Notification::create([
                        'user_id' => $admin->id,
                        'title' => 'Alerte de stock faible',
                        'message' => "Le produit {$product->nom} a besoin d’être réapprovisionné",
                        'type' => 'low_stock',
                        'data' => [
                            'product_id' => $product->id,
                            'current_quantity' => $product->stock->quantite,
                            'threshold' => $product->stock->seuil_alerte
                        ]
                    ]);

                    // Envoi d'un e-mail (optionnel)
                    try {
                        Mail::to($admin->email)->send(
                            new LowStockAlert($product)
                        );
                    } catch (\Exception $e) {
                        // Enregistrer l'erreur sans interrompre le processus
                        \Log::error('Échec de l’envoi de l’e-mail : ' . $e->getMessage());
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Vérification du stock effectuée'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur s’est produite lors de la vérification du stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
