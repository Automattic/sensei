<?php
/**
 * ParameterShadowSuperGlobalsSniffTest
 *
 * @package PHPCompatibility
 */


/**
 * ParameterShadowSuperGlobalsSniffTest
 *
 * @uses    BaseSniffTest
 * @package PHPCompatibility
 * @author  Juliette Reinders Folmer <phpcompatibility_nospam@adviesenzo.nl>
 */
class ParameterShadowSuperGlobalsSniffTest extends BaseSniffTest
{

    const TEST_FILE = 'sniff-examples/parameter_shadow_superglobals.php';

    /**
     * testParameterShadowSuperGlobals
     *
     * @group parameterShadowSuperGlobals
     *
     * @dataProvider dataParameterShadowSuperGlobals
     *
     * @param string $superglobal Parameter name.
     * @param int    $line        Line number where the error should occur.
     *
     * @return void
     */
    public function testParameterShadowSuperGlobal($superglobal, $line)
    {

        $file = $this->sniffFile(self::TEST_FILE, '5.3');
        $this->assertNoViolation($file, $line);


        $file = $this->sniffFile(self::TEST_FILE, '5.4');
        $this->assertError($file, $line, "Parameter shadowing super global ({$superglobal}) causes fatal error since PHP 5.4");
    }

    /**
     * dataParameterShadowSuperGlobals
     *
     * @see testParameterShadowSuperGlobals()
     *
     * @return array
     */
    public function dataParameterShadowSuperGlobals() {
        return array(
            array('$GLOBALS', 4),
            array('$_SERVER', 5),
            array('$_GET', 6),
            array('$_POST', 7),
            array('$_FILES', 8),
            array('$_COOKIE', 9),
            array('$_SESSION', 10),
            array('$_REQUEST', 11),
            array('$_ENV', 12),
        );
    }


    /**
     * testValidParameter
     *
     * @group parameterShadowSuperGlobals
     *
     * @dataProvider dataValidParameter
     *
     * @param int $line The line number.
     *
     * @return void
     */
    public function testValidParameter($line)
    {
        $file = $this->sniffFile(self::TEST_FILE);
        $this->assertNoViolation($file, $line);
    }

    /**
     * Data provider.
     *
     * @see testValidParameter()
     *
     * @return array
     */
    public function dataValidParameter()
    {
        return array(
            array(15),
            array(16),
            array(17),
        );
    }

}
