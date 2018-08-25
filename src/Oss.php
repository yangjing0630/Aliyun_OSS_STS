<?php

namespace Candice\Aliyun_OSS_STS;

require_once __DIR__ . "/aliyun_sts_php_sdk/aliyun-php-sdk-core/Config.php";
use Sts\Request\V20150401 as Sts;

class Oss
{

    public function index(array $params, $roleSessionName = 'candice')
    {

        // 你需要操作的资源所在的region，STS服务目前只有杭州节点可以签发Token，签发出的Token在所有Region都可用
// 只允许子用户使用角色

        $iClientProfile = \DefaultProfile::getProfile($params['regionId'], $params['accessKeyId'], $params['accessSecret']);
        $client = new \DefaultAcsClient($iClientProfile);

// 角色资源描述符，在RAM的控制台的资源详情页上可以获取
        $roleArn = $params['roleArn'];

        $policy = <<<POLICY
{
  "Statement": [
    {
      "Action": [
        "oss:Get*",
        "oss:List*",
        "oss:PutObject"
      ],
      "Effect": "Allow",
      "Resource": "*"
    }
  ],
  "Version": "1"
}

POLICY;
        $request = new Sts\AssumeRoleRequest();
// RoleSessionName即临时身份的会话名称，用于区分不同的临时身份
// 您可以使用您的客户的ID作为会话名称
        $request->setRoleSessionName($roleSessionName);
        $request->setRoleArn($roleArn);
        $request->setPolicy($policy);
        $request->setDurationSeconds(3600);

        try {
            $response = json_decode($client->doAction($request)->getBody());
            $data = $response->Credentials;
            $data->bucket = $params['bucket'];
            $data->endpoint = urlencode($params['endPoint']);
            $data->host = urlencode($params['host']);
            $data->callbackUrl = urlencode($params['callbackUrl']);
            $data->prefix = $params['prefix'];
            return [
                'status' => 200,
                'msg' => 'success',
                'data' => $data,
            ];
        } catch (\ServerException $e) {

            return [
                'status' => 500,
                'msg' => "Error: " . $e->getErrorCode() . " Message: " . $e->getMessage() . "\n",
            ];

        } catch (\ClientException $e) {

            return [
                'status' => 400,
                'msg' => "Error: " . $e->getErrorCode() . " Message: " . $e->getMessage() . "\n",
            ];
        }
    }
}
