<?php
use PHPUnit\Framework\TestCase;
use Brain\Monkey;

require_once __DIR__ . "/../../src/delete/package.php";

class mockPDO extends PDO
{
    public function __construct () {}
    public function setAttribute($attribute, $value) {}
}

/**
 * @covers deletePackage
 */
final class Testing extends TestCase {
    protected function tearDown() {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testInvalidIdError() {
        Monkey\Functions\when("argumentToId")->justReturn(-1);

        $result = deletePackage([]);
        $this->assertEquals($result, [
            "status" => 400,
            "error" => "The id argument could not be casted to a " .
                "positive integer.",
        ]);
    }

    public function testGetTokens() {
        Monkey\Functions\when("argumentToId")->justReturn(0);

        $pdoStub = new mockPDO();
        Monkey\Functions\when("getTwinepmDatabase")->justReturn($pdoStub);
        Monkey\Functions\expect("getTokens")->once()->andReturn([]);

        Monkey\Functions\when("tokensToUserdata")
            ->justReturn([ "status" => 425 ]);

        $result = deletePackage([]);
        $this->assertEquals($result, [
            "status" => 425,
            "error" => "The status received from tokensToUserdata in delete/package was not 200, but no error message was included.",
        ]);
    }
}
?>