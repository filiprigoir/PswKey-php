<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use FFI;
use DateTime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Service\PswKey;

class FallbackTest extends TestCase
{
    private function getKeyStream(string $seedPhrase, $date) : KeyStream {
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');
        return new KeyStream($seedPhrase, $key);
    }

    public function test_disabledFFI(): void
    {
        $date = new DateTime();
        $text = "Library for deterministic validation of a context-bound generated pswkey";
        $pswkey = new DisabledFFI(
            $this->getKeyStream("deterministic validation", $date)
        );

        $encode = $pswkey
            ->from(256)
            ->to(64)
            ->convert($text);

        //is output not empty? Yes!
        $this->assertNotNull($encode);

        $this->assertEquals(
            'PHP',
            $pswkey->usage
        );
    }

    public function test_missConfigFFI(): void
    {
        $date = new DateTime();
        $text = "Library for deterministic validation of a context-bound generated pswkey";
        $pswkey = new MissConfigFFI(
            $this->getKeyStream("deterministic validation", $date)
        );

        $encode = $pswkey
            ->from(256)
            ->to(64)
            ->convert($text);

        //is output not empty? Yes!
        $this->assertNotNull($encode);

        $this->assertEquals(
            'PHP_FALLBACK',
            $pswkey->usage
        );
    }

    public function test_regressionProof(): void
    {
        $date = new DateTime();
        $text = "Library for deterministic validation of a context-bound generated pswkey";
        $pswkey1 = new pswKey(
            $this->getKeyStream("deterministic validation", $date)
        );

        $enabledFFI = $pswkey1
            ->from(256)
            ->to(64)
            ->convert($text);

        //is output not empty? Yes!
        $this->assertNotNull($enabledFFI);

        if(ini_get('ffi.enable')) {
            $this->assertEquals(
                'FFI',
               \strtoupper($pswkey1->usage)
            );
        }

        $pswkey2 = new DisabledFFI(
            $this->getKeyStream("deterministic validation", $date)
        );

        $disabledFFI = $pswkey2
            ->from(256)
            ->to(64)
            ->convert($text);

        //is output not empty? Yes!
        $this->assertNotNull($disabledFFI);

        $this->assertEquals(
            'PHP',
            \strtoupper($pswkey2->usage)
        );

        //Generates FFI and pure PHP the same results? Yes!
        $this->assertEquals(
            $enabledFFI,
            $disabledFFI
        );
    }
}

//Inherits from Pswkey and force FFI disable
class DisabledFFI extends PswKey {
    
    public function __construct(KeyStream $keyStream) {
        parent::__construct($keyStream);
    }

    protected function setAvailability() : void {
       $this->_ffi = null;
    }
}

//Inherits from Pswkey and force bad FFI configuration: "Components" in path must be "Component"
class MissConfigFFI extends PswKey {
    
    public function __construct(KeyStream $keyStream) {
        parent::__construct($keyStream);
    }

    protected function setAvailability() : void {
        /**
         * Optional feature checks:
         * If you are certain that GMP, Libsodium, and/or FFI are available,
         * you may comment out this section and manually set the corresponding
         * properties to true (FFI set equal to using extension or disable).
         */
        $ffi = ini_get('ffi.enable'); //set enable or disable
        if (class_exists('FFI') && $ffi !== false && $ffi !== '0') {
            //Setup configuration
            $extension = match(PHP_OS_FAMILY) {
                'Windows' => 'dll',   //Win
                'Darwin'  => 'dylib', // macOS
                default   => 'so',    // Linux en other Unix-systems
            };

            //Load C library
            try {
                $this->_ffi = FFI::cdef("
                    int shuffle_indices_secure(
                        const uint8_t *rand_bytes, size_t rand_len,
                        size_t input_len,
                        size_t required_len,
                        uint8_t *out_array
                    );
                ", __DIR__ . "/Components/FFI/Compiled/shuffleindice.{$extension}"); //path is wrong on purpose
            } catch (FFI\Exception) {
                $this->_ffi = null;
                $this->usage = "PHP_FALLBACK";
            }
        }

        $this->_gmp = function_exists('gmp_init');
    }
}