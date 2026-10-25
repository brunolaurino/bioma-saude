<?php

namespace App\Http\Controllers\Patient;

use App\Domain\Catalog;
use App\Domain\Files;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExamsController extends Controller
{
    public function create()
    {
        return view('patient.exams-create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'label' => ['required', Rule::in(Catalog::EXAM_KINDS)],
            'taken_on' => ['required', 'string', 'max:10'],
            'arquivo' => ['required', 'file'],
        ], ['arquivo.required' => 'Escolha a foto ou o PDF do exame.', 'label.*' => 'Escolha o tipo de exame.']);

        $day = BioController::parseDay($request->input('taken_on'), 'taken_on');
        Files::store($request->file('arquivo'), $this->patient(), 'exame', ['taken_on' => $day->toDateString(), 'label' => $request->input('label')]);

        return redirect()->route('evolution', ['aba' => 'exames'])->with('status', 'Exame anexado.');
    }
}
