<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\TemplateField;
use Illuminate\Support\Collection;
use setasign\Fpdi\Tcpdf\Fpdi;

class SignedPdfService
{
    /** @param  array<int, array{template_field_id: int, value: string|null}>  $fieldValues */
    public function generate(Submission $submission, array $fieldValues): string
    {

        $document = $submission->document;
        $template = $document?->template;

        if (! $document || ! $template) {
            throw new \RuntimeException('Submission is missing document or template.');
        }

        $media = $template->getFirstMedia('template-pdf');

        if (! $media) {
            throw new \RuntimeException('Template is missing PDF.');
        }

        $fpdi = new Fpdi;
        $pageCount = $fpdi->setSourceFile($media->getPath());

        /** @var Collection<int, TemplateField> $fieldMap */
        $fieldMap = TemplateField::query()
            ->whereIn('id', collect($fieldValues)->pluck('template_field_id'))
            ->get()
            ->keyBy('id');

        $fieldsByPage = collect($fieldValues)->groupBy(function (array $fv) use ($fieldMap): int {
            /** @var TemplateField $field */
            $field = $fieldMap[$fv['template_field_id']];

            return $field->page;
        });

        for ($page = 1; $page <= $pageCount; $page++) {
            $templateId = $fpdi->importPage($page);
            /** @var array{width: float, height: float, orientation: string} $size */
            $size = $fpdi->getTemplateSize($templateId);
            $pageWidth = $size['width'];
            $pageHeight = $size['height'];

            $fpdi->addPage($size['orientation'], [$pageWidth, $pageHeight]);
            $fpdi->useImportedPage($templateId);

            $pageFields = $fieldsByPage->get($page, collect());

            foreach ($pageFields as $fv) {
                /** @var TemplateField $field */
                $field = $fieldMap[$fv['template_field_id']];
                $value = $fv['value'];

                if ($value === null || $value === '') {
                    continue;
                }

                $px = ($field->x / 100) * $pageWidth;
                $py = ($field->y / 100) * $pageHeight;
                $pw = ($field->width / 100) * $pageWidth;
                $ph = ($field->height / 100) * $pageHeight;

                if (in_array($field->type, ['text', 'date', 'dropdown', 'radio'], true)) {
                    $this->renderText($fpdi, $value, $px, $py, $pw, $ph, $field->font_size);
                } elseif ($field->type === 'checkbox') {
                    $this->renderCheckbox($fpdi, $value, $px, $py, $pw, $ph);
                } elseif (in_array($field->type, ['signature', 'initials'], true)) {
                    $this->renderImage($fpdi, $value, $px, $py, $pw, $ph);
                }
            }
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'signed_').'.pdf';
        $fpdi->Output($tempPath, 'F');

        return $tempPath;
    }

    private function renderText(Fpdi $fpdi, string $value, float $x, float $y, float $w, float $h, ?int $fontSize): void
    {
        $size = $fontSize ?? 12;
        $fpdi->SetFont('Helvetica', '', $size);
        $fpdi->SetXY($x, $y);
        $fpdi->MultiCell($w, $h, $value, 0, 'L', false, 1, $x, $y, true, 0, false, true, $h, 'T');
    }

    private function renderCheckbox(Fpdi $fpdi, string $value, float $x, float $y, float $w, float $h): void
    {
        if (! in_array($value, ['1', 'true', 'yes', 'on'], true)) {
            return;
        }

        $fpdi->SetFont('ZapfDingbats', '', min($h * 2.5, 14));
        $fpdi->SetXY($x, $y);
        $fpdi->Cell($w, $h, '4', 0, 0, 'C');
    }

    private function renderImage(Fpdi $fpdi, string $value, float $x, float $y, float $w, float $h): void
    {
        // Strip data URI prefix (e.g. "data:image/png;base64,")
        $base64 = $value;
        if (str_contains($base64, ',')) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
        }

        $imageData = base64_decode($base64, true);
        if ($imageData === false || $imageData === '') {
            return;
        }

        $info = @getimagesizefromstring($imageData);
        if ($info === false) {
            return;
        }

        if (! in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG])) {
            return;
        }

        // Flatten transparency onto white background so TCPDF renders it correctly
        $imageData = $this->flattenTransparency($imageData, $info);

        // Use TCPDF's '@' prefix to pass raw image data directly (no temp file needed)
        $fpdi->Image('@'.$imageData, $x, $y, $w, $h, 'PNG');
    }

    /**
     * Flatten a PNG's alpha channel onto a white background.
     *
     * @param  array{0: int, 1: int, 2: int}  $info
     */
    private function flattenTransparency(string $imageData, array $info): string
    {
        if ($info[2] !== IMAGETYPE_PNG) {
            return $imageData;
        }

        $src = @imagecreatefromstring($imageData);
        if ($src === false) {
            return $imageData;
        }

        $w = imagesx($src);
        $h = imagesy($src);

        $dst = imagecreatetruecolor($w, $h);
        if ($dst === false) {
            imagedestroy($src);

            return $imageData;
        }

        // Fill with white
        $white = (int) imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);

        // Composite the source (with alpha) onto the white background
        imagealphablending($dst, true);
        imagecopy($dst, $src, 0, 0, 0, 0, $w, $h);

        ob_start();
        imagepng($dst);
        $result = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $result !== false ? $result : $imageData;
    }

}
