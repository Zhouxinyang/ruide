<?php 
class Send
{
	public $readTimeout;
	public $connectTimeout;

	public function send ($user,$phone, $num)
	{
		return $this->curl("http://sms.zzgdapp.com/Sendmsg/wtlmSmsService?mobile={$phone}&content=".
			urlencode("尊敬的用户，微通联盟正在向您验证手机号码，本次验证码为{$num}，15分钟内有效"));
	}
	
	/*
	* 发送请求
	*/
	private function curl($url, $postFields = null)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($this->readTimeout) {
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
		}else{
		    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		}
		
		if ($this->connectTimeout) {
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
		}
		curl_setopt ( $ch, CURLOPT_USERAGENT, "top-sdk-php" );
		//https 请求
		if(strlen($url) > 5 && strtolower(substr($url,0,5)) == "https" ) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		}

		if (is_array($postFields) && 0 < count($postFields))
		{
			$postBodyString = "";
			$postMultipart = false;
			foreach ($postFields as $k => $v)
			{
				if(!is_string($v))
					continue ;

				if("@" != substr($v, 0, 1))//判断是不是文件上传
				{
					$postBodyString .= "$k=" . urlencode($v) . "&"; 
				}
				else//文件上传用multipart/form-data，否则用www-form-urlencoded
				{
					$postMultipart = true;
					if(class_exists('\CURLFile')){
						$postFields[$k] = new \CURLFile(substr($v, 1));
					}
				}
			}
			unset($k, $v);
			curl_setopt($ch, CURLOPT_POST, true);
			if ($postMultipart)
			{
				if (class_exists('\CURLFile')) {
					curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
				} else {
					if (defined('CURLOPT_SAFE_UPLOAD')) {
						curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
					}
				}
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
			}
			else
			{
				$header = array("content-type: application/x-www-form-urlencoded; charset=UTF-8");
				curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
				curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString,0,-1));
			}
		}
		$reponse = curl_exec($ch);
		$errno = curl_errno($ch);
		$errmsg = curl_error($ch);
		$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		if ($errno){
			throw new Exception($errmsg, 0);
		}else if (200 !== $httpStatusCode){
			throw new Exception($errmsg, $httpStatusCode);
		}
		return $reponse;
	}
}