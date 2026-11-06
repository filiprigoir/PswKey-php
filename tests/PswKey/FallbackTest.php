<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use DateTime;
use PHPUnit\Framework\TestCase;
use PswKey\Core\Component\FFI\InstanceFFI;
use PswKey\Core\Modifier\ImplementationType;
use PswKey\Service\KeyStream;
use PswKey\Service\PswKey;

class FallbackTest extends TestCase
{
    private function getKeyStream(string $seedPhrase, $date) : KeyStream {
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');
        return new KeyStream($seedPhrase, $key);
    }

    public function test_FFI_disabled(): void 
    {
        InstanceFFI::Enabled(false); //default is dissabled too

        if(!InstanceFFI::getState()) {
            $date = new DateTime();
            $pswkey = new PswKey(
                $this->getKeyStream("deterministic validation", $date)
            );

            $this->assertEquals(
                ImplementationType::PHP,
                $pswkey->implementation
            );
        }
    }

    public function test_FFI_enabled(): void 
    {
        InstanceFFI::Enabled(true);

        $date = new DateTime();
        $pswkey = new Pswkey(
            $this->getKeyStream("deterministic validation", $date)
        );

        $ffi = ini_get('ffi.enable');

        $state = InstanceFFI::getState();
        if($state) {
            //If FFI is set to 'true' in php.ini, it uses FFI
            $this->assertEquals(
                ImplementationType::FFI,
                $pswkey->implementation
            );   
        }
        elseif($ffi === 'preload') {
            //If FFI is set to 'preload' in php.ini, the 'if' check above succeeds; otherwise, it falls back to PHP
            //Also when FFI is commented out, it passes here (see -> PHP-documentation FFI)
            $this->assertEquals(
                ImplementationType::FALLBACK,
                $pswkey->implementation
            );
        }
        else {
            //If php.ini FFI is set to false, it uses PHP
            $this->assertEquals(
                ImplementationType::PHP,
                $pswkey->implementation
            );            
        }
    }
}