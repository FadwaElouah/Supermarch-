<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RayonController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\NotificationController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
// Route::get('/login', function () {
//     return redirect('/api/login');
// })->name('login');

Route::get('/', function () {
    return view('welcome');
})->name('home');
Route::get('/rayons/{rayon_id}/produits', [\App\Http\Controllers\ProduitController::class, 'getProduitsByRayon']);

Route::get('/rayons/{rayon_id}/specials', [ProduitController::class, 'getSpecialProductsByRayon']);

Route::get('/produits/search', [ProduitController::class, 'search']);

// Route::get('/admin/produits', [ProduitController::class, 'index']);

Route::get('/rayons', [RayonController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {



    Route::post('/logout', [AuthController::class, 'logout']);


    Route::get('/user', function (Request $request) {
        return $request->user();

    });


    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // Routes admin
    Route::middleware(['admin','auth:sanctum'])->prefix('admin')->group(function () {

        Route::get('/produits/populaires', [ProduitController::class, 'getPopulaires']);
        Route::get('/produits/promotions', [ProduitController::class, 'getPromotions']);
        Route::get('/produits/stocks-faibles', [ProduitController::class, 'getStocksFaibles']);

        Route::get('/produits/check-low-stock', [ProduitController::class, 'checkLowStockProducts']);

        Route::apiResource('rayons', RayonController::class);
        Route::apiResource('produits', ProduitController::class);
        Route::apiResource('categories', CategorieController::class);

    });
});
