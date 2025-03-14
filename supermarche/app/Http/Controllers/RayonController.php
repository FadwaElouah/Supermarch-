<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Rayon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class RayonController extends Controller
{
    /**
     * Afficher la liste de tous les rayons
     */
    public function index()
    {
        $rayons = Rayon::with('produits')->get();
        return response()->json([
            'status' => 'success',
            'data' => $rayons
        ]);
    }

    /**
     * Enregistrer un nouveau rayon
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['nom', 'description']);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('rayons', 'public');
            $data['image_path'] = $path;
        }

        $rayon = Rayon::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Rayon créé avec succès',
            'data' => $rayon
        ], 201);
    }

    /**
     * Afficher un rayon spécifique
     */
    public function show($id)
    {
        $rayon = Rayon::with('produits')->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $rayon
        ]);
    }

    /**
     * Mettre à jour un rayon
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $rayon = Rayon::findOrFail($id);
        $data = $request->only(['nom', 'description']);

        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image si elle existe
            if ($rayon->image_path) {
                Storage::disk('public')->delete($rayon->image_path);
            }
            $path = $request->file('image')->store('rayons', 'public');
            $data['image_path'] = $path;
        }

        $rayon->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Rayon mis à jour avec succès',
            'data' => $rayon
        ]);
    }

    /**
     * Supprimer un rayon
     */
    public function destroy($id)
    {
        $rayon = Rayon::findOrFail($id);

        // Supprimer l'image si elle existe
        if ($rayon->image_path) {
            Storage::disk('public')->delete($rayon->image_path);
        }

        $rayon->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Rayon supprimé avec succès'
        ]);
    }
}
