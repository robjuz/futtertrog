<?php

namespace App\Http\Controllers;
use OpenApi\Attributes as OA;

#[OA\OpenApi(
    servers: [new OA\Server(
        url: L5_SWAGGER_CONST_HOST
    )],
    info: new OA\Info(
        title: "Futtertrog API",
        version: "1.0.0"
    ),
    components: new OA\Components(
        parameters: [new OA\Parameter(
            parameter: "page_in_query",
            name: "page",
            in: "query",
            schema: new OA\Schema(
                type: "integer",
                minimum: 1
            )
        ), new OA\Parameter(
            parameter: "per_page_in_query",
            name: "per_page",
            in: "query",
            schema: new OA\Schema(
                type: "integer",
                minimum: 1
            )
        )],
        responses: [new OA\Response(
            response: "Default",
            description: "System error",
            content: new OA\JsonContent(
                ref: "#/components/schemas/Error"
            )
        ), new OA\Response(
            response: "Empty",
            description: "Empty response",
            content: new OA\JsonContent()
        )],
        schemas: [new OA\Schema(
            schema: "Error",
            type: "object",
            properties: [new OA\Property(
                property: "message",
                type: "string"
            )]
        ), new OA\Schema(
            schema: "id",
            type: "integer",
            example: "1"
        ), new OA\Schema(
            schema: "Pagination",
            type: "object",
            properties: [new OA\Property(
                property: "current_page",
                type: "integer"
            ), new OA\Property(
                property: "first_page_url",
                type: "string",
                format: "uri"
            ), new OA\Property(
                property: "from",
                type: "integer"
            ), new OA\Property(
                property: "last_page",
                type: "integer"
            ), new OA\Property(
                property: "last_page_url",
                type: "string",
                format: "uri"
            ), new OA\Property(
                property: "next_page_url",
                type: "string",
                format: "uri"
            ), new OA\Property(
                property: "path",
                type: "string",
                format: "uri"
            ), new OA\Property(
                property: "per_page",
                type: "integer"
            ), new OA\Property(
                property: "prev_page_url",
                type: "string",
                format: "uri"
            ), new OA\Property(
                property: "to",
                type: "integer"
            ), new OA\Property(
                property: "total",
                type: "integer"
            )]
        )]
    )
)]
abstract class Controller
{
    //
}
