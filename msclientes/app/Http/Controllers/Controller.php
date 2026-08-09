<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

// DOCUMENTAÇÃO SWAGGER
/**
 * @OA\Info(
 *      title="API - MSCLIENTES",
 *      version="1.0.0",
 *      description="User Management API"
 * )
 * @OA\Server(
 *      url="/",
 *      description="Local Server"
 * )
 * 
 * @OA\SecurityScheme(
 *      securityScheme="bearerAuth",
 *      type="http",
 *      scheme="bearer",
 *      bearerFormat="JWT"
 * )
 */

abstract class Controller
{
    //
}
