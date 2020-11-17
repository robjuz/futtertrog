<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *      @OA\Server( url=L5_SWAGGER_CONST_HOST ),
 *      @OA\Info(
 *         title="Futtertrog API",
 *         version="1.0.0",
 *      ),
 *      @OA\Components(
 *          @OA\Parameter(
 *              parameter="page_in_query",
 *              name="page",
 *              in="query",
 *              @OA\Schema(type="integer", minimum="1"),
 *          ),
 *          @OA\Parameter(
 *             parameter="per_page_in_query",
 *             name="per_page",
 *             in="query",
 *             @OA\Schema(type="integer", minimum="1"),
 *          ),
 *
 *          @OA\Response(
 *               response="Default",
 *               description="System error",
 *               @OA\JsonContent(ref="#/components/schemas/Error")
 *           ),
 *          @OA\Response(
 *              response="Empty",
 *              description="Empty response",
 *              @OA\JsonContent()
 *          ),
 *
 *          @OA\Schema(
 *              schema="Error",
 *              type="object",
 *              @OA\Property( property="message", type="string"),
 *          ),
 *
 *          @OA\Schema( schema="id", type="integer",  example="1" ),
 *
 *          @OA\Schema(
 *              schema="Pagination",
 *              type="object",
 *              @OA\Property( property="current_page", type="integer" ),
 *              @OA\Property( property="first_page_url", type="string", format="uri" ),
 *              @OA\Property( property="from", type="integer" ),
 *              @OA\Property( property="last_page", type="integer" ),
 *              @OA\Property( property="last_page_url", type="string", format="uri" ),
 *              @OA\Property( property="next_page_url", type="string", format="uri" ),
 *              @OA\Property( property="path", type="string", format="uri" ),
 *              @OA\Property( property="per_page", type="integer" ),
 *              @OA\Property( property="prev_page_url", type="string", format="uri" ),
 *              @OA\Property( property="to", type="integer" ),
 *              @OA\Property( property="total", type="integer" ),
 *          ),
 *     )
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
