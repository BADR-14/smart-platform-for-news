<?php

namespace App\Services\External;

use App\Contracts\DocumentTextExtractor;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\Image;

class GoogleVisionService implements DocumentTextExtractor
{
    public function isAvailable(): bool
    {
        $path = config('services.google_vision.credentials');

        return is_string($path) && $path !== '' && is_file($path);
    }

    public function extractText(string $contents): array
    {
        // الاتصال يُنشأ عند الحاجة فقط، حتى لا يفشل التطبيق إذا لم يُضبط المفتاح
        $client = new ImageAnnotatorClient([
            'credentials' => config('services.google_vision.credentials'),
        ]);

        try {
            $request = (new AnnotateImageRequest)
                ->setImage((new Image)->setContent($contents))
                ->setFeatures([(new Feature)->setType(Type::DOCUMENT_TEXT_DETECTION)]);

            $response = $client->batchAnnotateImages(
                (new BatchAnnotateImagesRequest)->setRequests([$request])
            )->getResponses()[0] ?? null;

            $annotation = $response?->getFullTextAnnotation();

            if (! $annotation) {
                return ['text' => '', 'confidences' => []];
            }

            // ثقة القراءة لكل صفحة (0..1) تُستخدم كمؤشر لوضوح الصورة
            $confidences = [];
            foreach ($annotation->getPages() as $page) {
                $confidences[] = (float) $page->getConfidence();
            }

            return ['text' => (string) $annotation->getText(), 'confidences' => $confidences];
        } finally {
            $client->close();
        }
    }
}
