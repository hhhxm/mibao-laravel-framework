<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\AuthManager;;
use Carbon\Carbon;
use Log;
use Auth;
use Image;
use App\Models\UploadFile;
use App\Models\Logs as LogsModel;
use Illuminate\Support\Facades\Storage;


class ApiController extends Controller
{

    use ApiResponse;

    public static function getUrlKeyValue($url, $key)
    {
        $url_parts = parse_url($url);
        if(isset($url_parts['query'])){
            parse_str($url_parts['query'], $path_parts);
            $res = !isset($path_parts[$key]) ? null : $path_parts[$key];
            return $res;
        }
        return null;
    }
    /**
     * 测试log
     *
     * @return \Illuminate\Http\Response
     */
    protected function testLog($request, $res='')
    {
        if($request->debug){
            Log::debug($_SERVER);
            Log::debug($request->all());
            Log::debug($res);
        }
        return true;
    }
    /**
     * 保存出错日志
     *
     * @return \Illuminate\Http\Response
     */
    public function saveLog(Request $request)
    {
        $content = $request->content ? $request->content : $request->all();
        $log = new LogsModel();
        $log->type    = $request->type;
        $log->content = $content;
        $log->server  = json_encode($_SERVER);
        $log->request = json_encode($request->all());
        $log->save();
        return $this->success($log->id);
    }
    /**
     * 验证提交参数
     *
     * @return 成功与否
     */
    protected function validatorParams($request, $roles)
    {
        Validator::extend('mobile', function($attribute, $value, $parameters) {
            return preg_match('/^((13[0-9])|(14[5,7,9])|(15[^4])|(18[0-9])|(17[0,1,3,5,6,7,8]))\d{8}$/', $value);
        });
        $validator = Validator::make($request->all(), $roles);
        if ($validator->fails()) {
            return $validator->errors();
        }else{
            return true;
        }
    }
    /**
     * 数据分页统一封装
     *
     * @return \Illuminate\Http\Response
     */
    public function paginateApiDate($request, $db, $no_order=false)
    {
        $fields       = $request->input('fields') ? :null;
        $limit        = $request->input('limit') ? : 30;
        $responseType = $request->input('responseType') ? : 'json';
        $orderName    = $request->input('orderName') ? : null;
        $order        = $request->input('order') ? : null;
        $timeDiff     = $request->input('timeDiff') ? : null;

        if($timeDiff && isset($timeDiff[0])){
            $db->where('created_at', '>',Carbon::parse($timeDiff[0]));
        }
        if($timeDiff && isset($timeDiff[1])){
            $db->where('created_at', '<',Carbon::parse($timeDiff[1])->addDay(1));
        }

        if(!$no_order){
            if($orderName && $order){
                $db->orderBy($orderName, $order);
            }else{
                $db->orderBy('id', 'DESC');
            }
        }
        if($responseType == 'excel'){
            global $excelData;
            $db->chunk(1000, function($res) {
                global $excelData;
                foreach ($res as $item) {
                    $excelData[] = $item->toArray();
                }
            });
            return [ 'data' => $excelData ];
        }else{
            $data = $db->paginate($limit);
            // $data = $db->simplePaginate($limit);
        }
        return $data;
    }
    /**
     * 上传base64图片
     *
     * @return 上传图片data
     */
    public function saveBase64($base64Data, $disk='public', $ip=null, $imageType='jpg', $preName='')
    {
        $user      = Auth::user();
        $base64Str = substr($base64Data, strpos($base64Data, ",")+1);
        $fileName  = $preName . "_" . md5(time()) . $user->id . "_" . mt_rand(0,999999) . "." . $imageType;
        $image     = Image::make($base64Str);
        $filePath = Storage::disk($disk)->put($fileName, (string) $image->encode());
        return $this->savePhotoFileData($fileName, $disk, $ip);
    }
    /**
     * 图片数据插入
     *
     * @return 上传图片data
     */
    public function savePhotoFileData($fileName, $disk='public', $ip=null){
        // $url        = Storage::disk($disk)->url($fileName);
        $publicPath = Storage::disk($disk)->path($fileName);
        $imageInfo  = getimagesize($publicPath);
        $fileId = UploadFile::insertGetId([
            'guard'         => Auth::getDefaultDriver(),
            'guard_user_id' => Auth::user()->id,
            'name'          => $fileName,
            'disk'          => $disk,
            'size'          => filesize($publicPath),
            'width'         => $imageInfo[0],
            'height'        => $imageInfo[1],
            'type'          => $imageInfo['mime'],
            'ip'            => $ip,
            // 'ip'         => $request->getClientIp(),
        ]);
        return (object) [
            'id'        => $fileId,
            'filePath' => $publicPath,
        ];
    }
    /**
     * Excel文件导出功能
     *
     * @return excel文件
     */
    public function exportExcel($data)
    {
        ini_set ('memory_limit', '512M');
        // 输出文件类型
        $type='xlsx';
        $cellData = [];
        // 对象转换成数组，因为excel不处理对象
        // $data = array_map('get_object_vars', $data);
        // 标题处理
        $names=[];
        foreach ($data[0] as $key => $value) {
            if($key == 'user'){
                $names[]='openid';
                !in_array('nickname', $names) ? $names[]='nickname' : null;
                $names[]='sex';
                $names[]='city';
                $names[]='province';
            }else{
                $names[]=$key;
            }
        }
        foreach ($data as $k0 => $item) {
            foreach ($item as $k1 => $value) {
                if($k1 == 'user'){
                    $user = $data[$k0][$k1];
                    $data[$k0]['openid'] = $user['openid'];
                    $data[$k0]['nickname'] = $user['nickname'];
                    $data[$k0]['sex'] = $user['sex'];
                    $data[$k0]['city'] = $user['city'];
                    $data[$k0]['province'] = $user['province'];
                    unset($data[$k0][$k1]);
                }
            }
        }
        $cellData[] = $names;
        // 标题与数据合并
        $cellData = array_merge($cellData,$data);
        $fileName = 'Data_'.md5(time());
        Excel::create($fileName,function($excel) use ($cellData){
            $excel->sheet('score', function($sheet) use ($cellData){
                $sheet->rows($cellData);
            });
        })
        ->store($type)
        // ->export('xls')
        ;
        $path = 'storage/exports/'.$fileName.'.'.$type;
        return url($path);
    }
}
