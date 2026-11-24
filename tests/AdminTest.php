<?php
use PHPUnit\Framework\TestCase;

class AdminTest extends TestCase
{
    private $pdo;

    public function setUp(): void
    {
        $this->pdo = new PDO("mysql:host=localhost;dbname=smart_car_db", 'root', '');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function testAddCar()
    {
        // Simulate a POST request to add a new car
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['make'] = 'TestMake';
        $_POST['model'] = 'TestModel';
        $_POST['year'] = 2023;
        $_POST['price'] = 10000;
        $_POST['mileage'] = 1000;
        $_POST['fuel_type'] = 'Petrol';
        $_POST['transmission'] = 'Automatic';
        $_POST['description'] = 'Test description';
        $_POST['featured'] = 1;
        $_POST['discount_percentage'] = 10;
        $_POST['old_price'] = 11000;

        // Include the script to be tested
        include 'c:/xamppNew/htdocs/car-selling/admin/cars.php';

        // Assert that the car was added to the database
        $stmt = $this->pdo->prepare("SELECT * FROM cars WHERE make = 'TestMake' AND model = 'TestModel'");
        $stmt->execute();
        $car = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($car);
        $this->assertEquals('TestMake', $car['make']);
        $this->assertEquals('TestModel', $car['model']);
    }

    public function tearDown(): void
    {
        // Clean up the database after each test
        $stmt = $this->pdo->prepare("DELETE FROM cars WHERE make = 'TestMake'");
        $stmt->execute();
    }
}
