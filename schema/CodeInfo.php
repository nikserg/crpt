<?php
namespace nikserg\crpt\schema;

/**
 * Информация о коде маркировки
 *
 * @package nikserg\crpt\schema
 */
class CodeInfo {
    /**
     * Код маркировки
     *
     *
     * @var string
     */
    public $uit;

    /**
     * Уникальный идентификатор (допускается как полное совпадение, так и частичное)
     *
     * @var string
     */
    public $cis;

    /**
     * Global Trade Item Number
     *
     * @var string
     */
    public $gtin;

    /**
     * @var string
     */
    public $sgtin;

    /**
     * Наименование продукции
     *
     * @var string
     */
    public $productName;
    /**
     * Дата эмиссии, от. Задается в формате yyyy-MM-dd'T'HH:mm:ss.SSS'Z
     * Пример: 2019-01-01T03:00:00.000Z
     *
     * @var string
     */
    public $emissionDate;
    /**
     * @var string
     */
    public $participantName;
    /**
     * @var string
     */
    public $participantInn;


    /**
     * Наименование собственника товара
     *
     * @var string
     */
    public $ownerName;

    /**
     * ИНН производителя
     *
     *
     * @var string
     */
    public $producerInn;

    /**
     * ИНН собственника товара
     *
     * @var string
     */
    public $ownerInn;

    /**
     * @var string
     */
    public $lastDocId;
    /**
     * Тип производства (LOCAL - производство РФ, FOREIGN - ввезен в РФ)
     *
     * @var string
     */
    public $emissionType;

    /**
     * @var string[]
     */
    public $prevCises;
    /**
     * @var string[]
     */
    public $nextCises;
    /**
     * @var string
     */
    public $status;
    /**
     * @var string
     */
    public $packType;
    /**
     * @var int
     */
    public $countChildren;

    /**
     * ИНН владельца или производителя
     *
     *
     * @return bool
     */
    public function getInn()
    {
        return $this->ownerInn or $this->producerInn;
    }
}
