<?php

namespace App\Http\Controllers\Me;

use App\Events\income;
use App\Jobs\disposeApply;
use App\Models\ApplyModel;
use App\Models\UserModel;
use GatewayWorker\Lib\Gateway;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

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

    /**
     * @param Request $request
     */
    public function income(Request $request)
    {
        $user = $this->getUserInfo();
        $money = $request->money;
        if (is_null($money) || $money <= 0) apiFail('金额错误');

        // 触发事件
        event(new income($money));
    }


    /**
     * 长连接推送消息
     * @param Request $request
     */
    public function pushMessage(Request $request)
    {
        $tel = $request->tel;
        $message = $request->message;
        Gateway::sendToUid($tel, $message);
        apiSuccess('信息已发送');
    }

//    public function importGoods(Request $request)
//    {
//        $imageFile = $request->file('file');
//        dd($imageFile);
//        $excel = \PHPExcel_IOFactory::load($imageFile);
//        dd($excel);
//    }

    public function importGoods(Request $request)
    {
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName(); // 文件原名
        $ext = $file->getClientOriginalExtension();     // 扩展名
        $realPath = $file->getRealPath();               //临时文件的绝对路径
        $type = $file->getClientMimeType();
        $filename = date('Y-m-d-H-i-s') . '-' . uniqid() . '.' . $ext;
        $bool = Storage::disk('public')->put($filename, file_get_contents($realPath));
        $filePath = 'public/uploads/'.iconv('UTF-8', 'GBK', $filename);

        $data = [];
        \Maatwebsite\Excel\Excel::load($filePath, function ($reader) use(&$data){
            $reader = $reader->getSheet(0);
            $data = $reader->toArray();
        });
        dd($data);
        //图片上传
        $imageFile = $request->file('file');
        $excel = \PHPExcel_IOFactory::load($imageFile);

        $path = base_path().'/public/uploads/';
        $drawing  = new \PHPExcel_Writer_Excel2007_Drawing();
        $drawingHashTable = new \PHPExcel_HashTable();

        $drawingHashTable->addFromSource($drawing->allDrawings($excel));

        for ($i = 0; $i < $drawingHashTable->count(); ++$i){
            $memoryDrawing = $drawingHashTable->getByIndex($i);

            if($memoryDrawing instanceof \PHPExcel_Worksheet_MemoryDrawing){
                $filePath = 'images/'.date('Y').'/'.date('m').'/'.date('d').'/'.$memoryDrawing->getCoordinates().'-'.$memoryDrawing->getHashCode().'.jpg';
                $filenameImg = $path .$filePath;
                //将图片存到指定的目录
                imagejpeg($memoryDrawing->getImageResource(),$filenameImg);
                //获取该图片所在的单元格
                $cell = $memoryDrawing->getWorksheet()->getCell($memoryDrawing->getCoordinates());
                $string = $memoryDrawing->getCoordinates();
                $coordinate = \PHPExcel_Cell::coordinateFromString($string);
            }
            $data[$coordinate[1] - 1][$this->getalphnum($coordinate[0]) - 1] = $filePath;
        }

        $admin_id = Admin::user()->{'id'};          //创建id
        $category_id = 8;                           //分类id
        $now_time = date('Y-m-d H:i:s');     //时间
        try{
            //事务
            DB::beginTransaction();

            for ($i = 1;$i <= count($data);$i++){
                if(empty($data[$i]) || empty($data[$i][0])){
                    continue;
                }
                //判断用户是否都填写
                if(!isset($data[$i][0]) || empty($data[$i][0])){
                    throw new \Exception('第'.($i+1).'行不能'.$data[0][0].'字段不能为空，请重新上传');
                }

                if(!isset($data[$i][1]) || empty($data[$i][1])){
                    throw new \Exception('第'.($i+1).'行不能'.$data[0][1].'字段不能为空，请重新上传');
                }

                if(!isset($data[$i][3]) || empty($data[$i][3])){
                    throw new \Exception('第'.($i+1).'行不能'.$data[0][3].'字段不能为空，请重新上传');
                }

                if(!isset($data[$i][4]) || empty($data[$i][4])){
                    throw new \Exception('第'.($i+1).'行不能'.$data[0][4].'字段不能为空，请重新上传');
                }

                //查询是否存在
                $goods = Goods::where([
                    'title' => $data[$i][0]
                ])->first();

                if(empty($goods)){
                    //新增
                    $temp = [
                        'title'     => $data[$i][0],
                        'price'     => $data[$i][1],
                        'img'       => $data[$i][2],
                        'describe'  => $data[$i][3],
                        'sort'      => $data[$i][4],
                        'created_id'=> $admin_id,
                        'updated_id'=> $admin_id,
                        'created_at'=> $now_time,
                        'updated_at'=> $now_time,
                        'is_putaway'=> 1,
                        'category_id'=> $category_id
                    ];
                    $res = DB::table('i_goods')->insertGetId($temp);
                    if(!$res){
                        throw new \Exception($data[$i][0].'新增失败，请重新上传新增');
                    }
                }else{
                    //更新
                    $temp = [
                        'price'     => $data[$i][1],
                        'img'       => $data[$i][2],
                        'describe'  => $data[$i][3],
                        'sort'      => $data[$i][4],
                        'updated_id'=> $admin_id,
                        'updated_at'=> $now_time,
                        'is_putaway'=> 1,
                    ];
                    $res = DB::table('i_goods')
                        ->where([
                            'title' => $data[$i][0]
                        ])
                        ->update($temp);
                    if(!$res){
                        throw new \Exception($data[$i][0].'更新失败，请重新上传更新');
                    }
                }
            }
            DB::commit();
            Storage::disk('uploads')->delete($filename);        //删除上传的暂存文件
            return response()->json(['code' => 200, 'message' => '文件上传成功', 'data' => []]);
        }catch (\Exception $e){
            $error = $e->getMessage();
            DB::rollBack();
            return response()->json(['code' => 300, 'message' => '文件上传失败,请重新上传', 'data' => []]);
        }

    }

    //转化EXCEL表格行
    public function getalphnum($char){
        $array=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $len=strlen($char);
        $sum=0;
        for($i=0;$i<$len;$i++){
            $index=array_search($char[$i],$array);
            $sum+=($index+1)*pow(26,$len-$i-1);
        }
        return $sum;
    }
}
