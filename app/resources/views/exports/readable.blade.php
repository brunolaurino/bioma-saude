<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><title>Meus dados · {{ $clinic }}</title>
<style>body{font:15px/1.5 system-ui,sans-serif;max-width:900px;margin:2rem auto;padding:0 1rem;color:#17211b}h1{font-size:1.6rem}h2{margin-top:2rem;border-bottom:2px solid #17643b;padding-bottom:.25rem}table{border-collapse:collapse;width:100%;font-size:13px}th,td{border:1px solid #ccd5cf;padding:4px 6px;text-align:left;vertical-align:top}th{background:#eef4f0}</style></head>
<body>
<h1>Meus dados no Saúde App</h1>
<p>{{ $patient->name }} · {{ $clinic }} · gerado em {{ now()->setTimezone(config('bioma.timezone'))->format('d/m/Y H:i') }}</p>
<p>Este arquivo traz uma cópia dos seus dados (LGPD, art. 18). Os mesmos dados estão em <code>dados.json</code> e nas planilhas da pasta <code>planilhas</code>. Fotos, exames e planos estão na pasta <code>arquivos</code>.</p>
@foreach ($sections as $name => $rows)
  <h2>{{ ucfirst(str_replace('_', ' ', $name)) }}</h2>
  @if (! $rows)<p>Nenhum registro.</p>@else
  <table><thead><tr>@foreach (array_keys($rows[0]) as $h)<th>{{ str_replace('_', ' ', $h) }}</th>@endforeach</tr></thead>
  <tbody>@foreach ($rows as $r)<tr>@foreach ($r as $v)<td>{{ is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (is_bool($v) ? ($v ? 'sim' : 'não') : $v) }}</td>@endforeach</tr>@endforeach</tbody></table>
  @endif
@endforeach
</body></html>
