<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Responses\ResponseBuilder;
use App\Services\NewsApi\Contracts\NewsServiceContract;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GetEverythingController extends Controller
{
    public function __construct(private NewsServiceContract $service)
    {
        //
    }

    public function __invoke(Request $request): JsonResponse
    {
        $response = ResponseBuilder::init();

        try {
            $everything = $this->service->getAllArticlesAboutInElastic($request->all());

            return $response->data($everything)
                ->status(Response::HTTP_OK)
                ->build();
        } catch (Exception $exception) {
            dd($exception->getMessage());

            return $response->message('Unexpected error in '.self::class)
                ->status(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->build();
        }
    }
}
