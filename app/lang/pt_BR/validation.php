<?php

// Mensagens padrão em português. Os formulários do Bioma CD já trazem mensagens próprias para
// os campos principais; estas cobrem o resto.
return [
    'accepted' => 'Marque :attribute para continuar.',
    'array' => 'Confira :attribute.',
    'between' => [
        'numeric' => 'Use um valor de :min a :max em :attribute.',
        'string' => ':attribute precisa ter de :min a :max caracteres.',
        'file' => 'O arquivo precisa ter de :min a :max KB.',
        'array' => 'Escolha de :min a :max itens em :attribute.',
    ],
    'boolean' => 'Confira :attribute.',
    'confirmed' => 'A confirmação de :attribute não confere.',
    'date' => 'Use uma data válida em :attribute.',
    'email' => 'Digite um e-mail válido.',
    'file' => 'Escolha um arquivo.',
    'in' => 'Escolha uma opção válida em :attribute.',
    'integer' => 'Use um número inteiro em :attribute.',
    'max' => [
        'numeric' => ':attribute pode ser no máximo :max.',
        'string' => ':attribute pode ter no máximo :max caracteres.',
        'file' => 'O arquivo pode ter no máximo :max KB.',
        'array' => 'Escolha no máximo :max itens em :attribute.',
    ],
    'min' => [
        'numeric' => ':attribute precisa ser pelo menos :min.',
        'string' => ':attribute precisa ter pelo menos :min caracteres.',
        'file' => 'O arquivo precisa ter pelo menos :min KB.',
        'array' => 'Escolha pelo menos :min itens em :attribute.',
    ],
    'numeric' => 'Use um número em :attribute.',
    'regex' => 'Confira o formato de :attribute.',
    'required' => 'Preencha :attribute.',
    'required_if' => 'Preencha :attribute.',
    'string' => 'Confira :attribute.',
    'uploaded' => 'Não foi possível receber o arquivo. Tente de novo.',

    'attributes' => [
        'name' => 'o nome', 'email' => 'o e-mail', 'phone' => 'o celular', 'password' => 'a senha',
        'birth_date' => 'a data de nascimento', 'objective' => 'o objetivo', 'weight' => 'o peso meta',
        'water' => 'a meta de água', 'protein' => 'a meta de proteína', 'day' => 'a data', 'time' => 'o horário',
        'type' => 'o tipo', 'text' => 'o texto', 'note' => 'a observação', 'status' => 'a situação',
        'specialty' => 'a especialidade', 'council_number' => 'o registro no conselho', 'response' => 'a resposta',
        'description' => 'a descrição', 'affected_count' => 'o número de titulares', 'risk' => 'o risco',
        'water_ml_per_kg' => 'a água por kg', 'protein_g_per_kg' => 'a proteína por kg', 'bio_interval_days' => 'o intervalo da bioimpedância',
        'clinic_name' => 'o nome do consultório', 'controller_name' => 'o controlador', 'dpo_name' => 'o encarregado', 'dpo_contact' => 'o e-mail do encarregado',
        'detected_on' => 'a data', 'height' => 'a altura', 'details' => 'os detalhes', 'patient_id' => 'o paciente', 'guardian_name' => 'o responsável',
    ],
];
