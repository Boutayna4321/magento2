<?php
error_reporting(E_ALL); ini_set("display_errors","1");
require dirname(__DIR__,2)."/app/bootstrap.php";
$b=\Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om=$b->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode("frontend");
$ch=curl_init("http://nginx/rest/default/V1/integration/customer/token");
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); curl_setopt($ch,CURLOPT_POST,true);
curl_setopt($ch,CURLOPT_HTTPHEADER,["Content-Type: application/json"]);
curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode(["username"=>"roni_cost@example.com","password"=>"roni_cost3@example.com"]));
$real=trim(curl_exec($ch));
$svc=$om->get(\Magento\Integration\Model\CustomerTokenService::class);
$probe=$svc->createCustomerAccessToken("roni_cost@example.com","roni_cost3@example.com");
$reader=$om->get(\Magento\Integration\Api\UserTokenReaderInterface::class);
foreach(["REAL"=>$real,"PROBE"=>$probe] as $label=>$t){
  try{ $u=$reader->read($t); echo "$label read: OK uid=".$u->getUserContext()->getUserId()."\n"; }
  catch(\Throwable $e){ echo "$label read: FAIL\n"; }
}
$realP=explode(".",$real); $probeP=explode(".",$probe);
echo "REAL sig=".substr($realP[2],0,16)." iat=".json_decode(base64_decode(strtr($realP[1],"-_","+/")))->iat."\n";
echo "PROBE sig=".substr($probeP[2],0,16)." iat=".json_decode(base64_decode(strtr($probeP[1],"-_","+/")))->iat."\n";
