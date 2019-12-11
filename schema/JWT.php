<?php
namespace nikserg\crpt\schema;

use Lcobucci\JWT\Parser;
use yii\base\Component;

/**
 * Токен авторизации
 *
 * @package nikserg\crpt\schema
 */
class JWT extends Component {

    /**
     * @var string Статус пользователя ACTIVE
     */
    public $userStatus;
    /**
     * @var string ИНН
     */
    public $inn;
    public $fullName;
    public $validToTimestamp;
    public $organizationStatus;
    public $token;

    /**
     * Парсинг токена
     *
     *
     * @param $tokenString
     * @return JWT
     */
    public static function parse($tokenString)
    {
        $token = (new Parser())->parse((string) $tokenString);
        return new JWT([
            'userStatus' => $token->getClaim('user_status'),
            'inn' => $token->getClaim('inn'),
            'fullName' => $token->getClaim('full_name'),
            'validToTimestamp' => $token->getClaim('exp'),
            'organizationStatus' => $token->getClaim('organisation_status'),
            'token' => $tokenString
        ]);
    }

    /**
     * До какого времени действует токен
     *
     *
     * @return \DateTime
     */
    public function getValidTo()
    {
        return new \DateTime('@'.$this->validToTimestamp);
    }

    /**
     * Действует ли еще токен
     *
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->getValidTo()->getTimestamp() > time();
    }
}
