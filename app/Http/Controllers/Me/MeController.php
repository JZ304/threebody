<?php

namespace App\Http\Controllers\Me;

use App\Events\income;
use App\Jobs\disposeApply;
use App\Models\ApplyModel;
use App\Models\UserModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MeController extends Controller
{

    /**
     * 注册
     * @param Request $request
     */
    public function register(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:5',
            'password' => 'required|max:10',
            'tel' => ['required', 'max:15', 'regex:/^1[3456789]\d{9}$/', Rule::unique('user_info')->whereNull('deleted_at')],
        ], [
            'name.required' => '姓名是必填信息',
            'name.max' => '姓名最长支持五个字符',
            'password.required' => '密码必填',
            'password.max' => '密码最长10位',
            'tel.required' => '电话号码必填',
            'tel.regex' => '电话号码格式有误',
            'tel.unique' => '账号已存在',
        ]);

        if ($validator->fails()) {
            apiFail($validator->errors());
        } else {
            $info['name'] = $request->name;
            $info['password'] = Hash::make($request->password);
            $info['tel'] = $request->tel;
            if (UserModel::create($info)) {
                apiSuccess();
            } else {
                apiFail();
            }

        }

    }

    /**
     * 登录
     * @param Request $request
     */
    public function login(Request $request)
    {

        $result = Validator::make($request->all(), [
            'tel' => 'required',
            'password' => 'required'
        ], [
            'tel.required' => '账号必填',
            'password.required' => '密码必填'
        ]);
        if ($result->fails()) apiFail();
        $account = ['tel' => $request->tel, 'password' => $request->password];

//        if (Auth::attempt($account)) {
//            apiSuccess('登陆成功');
//        } else {
//            apiFail('账号或密码错误');
//        }

        if ($token = auth('api')->attempt($account)) {
            // 授权通过
            apiSuccess(['message' => '登陆成功！', 'token' => $token]);
        } else {
            // 授权不通过
            apiFail('账号或密码错误');
        }
    }

    /**
     * @param Request $request
     */
    public function myInfo(Request $request)
    {
        $user = $this->getUserInfo();
        apiSuccess($user);
    }

    /**
     * 刷新Token
     * @param Request $request
     */
    public function refreshToken(Request $request)
    {
        $this->getUserInfo();
        apiSuccess(['message' => '刷新成功', 'token' => auth('api')->refresh()]);
    }


    /**
     * 发起体现申请
     * @param Request $request
     */
    public function addQueue(Request $request)
    {
        $user = $this->getUserInfo();
        $test = Validator::make($request->all(), [
            'remark' => 'required',
            'money' => 'required'
        ], [
            'remark.required' => '申请备注必填',
            'money.required' => '申请金额必填',
        ]);
        if ($test->fails()) apiFail($test->errors());

        $order['user_id'] = $user['id'];
        $order['apply_at'] = time();
        $order['remark'] = $request->remark;
        $order['money'] = $request->money;
        $order['created_at'] = time();
        $order['updated_at'] = time();

        // 将申请信息写入数据库
        if ($request->money > $user['balance']) apiFail('余额不足');

        $id = ApplyModel::insertGetId($order);

        UserModel::whereId($user['id'])->decrement('balance', $request->money);

        $order['id'] = $id;
        $order['type'] = 'apply';

        disposeApply::dispatch($order)->delay(now()->addMinutes(1));

        apiSuccess('申请已提交!');

    }

    public function income(Request $request)
    {
        $user = $this->getUserInfo();
        $money = $request->money;
        if(is_null($money) || $money <= 0 ) apiFail('金额错误');

        // 触发事件
        event(new income($money));
    }

}
