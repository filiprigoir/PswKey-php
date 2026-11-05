<?php
declare(strict_types=1);

namespace PswKey\Service;

use PswKey\Core\Modifier\DerivationProfile;
use PswKey\ErrorMessage\ClientMessage;
use PswKey\ErrorMessage\InternalMessage;
use PswKey\Exception\ConfigurationException;
use PswKey\Exception\InputException;
use PswKey\Util\Base\Precompute;
use PswKey\Util\Mapping\Merge;
use PswKey\Util\Secure\MemeZero;

/**
 * Deterministic Format-Preserving Encryption via a Balanced Feistel Network
 * 
 * For more information about the availability see: src/Core/Documentation/Conversion/Permutation.md
 */
final class Permutation {

    //Key Stream provider
    private KeyStream $_keyStream;

    //MainKey
    private string $_secretKey;

    //Default configuration with Number length of 8 (32-bit & 64-bit):
    private int $_maxID = 99999999;
    private int $_halfShift = 14;
    private int $_halfMask = 0x3FFF;
    private int $_rounds = 4;

    public function __construct(KeyStream $keyStream) {
        $this->_keyStream = $keyStream;
        $this->getKey(10);
    }

    /**
     * A number between 4 and 18 must be provided (ie.: 8 becomes ID => 1 until 99999999)
     */
    public function maxLength(int $numberLength) : self {
        if($numberLength < 4 || $numberLength > 18) {
            throw new ConfigurationException(
                Merge::string(InternalMessage::LENGTH_REQUIRED, 
                ['%required%' => 'a number between 4 and 18'] ) . "/" . ClientMessage::INVALID_INPUT
            );
        }

        if(strlen((string)$this->_maxID) != $numberLength ) {
            $this->getKey($numberLength);
            [$this->_halfShift, $this->_halfMask, $this->_maxID] = Precompute::initPermutation($numberLength);            
        }

        return $this;
    }

    public function rounds(int $rounds) : self {
        if($rounds >= 4 && $rounds <= 24) $this->_rounds = $rounds;
        return $this;
    }
    
    private function getKey(int $length) : void {
        $this->_keyStream->derivedKey(
            function($secretKey) {
               $this->_secretKey = $secretKey;
            }, 
            32, 
            DerivationProfile::getContextPermutation($length)
        );
    }

    private function pseudoRandom(int $nb, int $round): int {
        $data = pack("NN", $nb, $round);
        $hash = hash_hmac("sha256", $data, $this->_secretKey, true);
        $value = unpack("N", substr($hash, 0, 4))[1];
        return $value & $this->_halfMask;
    }

    public function encode(int $originalId) : int {

        if($originalId < 0 || $originalId > $this->_maxID) {
            throw new InputException(
                Merge::string(InternalMessage::OUT_OF_RANGE, 
                ['%arg%' => 'originalId'] ) . "/" . ClientMessage::INVALID_INPUT
            );
        }

        $permutedId = $originalId;
        //Format-Preserving Encryption (FPE) via Cycle-Walking
        do {
            //Split in bits
            $l = ($permutedId >> $this->_halfShift) & $this->_halfMask;
            $r = $permutedId & $this->_halfMask;

            //4 rounds to avoid bias
            for ($round = 0; $round < $this->_rounds; $round++) {
                $nextL = $r;
                $nextR = $l ^ $this->pseudoRandom($r, $round);
                $l = $nextL;
                $r = $nextR;
            }

            $permutedId = ($l << $this->_halfShift) | $r;

        } while($permutedId > $this->_maxID);

        return $permutedId;
    }

    public function decode(int $permutedId) : int {

        if ($permutedId < 0 || $permutedId > $this->_maxID) {
            throw new InputException(
                Merge::string(InternalMessage::OUT_OF_RANGE, 
                ['%arg%' => 'permutedId'] ) . "/" . ClientMessage::INVALID_INPUT
            );
        }

        $originalId = $permutedId;
        //Format-Preserving Encryption (FPE) via Cycle-Walking
        do {
            $l = ($originalId >> $this->_halfShift) & $this->_halfMask;
            $r = $originalId & $this->_halfMask;

            for ($round = ($this->_rounds-1); $round >= 0; $round--) {
                $prevR = $l;
                $prevL = $r ^ $this->pseudoRandom($l, $round);
                $l = $prevL;
                $r = $prevR;
            }

            $originalId = ($l << $this->_halfShift) | $r;

        } while($originalId > $this->_maxID);

        return $originalId;
    }

    public function __destruct() {
        MemeZero::overwriteString($this->_secretKey);
        unset($this->_secretKey);
    }

    public function __debugInfo(): array {
        return ['key' => '*** hidden ***'];
    }
}