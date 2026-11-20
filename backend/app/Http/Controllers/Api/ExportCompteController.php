<?php

namespace App\Http\Controllers\Api;

use App\Babel\ExportCompte;
use App\Babel\ImportCompte;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImporterCompteRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

/**
 * M11 exporter, M12 importer. Le travail est dans App\Babel\ExportCompte et ImportCompte : le contrôleur
 * ne fait que relier la requête HTTP à ces deux classes (et ils se testent sans HTTP).
 */
#[Middleware('auth:sanctum')]
class ExportCompteController extends Controller
{
    /**
     * Un fichier JSON à télécharger (Content-Disposition: attachment). Jamais en cache : ce sont des données
     * personnelles (no-store).
     */
    public function export(Request $request, ExportCompte $export): JsonResponse
    {
        $fichier = 'babel-export-'.now()->format('Y-m-d').'.json';

        return response()
            ->json($export->pour($this->membre($request)), 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Content-Disposition', 'attachment; filename="'.$fichier.'"')
            ->header('Cache-Control', 'no-store, private');
    }

    /** 201 : des marque-pages, collections et annotations sont créés. Le rapport dit combien. */
    public function import(ImporterCompteRequest $request, ImportCompte $import): JsonResponse
    {
        $rapport = $import->importer($this->membre($request), $request->validated());

        return response()->json(['data' => $rapport], 201);
    }

    private function membre(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
