<?php namespace App\Lib;

class DBConnect {
	private static $db;

	public static function getDB() {
		if(!self::$db)
			self::$db = self::createDB();
		
		return self::$db;
	}

	public static function createDB() {
		try {
			$conn = new \PDO('mysql:host=' . $_ENV['DB_HOST'] .';dbname=' . $_ENV['DB_NAME'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
			$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			return $conn;
		} catch (\Exception $e) {
			echo "Database Error: " . $e->getMessage();
		}
	}
}