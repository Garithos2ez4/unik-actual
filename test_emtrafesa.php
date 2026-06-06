<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://emtrafesa.pe/Home/GetSucursales");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$output = curl_exec($ch);
curl_close($ch);
echo substr($output, 0, 300);
