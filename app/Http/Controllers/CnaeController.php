<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CnaeController extends Controller
{
    /**
     * Search CNAE classes.
     *
     * GET /api/cnae/classes?search={term}
     */
    public function searchClasses(Request $request)
    {
        $search = $request->input('search', '');
        
        try {
            $response = Http::timeout(10)->get('https://servicodados.ibge.gov.br/api/v2/cnae/classes');

            if ($response->failed()) {
                return response()->json(['error' => 'Erro ao buscar CNAEs'], 500);
            }

            $classes = $response->json();

            // Filter by search term if provided
            if (!empty($search)) {
                $searchLower = mb_strtolower($search);
                $classes = array_filter($classes, function ($class) use ($searchLower) {
                    $id = mb_strtolower($class['id'] ?? '');
                    $descricao = mb_strtolower($class['descricao'] ?? '');
                    return str_contains($id, $searchLower) || str_contains($descricao, $searchLower);
                });
            }

            // Format response
            $formatted = array_map(function ($class) {
                return [
                    'id' => $class['id'] ?? '',
                    'descricao' => $class['descricao'] ?? '',
                    'label' => ($class['id'] ?? '') . ' - ' . ($class['descricao'] ?? ''),
                ];
            }, array_values($classes));

            // Limit to 100 results
            $formatted = array_slice($formatted, 0, 100);

            return response()->json($formatted);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao buscar CNAEs: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get CNAE class by ID.
     *
     * GET /api/cnae/classes/{id}
     */
    public function getClass(string $id)
    {
        try {
            $response = Http::timeout(10)->get("https://servicodados.ibge.gov.br/api/v2/cnae/classes/{$id}");

            if ($response->failed()) {
                return response()->json(['error' => 'CNAE não encontrado'], 404);
            }

            $class = $response->json();

            return response()->json([
                'id' => $class['id'] ?? '',
                'descricao' => $class['descricao'] ?? '',
                'label' => ($class['id'] ?? '') . ' - ' . ($class['descricao'] ?? ''),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao buscar CNAE'], 500);
        }
    }

    /**
     * Get subclasses for a class.
     *
     * GET /api/cnae/classes/{id}/subclasses
     */
    public function getSubclasses(string $id)
    {
        try {
            $response = Http::timeout(10)->get("https://servicodados.ibge.gov.br/api/v2/cnae/classes/{$id}/subclasses");

            if ($response->failed()) {
                return response()->json(['error' => 'Subclasses não encontradas'], 404);
            }

            $subclasses = $response->json();

            $formatted = array_map(function ($subclass) {
                return [
                    'id' => $subclass['id'] ?? '',
                    'descricao' => $subclass['descricao'] ?? '',
                    'label' => ($subclass['id'] ?? '') . ' - ' . ($subclass['descricao'] ?? ''),
                ];
            }, $subclasses);

            return response()->json($formatted);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao buscar subclasses'], 500);
        }
    }
}

