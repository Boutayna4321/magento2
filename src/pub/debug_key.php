<?php
require __DIR__."/../app/bootstrap.php";
$b=\Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om=$b->getObjectManager();
$c=$om->get(\Magento\Framework\App\DeploymentConfig::class);
echo substr($c->get("crypt/key"),0,40)." len=".strlen((string)$c->get("crypt/key"))."\n";
echo "env.php: ".substr((require BP."/app/etc/env.php")["crypt"]["key"],0,40)."\n";
