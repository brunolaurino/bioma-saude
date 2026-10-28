<?php

namespace App\Domain;

use App\Models\Patient;
use App\Models\StoredFile;
use App\Security\Crypto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Upload de laudos, fotos, exames e planos. Aceita só PDF, JPEG e PNG, até 15 MB, conferindo o
 * tipo real pelo conteúdo. Imagens são redesenhadas no servidor (some GPS e outros metadados).
 * Tudo é gravado cifrado com a chave do paciente, com nome aleatório, fora de public/.
 */
class Files
{
    private const TYPES = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

    public static function store(UploadedFile $upload, Patient $patient, string $kind, array $attrs = [], string $field = 'arquivo'): StoredFile
    {
        if (! $upload->isValid()) {
            throw ValidationException::withMessages([$field => 'Não foi possível receber o arquivo. Tente de novo.']);
        }
        if ($upload->getSize() > config('bioma.uploads.max_kb') * 1024) {
            throw ValidationException::withMessages([$field => 'O arquivo passa de 15 MB. Envie um arquivo menor.']);
        }

        $path = $upload->getRealPath();
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);

        if ($mime === 'image/heic' || $mime === 'image/heif') {
            throw ValidationException::withMessages([$field => 'Fotos HEIC ainda não são aceitas. No iPhone, use Ajustes > Câmera > Formatos > Mais compatível, ou envie em JPG.']);
        }
        if (! isset(self::TYPES[$mime])) {
            throw ValidationException::withMessages([$field => 'Envie um PDF, JPG ou PNG.']);
        }

        $clean = tempnam(sys_get_temp_dir(), 'bio');
        try {
            if ($mime === 'application/pdf') {
                if (file_get_contents($path, false, null, 0, 5) !== '%PDF-') {
                    throw ValidationException::withMessages([$field => 'Este PDF parece corrompido. Gere o arquivo de novo.']);
                }
                self::scan($path, $field);
                copy($path, $clean);
            } else {
                self::reencodeImage($path, $mime, $clean, $field);
                $mime = 'image/jpeg';
            }

            $file = new StoredFile([
                'patient_id' => $patient->id,
                'kind' => $kind,
                'mime' => $mime,
                'size' => filesize($clean),
                'sha256' => hash_file('sha256', $clean),
                'storage_name' => Str::random(40),
                'uploaded_by' => auth()->id(),
            ] + $attrs);
            $file->id = $file->newUniqueId();
            $file->name = self::safeName($upload->getClientOriginalName(), $mime === 'application/pdf' ? 'pdf' : 'jpg');

            $dir = self::dir();
            Crypto::encryptFile($patient->id, $file->id, $clean, $dir.'/'.$file->storage_name);
            $file->save();

            return $file;
        } finally {
            @unlink($clean);
        }
    }

    /** Entrega o arquivo decifrado. PDF sai como anexo; imagem pode abrir na tela. */
    public static function response(StoredFile $file, bool $inline = false): StreamedResponse
    {
        $source = self::dir().'/'.$file->storage_name;
        abort_unless(is_file($source), 404);

        $tmp = tempnam(sys_get_temp_dir(), 'bio');
        Crypto::decryptFile($file->patient_id, $file->id, $source, $tmp);

        $download = 'bioma-'.$file->kind.'-'.$file->taken_on->format('Y-m-d').'.'.($file->mime === 'application/pdf' ? 'pdf' : 'jpg');
        $disposition = ($inline && str_starts_with($file->mime, 'image/') ? 'inline' : 'attachment').'; filename="'.$download.'"';

        return response()->stream(function () use ($tmp) {
            readfile($tmp);
            @unlink($tmp);
        }, 200, [
            'Content-Type' => $file->mime,
            'Content-Length' => (string) filesize($tmp),
            'Content-Disposition' => $disposition,
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Decifra para um caminho temporário (exportação). Quem chama apaga depois. */
    public static function decryptTo(StoredFile $file, string $target): void
    {
        Crypto::decryptFile($file->patient_id, $file->id, self::dir().'/'.$file->storage_name, $target);
    }

    /** Apaga o arquivo do disco (eliminação). O registro some junto. */
    public static function purge(StoredFile $file): void
    {
        @unlink(self::dir().'/'.$file->storage_name);
        $file->forceDelete();
    }

    private static function reencodeImage(string $path, string $mime, string $target, string $field): void
    {
        $info = @getimagesize($path);
        if (! $info || $info[0] < 16 || $info[1] < 16 || $info[0] * $info[1] > 60_000_000) {
            throw ValidationException::withMessages([$field => 'Não foi possível ler esta imagem.']);
        }

        $img = $mime === 'image/png' ? @imagecreatefrompng($path) : @imagecreatefromjpeg($path);
        if (! $img) {
            throw ValidationException::withMessages([$field => 'Não foi possível ler esta imagem.']);
        }

        // Respeita a orientação da câmera antes de descartar os metadados.
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $o = (int) (@exif_read_data($path)['Orientation'] ?? 1);
            $img = match ($o) {
                3 => imagerotate($img, 180, 0),
                6 => imagerotate($img, -90, 0),
                8 => imagerotate($img, 90, 0),
                default => $img,
            };
        }

        $max = config('bioma.uploads.image_max_px');
        $w = imagesx($img);
        $h = imagesy($img);
        if (max($w, $h) > $max) {
            $scale = $max / max($w, $h);
            $img = imagescale($img, (int) round($w * $scale), (int) round($h * $scale));
        }

        // Fundo branco para PNG com transparência virar JPEG.
        $out = imagecreatetruecolor(imagesx($img), imagesy($img));
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
        imagecopy($out, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
        imagejpeg($out, $target, 85);
    }

    /** Antivírus (ClamAV via clamd) quando configurado. Sem clamd, registra que não houve varredura. */
    private static function scan(string $path, string $field): void
    {
        $host = config('bioma.clamav_host');
        if (! $host) {
            Log::warning('Upload de PDF sem antivírus: BIOMA_CLAMAV_HOST não configurado.');

            return;
        }

        $sock = @fsockopen($host, (int) config('bioma.clamav_port', 3310), $errno, $errstr, 5);
        if (! $sock) {
            throw ValidationException::withMessages([$field => 'O antivírus não respondeu. Tente de novo em alguns minutos.']);
        }
        fwrite($sock, "zINSTREAM\0");
        $fh = fopen($path, 'rb');
        while (! feof($fh)) {
            $chunk = fread($fh, 8192);
            fwrite($sock, pack('N', strlen($chunk)).$chunk);
        }
        fclose($fh);
        fwrite($sock, pack('N', 0));
        $reply = trim((string) fgets($sock));
        fclose($sock);

        if (! str_ends_with(rtrim($reply, "\0"), 'OK')) {
            Log::warning('Upload recusado pelo antivírus.');
            throw ValidationException::withMessages([$field => 'O antivírus recusou este arquivo.']);
        }
    }

    private static function safeName(string $original, string $ext): string
    {
        $base = Str::of(pathinfo($original, PATHINFO_FILENAME))->ascii()->replaceMatches('/[^A-Za-z0-9._-]+/', '_')->limit(80, '')->toString();

        return ($base !== '' ? $base : 'arquivo').'.'.$ext;
    }

    private static function dir(): string
    {
        $dir = storage_path('app/private/files');
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        return $dir;
    }
}
