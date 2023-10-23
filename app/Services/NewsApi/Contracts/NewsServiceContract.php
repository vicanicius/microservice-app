<?php

namespace App\Services\NewsApi\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface NewsServiceContract
{
    /**
     * @param  array  $dataRequest
     * @return array
     */
    public function getAllArticlesAbout(array $dataRequest): array;

    public function getAllArticlesAboutInElastic(array $dataRequest): array;

    public function getTopHeadlinesInTheCountry(array $dataRequest): array;

    public function getTopHeadlinesInTheCountryInElastic(array $dataRequest): array;

    public function getSummaryNews(): void;
}
