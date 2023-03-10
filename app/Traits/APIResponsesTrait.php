<?php

namespace App\Traits;

/**
 * Api Responses Traits
 */
trait APIResponsesTrait
{
    public function responseOK($data, $msg)
    {
        return response()->json([
            'status' => true,
            'message' => $msg,
            'data' => $data
        ], status: 200);
    }

    public function responseUnauthorise($data, $msg)
    {
        return response()->json([
            'status' => false,
            'message' => $msg,
            'data' => $data
        ], status: 401);
    }

    public function responseError($data, $msg)
    {
        return response()->json([
            'status' => false,
            'message' => $msg,
            'data' => $data
        ], status: 500);
    }
}
