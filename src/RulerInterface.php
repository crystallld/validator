<?php
namespace Validator;

interface RulerInterface
{
    /**
     * @return array
     */
    public function attributes();

    /**
     * @return array
     */
    public function scenarios();

    /**
     * @return array
     */
    public function rules();

    /**
     * @param $attribute
     * @return boolean
     */
    public function hasAttribute($attribute);

    /**
     * @return string
     */
    public function getScenario();

    /**
     * @param $scenario
     * @return mixed
     */
    public function setScenario($scenario);


    /**
     * @return boolean
     */
    public function validate();
}