<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require __DIR__ . "/../Server/htdocs/AppController/commands_RSM/utilities/RSMitemsManagement.php";
// require __DIR__ . "/../Server/htdocs/AppController/commands_RSM/utilities/RSdatabase.php";
require __DIR__ . "/../Server/htdocs/AppController/commands_RSM/database/RSdatabase.php";
require __DIR__ . "/../Server/htdocs/AppController/commands_RSM/utilities_2/RSproperty.php";
require __DIR__ . "/../Server/htdocs/AppController/commands_RSM/utilities_2/RSclientProperty.php";

final class PropertiesTest extends TestCase {
    protected $db;

    protected function setUp(): void {
        global $db;
        $this->db = $db;
        $this->db->connect();
    }

    public function testParsePidWithNumericalPropertyId() {
        $result = parsePID(710, 1);
        $this->assertEquals(710, $result);
    }

    public function testParseItidWithNumericalItemTypeId() {
        $result = parseITID(42, 1);
        $this->assertEquals(42, $result);
    }

    /* public function testPropertyIsAnIdentifier() {
        $result =  isIdentifier(19, 1, 'identifier');
        $this->assertTrue($result);
    } */

    public function testPropertyIsAnIdentifier() {
        $dbMock = $this->getMockBuilder(Database::class)
            ->onlyMethods(['getPropertyType'])
            ->getMock();

        $mockProperty = 'identifier';
        $dbMock->method('getPropertyType')->willReturn($mockProperty);
        $property = new Property($dbMock);
        $this->assertTrue($property->isIdentifier(19, 1, ''));
    }

    /* public function testPropertyIsNotAnIdentifier() {
        $result =  isIdentifier(38, 1, 'longtext');
        $this->assertNotTrue($result);
    } */

    public function testPropertyIsNotAnIdentifier() {
        $dbMock = $this->getMockBuilder(Database::class)
            ->onlyMethods(['getPropertyType'])
            ->getMock();

        $mockProperty = 'longtext';
        $dbMock->method('getPropertyType')->willReturn($mockProperty);
        $property = new Property($dbMock);
        $this->assertNotTrue($property->isIdentifier(19, 1, ''));
    }

    public function testPropertyIsASingleIdentifier() {
        $property = new Property($this->db);
        $result =  $property->isSingleIdentifier('identifier');
        $this->assertTrue($result);
    }

    public function testPropertyIsNotASingleIdentifier() {
        $property = new Property($this->db);
        $result =  $property->isSingleIdentifier('identifiers');
        $this->assertNotTrue($result);
    }

    public function testPropertyIsMultiIdentifier() {
        $property = new Property($this->db);
        $result =  $property->isMultiIdentifier('identifiers');
        $this->assertTrue($result);
    }

    public function testPropertyIsNotMultiIdentifier() {
        $property = new Property($this->db);
        $result =  $property->isMultiIdentifier('identifier');
        $this->assertNotTrue($result);
    }

    public function testPropertyIsIdentifierToItemtype() {
        $property = new Property($this->db);
        $result =  $property->isIdentifier2itemtype('identifier2itemtype');
        $this->assertTrue($result);
    }

    public function testPropertyIsNotIdentifierToItemtype() {
        $property = new Property($this->db);
        $result =  $property->isIdentifier2itemtype('identifier2property');
        $this->assertNotTrue($result);
    }

    public function testPropertyIsIdentifierToProperty() {
        $property = new Property($this->db);
        $result =  $property->isIdentifier2property('identifier2property');
        $this->assertTrue($result);
    }

    public function testPropertyIsNotIdentifierToProperty() {
        $property = new Property($this->db);
        $result =  $property->isIdentifier2property('identifier2itemtype');
        $this->assertNotTrue($result);
    }
    
}
