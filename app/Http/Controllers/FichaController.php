<?php

namespace App\Http\Controllers;

use App\Models\Ficha;
use App\Models\Programa;
use App\Models\Competencia;
use Illuminate\Http\Request;

class FichaController extends Controller
{
    public function index()
    {
        $fichas = Ficha::with(['programa', 'competencia'])->paginate(15);
        return view('fichas.index', compact('fichas'));
    }

    public function create()
    {
        $programas = Programa::all();
        $competencias = Competencia::all();
        return view('fichas.create', compact('programas', 'competencias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'Id_Ficha' => 'required|integer|unique:ficha,Id_Ficha',
            'Id_Programa' => 'required|exists:programa,Id_Programa',
        ]);

        $data = $request->all();
        $data['Jornada'] = $data['Jornada'] ?? 'DIURNA';
        $data['Id_Competencia'] = $data['Id_Competencia'] ?? \App\Models\Competencia::first()->Id_Competencia ?? 1;

        Ficha::create($data);

        return redirect()->route('fichas.index')->with('success', 'Ficha creada correctamente.');
    }

    public function edit($id)
    {
        $ficha = Ficha::findOrFail($id);
        $programas = Programa::all();
        $competencias = Competencia::all();
        return view('fichas.edit', compact('ficha', 'programas', 'competencias'));
    }

    public function update(Request $request, $id)
    {
        $ficha = Ficha::findOrFail($id);
        
        $request->validate([
            'Id_Ficha' => 'required|integer|unique:ficha,Id_Ficha,' . $id . ',Id_Ficha',
            'Id_Programa' => 'required|exists:programa,Id_Programa',
        ]);

        $ficha->update($request->only(['Id_Ficha', 'Id_Programa']));

        return redirect()->route('fichas.index')->with('success', 'Ficha actualizada correctamente.');
    }

   public function destroy($id)
{
    $ficha = Ficha::findOrFail($id);

    foreach ($ficha->aprendices as $aprendiz) {
        // eliminar juicios primero
        $aprendiz->juicios()->delete();

        // luego aprendiz
        $aprendiz->delete();
    }

    // por último ficha
    $ficha->delete();

    return redirect()->route('fichas.index')
        ->with('success', 'Ficha eliminada correctamente.');
}
}
