<?php

namespace Tests\Feature;

use App\Models\StoredFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    private function pdf(): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($tmp, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n");

        return new UploadedFile($tmp, 'exame.pdf', 'application/pdf', null, true);
    }

    public function test_exame_em_pdf_fica_cifrado_no_disco(): void
    {
        $this->demo();
        $this->asPatient('ana@demo.bioma.local');
        $this->post(route('exams.store'), ['label' => 'Exame de sangue', 'taken_on' => '20/10/2026', 'arquivo' => $this->pdf()])->assertRedirect();

        $file = StoredFile::where('kind', 'exame')->latest()->first();
        $raw = file_get_contents(storage_path('app/private/files/'.$file->storage_name));
        $this->assertStringNotContainsString('%PDF', $raw);

        $this->get(route('files.show', $file))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_arquivo_disfarcado_de_pdf_e_recusado(): void
    {
        $this->demo();
        $this->asPatient('ana@demo.bioma.local');
        $tmp = tempnam(sys_get_temp_dir(), 'x');
        file_put_contents($tmp, '<?php echo "oi";');
        $fake = new UploadedFile($tmp, 'laudo.pdf', 'application/pdf', null, true);

        $this->post(route('exams.store'), ['label' => 'Outro', 'taken_on' => '20/10/2026', 'arquivo' => $fake])->assertSessionHasErrors('arquivo');
    }

    public function test_foto_e_redesenhada_sem_metadados(): void
    {
        $this->demo();
        $this->asPatient('ana@demo.bioma.local');
        $img = imagecreatetruecolor(64, 80);
        $tmp = tempnam(sys_get_temp_dir(), 'jpg');
        imagejpeg($img, $tmp);
        // Comentário no JPEG faz o papel de um metadado que precisa sumir.
        $data = file_get_contents($tmp);
        file_put_contents($tmp, substr($data, 0, 2)."\xFF\xFE\x00\x10GPS-SECRETO-XY".substr($data, 2));
        $up = new UploadedFile($tmp, 'foto.jpg', 'image/jpeg', null, true);

        $this->post(route('exams.store'), ['label' => 'Imagem', 'taken_on' => '20/10/2026', 'arquivo' => $up])->assertRedirect();
        $file = StoredFile::where('kind', 'exame')->latest()->first();
        $out = $this->get(route('files.show', $file))->streamedContent();
        $this->assertStringNotContainsString('GPS-SECRETO', $out);
        $this->assertStringStartsWith("\xFF\xD8", $out);
    }
}
