<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Categorie;

class CategorieController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $categorie = Categorie::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Categorie créée avec succès',
            'data' => $categorie
        ], 201);
    }
}
