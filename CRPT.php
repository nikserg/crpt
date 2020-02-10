<?php

namespace nikserg\crpt;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
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
     * @throws \Exception
     * @throws NotAuthException
     */
    public function getCodeInfo($code)
    {
        $this->checkJwt();
        $code = substr($code, 0, 31);

        if (strlen($code) < 31)  {
            throw new \Exception('Код после обрезки при получении информации из ЦРПТ слишком короткий: `'.$code.'`', 500);
        }
        try {
            $info = @json_decode($this->httpClient->get($this->getCRPTDomain() . 'facade/identifytools/' . urlencode($code),
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->jwt->token,
                    ],
                ])->getBody()->getContents(), true);
        } catch (ClientException $e) {
            if ($e->getCode() == 404 || $e->getCode() == 400) {
                //Пробуем через другой адрес

                $jsonData = $this->httpClient->get($this->getCRPTDomain() . 'facade/cis/cis_list?cis=&cis=' . urlencode($code),
                    [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->jwt->token,
                        ],
                    ])->getBody()->getContents();
                $info = @json_decode($jsonData, true);
                if ($info) {
                    if (!isset($info[$code])) {
                        throw new \Exception('Ошибка при получении информации о коде из альтернативного источника. Получен ответ '.print_r($info), 500, $e);
                    }
                    $info = $info[$code];
                    $info['emissionDate'] = (new \DateTime('@'.substr($info['emissionDate'], 0, strlen($info['emissionDate'])-3)))->format('Y-m-dTH:i:s.vZ');
                } else {
                    throw new \Exception('Не получается расшифровать ответ от ЦРПТ. Ожидается JSON, получен ответ '.$jsonData, 500, $e);
                }
            } else {
                throw $e;
            }
        }

        if (!$info) {
            return null;
        }
        $return = new CodeInfo();
        foreach ($info as $key => $value) {
            $return->{$key} = $value;
        }
        return $return;
    }

    /**
     * @throws NotAuthException
     * @throws TokenExpiredException
     */
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
