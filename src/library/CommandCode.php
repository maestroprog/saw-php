<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 02.12.2016
 * Time: 0:51
 */

namespace maestroprog\saw\library;


trait CommandCode
{
    /**
     * @var int
     */
    private $code;

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->code === Command::RES_SUCCESS;
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->code === Command::RES_ERROR;
    }
}