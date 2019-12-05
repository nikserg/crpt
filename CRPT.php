<?php
namespace nikserg\crpt;

class CRPT {
    private $test;
    private $jwt;
    private $httpClient;

    /**
     * CRPT constructor.
     *
     * @param bool $test Отправлять запросы на тестовый контур ЦРПТ
     */
    function __construct($test = true)
    {
        $this->test = $test;
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
    }
}
