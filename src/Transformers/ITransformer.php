<?php
namespace TwinePM\Transformers;

use TwinePM\Responses;
interface ITransformer {
    public static function transform(
        $value,
        array $context = null): Responses\IResponse;  
}