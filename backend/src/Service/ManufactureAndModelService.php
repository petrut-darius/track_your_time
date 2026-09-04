<?php

declare(strict_types = 1);

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ManufactureAndModelService {
    const DATA_URL = "https://storage.googleapis.com/kagglesdsdata/datasets/6049042/9857063/vehicle%20models.json?X-Goog-Algorithm=GOOG4-RSA-SHA256&X-Goog-Credential=gcp-kaggle-com%40kaggle-161607.iam.gserviceaccount.com%2F20260904%2Fauto%2Fstorage%2Fgoog4_request&X-Goog-Date=20260904T205552Z&X-Goog-Expires=259200&X-Goog-SignedHeaders=host&X-Goog-Signature=1380fe239c8e92244a15a10f8993f20a5af8389e5b4cf9c3c7565ff701e5330cc899d9a5be41a1e6616e2087dceed65d632168a05217499bb91d6c3e8479b50ba1328af289d321401216462232303971dff2ca3c0bcf6ed93bb146fc7759d7175f599c8ae49f816ecb366d3976d33fc333c0749bd507abe4daacaa76ae90066339f1bf994eca55c111d9cad227e7c756f74897b5d4eef1aa9aa235b210e77b65fd1c08a0b1f1a48c8cf195d10267c6978f5e8bbd071b7b2cf5a2bf4649d38a718626b87fb932dd2de2775e72f31e8d3fe2ca581ceead5c03b3e2aa81579b8873a1501c515184256a3494cc6602005c4ebaf9ec0c79da598f6cebcbca01866510";

    public function handle()
    {
        $data = (array) json_decode(file_get_contents(self::DATA_URL), true);
    
        $manufactures = [];

        foreach($data as $item) {
            $manufactures[$item["Make"]] = $item["Models"];
        }

        return $manufactures;
    }
}