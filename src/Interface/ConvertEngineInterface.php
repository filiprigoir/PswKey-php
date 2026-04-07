<?php
declare(strict_types=1);

namespace PswKey\Interface;

interface ConvertEngineInterface {
    function gmpEnable(bool $useGMP) : self;
    function longEndianChunk(bool $longEndianChunk) : self; 
}