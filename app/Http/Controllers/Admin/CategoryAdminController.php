<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryAdminController extends Controller
{
    public function index()
    {
        return response()->json(
            Category::withCount(['demandes', 'prestataireProfiles'])->orderBy('nom')->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom'   => 'required|string|max:100|unique:categories,nom',
            'icone' => 'nullable|string|max:50',
        ]);
        return response()->json(Category::create($request->only(['nom', 'icone'])), 201);
    }

    public function update(Request $request, int $id)
    {
        $cat = Category::findOrFail($id);
        $request->validate([
            'nom'   => "sometimes|string|max:100|unique:categories,nom,$id",
            'icone' => 'nullable|string|max:50',
        ]);
        $cat->update($request->only(['nom', 'icone']));
        return response()->json($cat);
    }

    public function destroy(int $id)
    {
        $cat = Category::withCount(['demandes', 'prestataireProfiles'])->findOrFail($id);

        if ($cat->demandes_count > 0 || $cat->prestataire_profiles_count > 0) {
            return response()->json([
                'message' => 'Catégorie utilisée par des demandes ou des prestataires — suppression impossible',
            ], 422);
        }

        $cat->delete();
        return response()->json(['message' => 'Catégorie supprimée']);
    }
}
