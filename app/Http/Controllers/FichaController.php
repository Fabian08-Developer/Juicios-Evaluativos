<?php

namespace App\Http\Controllers;

use App\Models\Aprendiz;
use App\Models\Ficha;
use App\Models\Programa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FichaController extends Controller
{
    /** Jornadas de formación que ofrece el SENA. */
    private const JORNADAS = ['DIURNA', 'NOCTURNA', 'MIXTA', 'MADRUGADA', 'FINES DE SEMANA'];

    public function index()
    {
<<<<<<< Updated upstream
        $fichas = Ficha::with('programa')->paginate(15);
=======
        $fichas = Ficha::with(['programa', 'competencia'])->withCount('aprendices')->paginate(15);
>>>>>>> Stashed changes
        return view('fichas.index', compact('fichas'));
    }

    public function create()
    {
        $programas = Programa::all();
        return view('fichas.create', compact('programas'));
    }

    public function store(Request $request)
    {
        // Id_Ficha es integer en aprendiz.Id_Ficha: no puede superar 2147483647.
        $data = $request->validate([
            'Id_Ficha'    => 'required|integer|min:1|max:2147483647|unique:ficha,Id_Ficha',
            'Id_Programa' => 'required|exists:programa,Id_Programa',
            'Jornada'     => 'nullable|string|in:' . implode(',', self::JORNADAS),
        ]);

        $data['Jornada'] = $data['Jornada'] ?? 'DIURNA';

        Ficha::create($data);

        return redirect()->route('fichas.index')->with('success', 'Ficha creada correctamente.');
    }

    public function edit($id)
    {
        $ficha = Ficha::findOrFail($id);
        $programas = Programa::all();
        return view('fichas.edit', compact('ficha', 'programas'));
    }

    public function update(Request $request, $id)
    {
        $ficha = Ficha::findOrFail($id);

        // El número de ficha es la llave primaria y viene de Sofia Plus: no se edita
        // (aprendices e importaciones dependen de él).
        $data = $request->validate([
            'Id_Programa' => 'required|exists:programa,Id_Programa',
            'Jornada'     => 'nullable|string|in:' . implode(',', self::JORNADAS),
        ]);

        $ficha->update(array_filter($data, fn ($v) => $v !== null));

        return redirect()->route('fichas.index')->with('success', 'Ficha actualizada correctamente.');
    }

    public function destroy($id)
    {
        $ficha = Ficha::findOrFail($id);

        // Todo o nada: si algo falla a mitad, no queda la ficha a medio borrar.
        DB::transaction(function () use ($ficha) {
            $aprendizIds = Aprendiz::where('Id_Ficha', $ficha->Id_Ficha)->pluck('Id_Aprendiz');

            DB::table('juicios_evaluativos')->whereIn('Id_Aprendiz', $aprendizIds)->delete();
            Aprendiz::whereIn('Id_Aprendiz', $aprendizIds)->delete();
            $ficha->delete();
        });

        Cache::forget("dashboard.stats.ficha.{$ficha->Id_Ficha}");
        Cache::forget('dashboard.stats.global');

        return redirect()->route('fichas.index')
            ->with('success', 'Ficha eliminada correctamente.');
    }
}
