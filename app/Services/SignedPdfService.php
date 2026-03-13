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
        $fpdi->SetFont('Helvetica', '', $fontSize ?? 12);
        $fpdi->SetXY($x, $y);
        $fpdi->Cell($w, $h, $value, 0, 0);
    }

    private function renderCheckbox(Fpdi $fpdi, string $value, float $x, float $y, float $w, float $h): void
    {
        if ($value !== '1') {
            return;
        }

        $fpdi->SetFont('ZapfDingbats', '', 12);
        $fpdi->SetXY($x, $y);
        $fpdi->Cell($w, $h, '4', 0, 0);
    }

    private function renderImage(Fpdi $fpdi, string $value, float $x, float $y, float $w, float $h): void
    {
        $imageData = base64_decode($value, true);
        if ($imageData === false) {
            return;
        }

        $info = @getimagesizefromstring($imageData);
        if ($info === false || ! in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG])) {
            return;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'sig_').'.png';
        file_put_contents($tempFile, $imageData);

        $fpdi->Image($tempFile, $x, $y, $w, $h, 'PNG');

        @unlink($tempFile);
    }
}
