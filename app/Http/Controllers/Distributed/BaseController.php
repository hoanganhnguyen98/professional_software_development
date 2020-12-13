<?php
namespace App\Http\Controllers\Distributed;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use GuzzleHttp\Psr7\Request as ApiRequest;

class BaseController extends Controller
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($data = [])
    {
    	$response = $data;

        return response()->json($response, 200)->withHeaders([
            'Access-Control-Allow-Headers' => 'api-token, project-type, Authorization, Origin, X-Requested-With, Content-Type, Accept, DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Range',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS, PUT, DELETE, HEAD',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($message = 'Lỗi chưa xác định', $code = 404)
    {
    	$response = [
            'error' => [
                "message" => $message
            ]
        ];

        return response()->json($response, $code)->withHeaders([
            'Access-Control-Allow-Headers' => 'api-token, project-type, Authorization, Origin, X-Requested-With, Content-Type, Accept, DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Range',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS, PUT, DELETE, HEAD',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    public function notification($type, $action, $employee_id)
    {

    }

    public function verifyApiToken($apiToken = null, $projectType = null)
    {
        if (!$apiToken) {
            $verifyApiToken['message'] = 'Thiếu giá trị api-token ở Header';
            $verifyApiToken['code'] = 401;

            return $verifyApiToken;
        }

        if (!$projectType) {
            $verifyApiToken['message'] = 'Thiếu giá trị project-type ở Header';
            $verifyApiToken['code'] = 400;

            return $verifyApiToken;
        }

        $url = 'https://distributed.de-lalcool.com/api/verify-token';
        $headers = [
            'api-token' => $apiToken,
            'project-type' => $projectType
        ];

        $client = new \GuzzleHttp\Client();

        $verifyApiToken = [];

        try {
            $response = $client->get($url, [
                'headers' => $headers
            ]);
        } catch (\Throwable $th) {
            if ($th->getCode() == 401) {
                $verifyApiToken['message'] = 'User token hoặc loại dự án không đúng!';
            } else {
                $verifyApiToken['message'] = 'Đã có lỗi xảy ra từ khi gọi api verify token';
            }

            $verifyApiToken['code'] = $th->getCode();

            return $verifyApiToken;
        }

        $responseStatus = $response->getStatusCode();
        $data = json_decode($response->getBody()->getContents(), true);

        if ($responseStatus !== 200) {
            if ($data['message']) {
                $verifyApiToken['message'] = 'Đã có lỗi xảy ra từ khi gọi api verify token';
            } else {
                $verifyApiToken['message'] = 'Lỗi chưa xác định đã xảy ra khi verify token';
            }
        } else {
            $verifyApiToken['id'] = $data['result']['id'];
            $verifyApiToken['name'] = $data['result']['full_name'];
            $verifyApiToken['role'] = $data['result']['role'];
        }

        $verifyApiToken['code'] = $responseStatus;

        return $verifyApiToken;
    }

    public function logging($description = 'test', $authorId=1, $projectType='LUOI_DIEN', $state='doing', $name='test')
    {
        $url = 'http://it4883logging.herokuapp.com/api/resolve-problem/add';

        // $headers = [
        //     'api-token' => $apiToken,
        //     'project-type' => $projectType
        // ];

        $body = [
            "regionId" => 0,
            "entityId" => 0,
            "description" => $description,
            "authorId" => $authorId,
            "projectType" => $projectType,
            "state" => $state,
            "name" => $name
        ];

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->post($url, [
                // 'headers' => $headers
                'json' => $body,

            ]);
        } catch (\Throwable $th) {
        }
    }

    public function createUserMeta(
        $apiToken,
        $projectType,
        $user_id,
        $target_id,
        $description,
        $status,
        $task_id
    )
    {
        $url = 'https://distributed.de-lalcool.com/api/userMeta';

        $headers = [
            'token' => $apiToken,
            'project-type' => $projectType,
            'Content-Type' => 'application/json'
        ];

        $body = [
            "user_id" => $user_id,
            "target_id" => $target_id,
            "description" => "Khắc phục sự cố",
            "status" => $status,
            "type" => $projectType,
            "name" => "INCIDENT",
            "meta_data" => [
                "task_id" => $task_id
            ]
        ];

        try {
            $response = $client->post($url, [
                // 'headers' => $headers
                'json' => $body,

            ]);
        } catch (\Throwable $th) {
        }
    }
}
