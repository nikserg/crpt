<?php
namespace nikserg\crpt;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use nikserg\cryptoprocli\CryptoProCli;
use yii\db\Exception;

class CRPT {
    private $test;
    private $jwt;
    private $httpClient;
    private $certificateThumbprint;

    /**
     * CRPT constructor.
     *
     * @param string $certificateThumbprint SHA1-отпечаток сертификата для входа в ЦРПТ. Пример: 2b0b6f44b3507b509aaa0b2d38dc4819a557a40b
     * @param bool $test Отправлять запросы на тестовый контур ЦРПТ
     */
    function __construct($certificateThumbprint, $test = true)
    {
        $this->test = $test;
        $this->certificateThumbprint = $certificateThumbprint;
        $this->httpClient = new Client();
    }

    /**
     * Домен для запросов к ЦРПТ
     *
     *
     * @return string
     */
    private function getCRPTDomain()
    {
        if ($this->test) {
            return 'https://demo.fashion.crpt.ru/api/v3/';
        }
        return 'https://ismp.crpt.ru/api/v3/';
    }

    public function auth()
    {
        //Уже авторизированы
        if ($this->jwt) {
            return true;
        }

        //Получаем данные для подписи
        $key = @json_decode($this->httpClient->get($this->getCRPTDomain() . 'auth/cert/key/')->getBody(), true);

        //Подписываем
        $signed = CryptoProCli::signData($key['data'], $this->certificateThumbprint);
        $signed = str_replace("\n", '', $signed);

        /*$json = json_encode([ 'uuid' => $key['uuid'],
                              'data' => $signed,]);
        die($json);*/
        //Отправляем подписанное

            $jwt = $this->httpClient->post($this->getCRPTDomain() . 'auth/cert/', [
                RequestOptions::JSON => [
                    'uuid' => $key['uuid'],
                    'data' => $signed,
                ],
                'curl' => [
                    CURLOPT_VERBOSE => true,
                ],
                'debug'              => true
            ]);



        print_r($jwt);exit;

    }
}
