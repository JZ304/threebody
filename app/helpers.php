<?php

if (!function_exists('apiSuccess')) {
    /**
     * 接口成功返回数据
     * @param array $data
     */
    function apiSuccess($data = [],$message = '请求成功')
    {
        $response = response()->json([
            'code' => 200,
            'status' => true,
            'message' => $message,
            'data' => $data
        ]);
        throw new \Illuminate\Http\Exceptions\HttpResponseException($response);
    }
}

if (!function_exists('apiFail')) {
    /**
     * 接口失败返回数据
     * @param string $message
     * @param array $data
     */
    function apiFail($data = [], $message = '请求失败')
    {
        $response = response()->json([
            'code' => 500,
            'status' => false,
            'message' => $message,
            'data' => $data
        ]);
        throw  new \Illuminate\Http\Exceptions\HttpResponseException($response);
    }
}



