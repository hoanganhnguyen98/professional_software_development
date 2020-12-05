<?php

namespace App\Http\Controllers\Distributed;

use App\Http\Controllers\Distributed\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Model\Employee;
use App\Model\Task;
use Carbon\Carbon;

class EmployeeController extends BaseController
{
    public function active(Request $request)
    {
        $apiToken = $request->header('api-token');
        $projectType = $request->header('project-type');

        $verifyApiToken = $this->verifyApiToken($apiToken, $projectType);

        if(empty($verifyApiToken)) {
            return $this->sendError('Đã có lỗi xảy ra từ khi gọi api verify token', 401);
        } else {
            $statusCode = $verifyApiToken['code'];

            if ($statusCode != 200) {
                return $this->sendError($verifyApiToken['message'], $statusCode);
            }
        }

        $task_id = $request->get('task_id');

        if (!$task_id) {
            return $this->sendError('Không có giá trị định danh sự cố', 400);
        }

        $task = Task::where('id', $task_id)->first();

        if (!$task) {
            return $this->sendError('Công việc xử lý không tồn tại', 404);
        }

        if ($task->status == 'done') {
            return $this->sendError('Công việc xử lý đã hoàn tất', 403);
        }

        $employee_id = $verifyApiToken['id'];
        $employee_ids = $task->employee_ids;

        if (strpos($employee_ids, ',') > 0) {
            $employee_area = explode(',', $employee_ids);
        } else {
            $employee_area = [$employee_ids];
        }

        if (!in_array($employee_id, $employee_area)) {
            return $this->sendError('Không thuộc phạm vi quản lý công việc', 403);
        }

        $active_ids = $task->active_ids;
        $active_task = false;

        if ($active_ids) {
            if (strpos($active_ids, ',') > 0) {
                if (in_array($employee_id, explode(',', $active_ids))) {
                    $active_task = true;
                }
            } else {
                if ($employee_id === (int) $active_ids) {
                    $active_task = true;
                }
            }
        }

        if ($active_task) {
            return $this->sendError('Công việc đã được xác nhận từ trước', 403);
        }

        if ($active_ids) {
            $new_active_ids = $active_ids . ',' . $employee_id;
        } else {
            $new_active_ids = $employee_id;
        }

        $task->active_ids = $new_active_ids;
        $task->status = 'doing';
        $task->save();

        return $this->sendResponse();
    }

    public function listing(Request $request)
    {
        $apiToken = $request->header('api-token');
        $projectType = $request->header('project-type');

        $verifyApiToken = $this->verifyApiToken($apiToken, $projectType);

        if(empty($verifyApiToken)) {
            return $this->sendError('Đã có lỗi xảy ra từ khi gọi api verify token', 401);
        } else {
            $statusCode = $verifyApiToken['code'];

            if ($statusCode != 200) {
                return $this->sendError($verifyApiToken['message'], $statusCode);
            }
        }

        $type = $projectType;

        $page = $request->get('page');
        $limit = $request->get('limit');
        $metadata = [];

        if (!$page || !$limit) {
            $employees = Employee::where('type',$type)->get();
        } else {
            $employees = Employee::where('type',$type)->offset(($page - 1) * $limit)->limit($limit)->get();

            $count = Employee::where('type',$type)->count();
            $total = ceil($count / $limit);

            $metadata = [
                'total' => (int) $total,
                'page' => (int) $page,
                'limit' => (int) $limit
            ];
        }

        $data = [
            'metadata' => $metadata,
            'employees' => $employees
        ];

        return $this->sendResponse($data);
    }

    public function login(Request $request)
    {
        $username = $request->get('username');

        if (!$username) {
            return $this->sendError('Thiếu giá trị username', 400);
        }

        $password = $request->get('password');

        if (!$password) {
            return $this->sendError('Thiếu giá trị password', 400);
        }

        $url = 'https://distributed.de-lalcool.com/api/login';

        $headers = [
            'Content-Type' => 'application/json',
        ];

        $body = [
            "username" => $username,
            "password" => $password
        ];

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $body,
            ]);
        } catch (\Throwable $th) {
            $message = 'Đã có lỗi xảy ra từ khi gọi api login';

            return $this->sendError($message, $th->getCode());
        }

        $responseStatus = $response->getStatusCode();
        $data = json_decode($response->getBody()->getContents(), true);

        if ($responseStatus !== 200) {
            if ($data['message']) {
                $message = $data['message'];
            } else {
                $message = 'Lỗi chưa xác định đã xảy ra khi gọi api login';
            }

            return $this->sendError($message, $responseStatus);
        }

        $employee = $data['result'];
        $employee_id = $employee['id'];

        $existedEmployee = Employee::where('employee_id', $employee_id)->first();

        if (!$existedEmployee) {
            $newEmployee = Employee::create([
                'employee_id' => $employee_id,
                'current_id' => null,
                'pending_ids' => null,
                'all_ids' => null
            ]);

            $data = [
                'employee' => $newEmployee,
                'current_task' => null,
                'active_current_task' => false,
                'pending_tasks' => []
            ];

            return $this->sendResponse($data);
        }

        $current_id = $existedEmployee->current_id;
        $current_task = Task::where([['id', $current_id], ['status', '<>' ,'done']])->first();

        $current_task_info = [];
        if ($current_task) {
            $current_task_type_id = $current_task->task_type_id;
            $current_task_type = TaskType::where('id', $current_task_type_id)->first();

            $current_task_info['status'] = $current_task->status;
            $current_task_info['task_type'] = $current_task_type;
        }

        $active_task = false;
        if ($current_task) {
            $active_ids = $current_task->active_ids;

            if ($active_ids) {
                if (strpos($active_ids, ',') > 0) {
                    if (in_array($employee_id, explode(',', $active_ids))) {
                        $active_task = true;
                    }
                } else {
                    if ($employee_id === (int) $active_ids) {
                        $active_task = true;
                    }
                }
            }
        }

        $pending_ids = $existedEmployee->pending_ids;
        $pending_tasks = [];

        if ($pending_ids) {
            if (strpos($pending_ids, ',') > 0) {
                $pending_ids_list = explode(',', $pending_ids);
            } else {
                $pending_ids_list = [$pending_ids];
            }

            foreach ($pending_ids_list as $id) {
                $task = Task::where([['id', $id], ['status', '<>' ,'done']])->first();

                if ($task) {
                    $task_data = [];

                    $task_data['status'] = $task->status;

                    $task_type_id = $task->id;
                    $task_type = TaskType::where('id', $task_type_id)->first();
                    $task_data['task_type'] = $task_type;

                    $pending_tasks[] = $task;
                }
            }
        }


        $data = [
            'employee' => $existedEmployee,
            'current_task' =>$current_task_info,
            'active_current_task' => $active_task,
            'pending_tasks' => $pending_tasks
        ];

        return $this->sendResponse($data);
    }
}
