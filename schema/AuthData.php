<?php
namespace nikserg\crpt\schema;

use Lcobucci\JWT\Parser;
use yii\base\Component;

/**
 * Данные для авторизации
 *
 * @package nikserg\crpt\schema
 */
class AuthData extends Component {

    /**
     * @var string Строка из нескольких байт, которую нужно подписать
     */
    public $data;

    /**
     * @var string Идентификатор запроса, который нужно вернуть
     */
    public $uuid;
}
