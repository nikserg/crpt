<?php

namespace nikserg\crpt;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\RequestException;
use nikserg\crpt\exception\NotAuthException;
use nikserg\crpt\exception\TokenExpiredException;
use nikserg\crpt\schema\AuthData;
use nikserg\crpt\schema\CodeInfo;
use nikserg\crpt\schema\JWT;
use nikserg\cryptoprocli\CryptoProCli;
use yii\db\Exception;

class CRPT
{
    private $test;
    /**
     * @var JWT
     */
    private $jwt;
    private $httpClient;

    /**
     * CRPT constructor.
     *
     * @param string $certificateThumbprint SHA1-отпечаток сертификата для входа в ЦРПТ. Пример:
     *     2b0b6f44b3507b509aaa0b2d38dc4819a557a40b
     * @param bool   $test Отправлять запросы на тестовый контур ЦРПТ
     */
    function __construct($test = true)
    {
        $this->test = $test;
        $this->httpClient = new Client();
    }

    /**
     * Получить JWT-токен в ЦРПТ
     *
     *
     * @param string $certificateThumbprint SHA1-отпечаток сертификата для входа в ЦРПТ. Пример:
     *     2b0b6f44b3507b509aaa0b2d38dc4819a557a40b
     * @throws \Exception
     * @return JWT
     */
    public function getJwt($certificateThumbprint)
    {
        //Получаем данные для подписи
        $authData = $this->getAuthData();

        //Подписываем
        $signed = CryptoProCli::signData($authData->data, $certificateThumbprint);
        $signed = str_replace("\n", '', $signed);

        return $this->checkAuthData($authData->uuid, $signed);
    }

    /**
     * @return AuthData
     * @throws \Exception
     */
    public function getAuthData() {

        $data = @json_decode($this->httpClient->get($this->getCRPTDomain() . 'auth/cert/key/')->getBody(), true);
        if (!$data || !isset($data['data'])) {
            throw new \Exception('Невозможно получить данные для подписи для аутентификации в ЦРПТ');
        }
        return new AuthData($data);
    }


    /**
     * Проверить подписанные данные для авторизации
     *
     * @param string $uuid
     * @param string $signedData
     * @return JWT
     * @throws \Exception
     */
    public function checkAuthData($uuid, $signedData)
    {
        //Отправляем подписанные данные для JWT-токена
        try {
            $jwt = @json_decode($this->httpClient->post($this->getCRPTDomain() . 'auth/cert/', [
                RequestOptions::JSON => [
                    'uuid' => $uuid,
                    'data' => $signedData,
                ],
            ])->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new \Exception('Ошибка ответа от сервера ЦРПТ: '.$e->getResponse()->getBody(), 500, $e);
        }
        if (!$jwt || !isset($jwt['token'])) {
            throw new \Exception('Невозможно получить JWT-токен в ЦРПТ с использованием указанной подписи');
        }

        //Парсим JWT
        return JWT::parse($jwt['token']);

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

    /**
     * Авторизация на сервере
     *
     * @param JWT $jwt
     * @throws TokenExpiredException
     */
    public function auth($jwt)
    {
        if (!$jwt->isValid()) {
            throw new TokenExpiredException('JWT-токен валиден только до '.$jwt->getValidTo()->format('d.m.Y H:i:s'));
        }
        $this->jwt = $jwt;
    }

    /**
     * Получить информацию о коде
     *
     * @param $code
     * @return CodeInfo|null
     * @throws NotAuthException
     */
    public function getCodeInfo($code)
    {
        $this->checkJwt();
        $code = substr($code, 0, 31);
        $info = @json_decode($this->httpClient->get($this->getCRPTDomain() . 'facade/identifytools/' . $code, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->jwt->token,
            ],
        ])->getBody()->getContents(), true);

        if (!$info) {
            return null;
        }
        $return = new CodeInfo();
        foreach ($info as $key => $value) {
            $return->{$key} = $value;
        }
        return $return;
    }

    private function checkJwt()
    {
        if (!$this->jwt) {
            throw new NotAuthException();
        }
        if (!$this->jwt->isValid()) {
            throw new TokenExpiredException();
        }
    }
}
