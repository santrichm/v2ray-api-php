<?php

/*\
 * | - Author : github.com/mobinjavari
 * | - Source : github.com/mobinjavari/v2ray-api-php
 * | - License : github.com/mobinjavari/v2ray-api-php/LICENSE.md
\*/

class xui_api
{
    private string $address;

    private int $port;

    private string $username;

    private string $password;

    public function __construct(string $address, int $port, string $username, string $password)
    {
        $this->address = $address;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;

        if(!file_exists('./.cookie.txt')) $this->login();
    }

    public function request(string $method, array | string $param = "") : array
    {
        $value = is_array($param) ? json_encode($param) : $param;
        $handle = curl_init("$this->address:$this->port/$method");
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_ENCODING, "");
        curl_setopt($handle, CURLOPT_COOKIEFILE, "./.cookie.txt");
        curl_setopt($handle, CURLOPT_COOKIEJAR, "./.cookie.txt");
        curl_setopt($handle, CURLOPT_MAXREDIRS, 10);
        curl_setopt($handle, CURLOPT_TIMEOUT, 0);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($handle, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $value);
        $response = json_decode(curl_exec($handle),true);
        curl_close($handle);
        return $response;
    }

    public function login() : bool
    {
        return (bool)$this->request("login",[
            "username" => $this->username,
            "password" => $this->password
        ]);
    }

    public function list(array $filter = []) : array
    {
        $list = $this->request(
            "xui/inbound/list"
        )["obj"];

        if(!empty($filter))
        {
            $result = [];

            for ($list_andis = 0, $num = 0; $num < count($list); $num++)
            {
                $filter_status = 1;
                $port = empty($filter["port"]) ? "" : (int)$filter["port"];
                $uuid = empty($filter["uuid"]) ? "" : $filter["uuid"];
                $protocol = empty($filter["protocol"]) ? "" : $filter["protocol"];
                $list_settings = json_decode($list[$num]["settings"],true);

                if(!empty($port) && $port !== (int)$list[$num]["port"]) $filter_status = 0;
                if(!empty($uuid) && $uuid !== $list_settings["clients"][0]["id"]) $filter_status = 0;
                if(!empty($protocol) && $protocol !== $list[$num]["protocol"]) $filter_status = 0;

                if($filter_status)
                {
                    $result[$list_andis]["id"] = (int)$list[$num]["id"];
                    $result[$list_andis]["up"] = (int)$list[$num]["up"];
                    $result[$list_andis]["down"] = (int)$list[$num]["down"];
                    $result[$list_andis]["total"] = (int)$list[$num]["total"];
                    $result[$list_andis]["remark"] = $list[$num]["remark"];
                    $result[$list_andis]["enable"] = (bool)$list[$num]["enable"];
                    $result[$list_andis]["expiryTime"] = (int)$list[$num]["expiryTime"];
                    $result[$list_andis]["listen"] = $list[$num]["listen"];
                    $result[$list_andis]["port"] = (int)$list[$num]["port"];
                    $result[$list_andis]["protocol"] = $list[$num]["protocol"];
                    $result[$list_andis]["settings"] = json_decode($list[$num]["settings"],true);
                    $result[$list_andis]["streamSettings"] = json_decode($list[$num]["streamSettings"],true);
                    $result[$list_andis]["tag"] = $list[$num]["tag"];
                    $result[$list_andis]["sniffing"] = json_decode($list[$num]["sniffing"],true);
                    $result[$list_andis]["url"] =
                        $this->url(
                            $result[$list_andis]["protocol"],
                            $result[$list_andis]["settings"]["clients"][0]["id"],
                            $result[$list_andis]["remark"],
                            $result[$list_andis]["streamSettings"]["network"],
                            $result[$list_andis]["port"]
                        );
                    $list_andis++;
                }
            }

            if(count($result) == 1) return $result[0];
            elseif($result == 0) return [];
            else return $result;
        }

        return $list;
    }

    public function url(string $type, string $guid, string $remark, string $network, int $port) : string
    {
        switch ($type)
        {
            case "vmess":
                $vmess_url = "vmess://";
                $path = $network == "ws" ? "/" : "";
                $vmess_settings = [
                    "v" => "2",
                    "ps" => $remark,
                    "add" => $this->address,
                    "port" => $port,
                    "id" => $guid,
                    "aid" => 0,
                    "net" => $network,
                    "type" => "none",
                    "host" => "",
                    "path" => $path,
                    "tls" => "none"
                ];
                $vmess_base = base64_encode(json_encode($vmess_settings));
                return $vmess_url.$vmess_base;

            case "vless":
                $vless_url = "vless://$guid";
                $vless_url .= "@$this->address:$port";
                $vless_url .= "?type=$network&security=none&path=/";
                $vless_url .= "#$remark";
                return $vless_url;

            default:return "Error, url could not be created";
        }
    }
}
