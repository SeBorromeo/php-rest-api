<?php namespace App\Controllers;

use App\Lib\DBConnect;
use App\Lib\Request;
use App\Lib\Response;
use App\Lib\Logger;
use App\Middleware\Validator;

class MerchController {
    const TABLE_NAME = 'merch';
    const IMG_TABLE_NAME = 'merch_img';

    public static function getAllItems(Request $req, Response $res, callable $next) {
        $dbConn = DBConnect::getDB();

        if(isset($req->extraProperties['userId'])) {
            $statement = "
                SELECT m.*, JSON_ARRAYAGG(r.img_name) AS images
                FROM " . self::TABLE_NAME . " m
                LEFT JOIN " . self::IMG_TABLE_NAME . " r ON m.id = r.merch_id
                GROUP BY m.id;
            ";
        }
        else {
            $statement = "
                SELECT m.id, m.category, m.name, m.price, m.stock, m.description, m.position, JSON_ARRAYAGG(r.img_name) AS images
                FROM " . self::TABLE_NAME . " m
                LEFT JOIN " . self::IMG_TABLE_NAME . " r ON m.id = r.merch_id
                WHERE m.approved = 1 AND m.visible = 1
                GROUP BY m.id;
            ";
        }

        $statement = $dbConn->prepare($statement);
		$statement->execute();
		$result = $statement->fetchAll(\PDO::FETCH_ASSOC);
		
		foreach ($result as &$row) {
            $row["images"] = json_decode($row["images"]);
        }

        usort($result, function($a, $b) {
            return $a['position'] <=> $b['position'];
        });

		return $res->toJSON($result);
    }

    public static function getItem(Request $req, Response $res, callable $next)
    {
        $dbConn = DBConnect::getDB();

        if(isset($req->extraProperties['userId'])) {
            $statement = "
                SELECT m.*, JSON_ARRAYAGG(r.img_name) AS images
                FROM " . self::TABLE_NAME . " m
                LEFT JOIN " . self::IMG_TABLE_NAME . " r ON m.id = r.merch_id
                WHERE m.id = :id
                GROUP BY m.id;
            ";
        }
        else {
            $statement = "
                SELECT m.id, m.category, m.name, m.price, m.stock, m.description, m.position, JSON_ARRAYAGG(r.img_name) AS images
                FROM " . self::TABLE_NAME . " m
                LEFT JOIN " . self::IMG_TABLE_NAME . " r ON m.id = r.merch_id
                WHERE m.approved = 1 AND m.visible = 1 AND m.id = :id
                GROUP BY m.id;
            ";
        }

        $statement = $dbConn->prepare($statement);
        $statement->bindParam(':id', $req->params['id']);
        $statement->execute();

        if($statement->rowCount() > 0) {
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        
            foreach ($result as &$row) {
                $row["images"] = json_decode($row["images"]);
            }

            return $res->toJSON($result);
        }

        $next(new \Exception("Merch with id = " . $req->params['id'] . " could not be found", 404));
    }

    public static function insertItem(Request $req, Response $res, callable $next) {
        $errors = Validator::validationResult($req);

        if(!empty($errors)) {
            return $res->status(400)->toJSON(['error' => [
                'code' => 400,
                'message' => 'Validation Error',
                'validationErrors' => $errors
            ]]);
        }

        $dbConn = DBConnect::getDB();

        /* Insert into merch table */
        $params = ['name', 'category', 'price', 'stock', 'description', 'created_by_user_id', 'approved', 'visible'];
        $statement = DBConnect::createInsertStmt(self::TABLE_NAME, $params);

        $statement = $dbConn->prepare($statement);

        $data['visible'] = 0;
        $data['created_by_user_id'] = $req->extraProperties['userId'];

        $unspecifiedParams = [];
        foreach ($req->body as $key => $value) {
            if(!in_array($key, $params))
                $unspecifiedParams[] = $key;
            $data[$key] = $value;
        }

        if(!empty($unspecifiedParams))
            return $next(new \Exception("invalid parameters passed", 400));

        $role = $req->extraProperties['userRole'];
        if($role === 'webmaster')
            $data['approved'] = 1;
        else
            $data['approved'] = 0;

        $statement->execute($data);

        /* Insert into images table */
        $id = $dbConn->lastInsertId();

        if (isset($data['images']) && is_array($data['images'])) {
            foreach ($data['images'] as $image) {
                $statement = "
                    INSERT INTO " . self::IMG_TABLE_NAME . "
                        (merch_id, img_name, approved)
                    VALUES
                        (?, ?, ?);
                ";

                $statement = $dbConn->prepare($statement);
                $statement->execute([$id, $image, $data['approved']]);
            }
        }

        if($statement->rowCount() > 0) {
            Logger::getInstance()->info("user {$req->extraProperties['userId']} inserted item {$data['name']}");

            return $res->toJSON([
                'message' => "Merch successfully inserted",
                'data' => $data
            ]);
        }

        $next(new \Exception("Error inserting item '{$data['name']}'", 500));
    }

    public static function updateItem(Request $req, Response $res, callable $next) {
        $errors = Validator::validationResult($req);

        if(!empty($errors)) {
            return $res->status(400)->toJSON(['error' => [
                'code' => 400,
                'message' => 'Validation Error',
                'validationErrors' => $errors
            ]]);
        }
        
        $dbConn = DBConnect::getDB();

        $role = $req->extraProperties['userRole'];

        if($role !== 'webmaster' && $req->body['approved'] === 1)
            $next(new \Exception("Unauthorized access to change approval for role " . $role, 403));

        $statement = " UPDATE " . self::TABLE_NAME . " SET ";

        foreach ($req->body as $key => $value) {
            $data[$key] = $value;
            $setValues[] = "$key = :$key";
        }
        $data['id'] = $req->params['id'];

        $statement .= implode(', ', $setValues);
        $statement = rtrim($statement, ', ');
        $statement .= ' WHERE id = :id';

        $statement = $dbConn->prepare($statement);
        $statement->execute($data);

        if($statement->rowCount() > 0) {
            Logger::getInstance()->info("user {$req->extraProperties['userId']} updated item with id {$data['id']}");

            return $res->toJSON([
                'message' => "Merch with id = " . $req->params['id']. " successfully updated",
                'data' => $data
            ]);
        }

        $next(new \Exception("Merch with id = " . $req->params['id'] . " could not be found", 404));
    }

    public static function deleteItem(Request $req, Response $res, callable $next) {
        $dbConn = DBConnect::getDB();

        $statement = $dbConn->prepare("DELETE FROM " . self::TABLE_NAME . " WHERE id = :id");
		$statement->bindParam(':id', $req->params['id']);
        $statement->execute();

        if($statement->rowCount() > 0) {
            Logger::getInstance()->info("user {$req->extraProperties['userId']} deleted item with id {$req->params['id']}");

            return $res->toJSON([
                'message' => "Merch with id = " . $req->params['id'] . " successfully deleted"
            ]);
        }

        $next(new \Exception("Merch with id = " . $req->params['id'] . " could not be found", 404));
    }

    public static function reorderItems(Request $req, Response $res, callable $next) {
        $errors = Validator::validationResult($req);

        if(!empty($errors)) {
            return $res->status(400)->toJSON(['error' => [
                'code' => 400,
                'message' => 'Validation Error',
                'validationErrors' => $errors
            ]]);
        }
        
        $dbConn = DBConnect::getDB();

        $statement = "UPDATE " . self::TABLE_NAME . " SET position = CASE ";
        $ids = [];
        foreach ($req->body->items as $item) {
            $statement .= "WHEN id = :id_{$item->{'id'}} THEN :position_{$item->{'id'}} ";
            $ids[] = $item->{'id'};
        }
        $statement .= "ELSE position END";

        $statement = $dbConn->prepare($statement);
        
        foreach ($req->body->items as $item) {
            $statement->bindValue(":id_{$item->{'id'}}", $item->{'id'}, \PDO::PARAM_INT);
            $statement->bindValue(":position_{$item->{'id'}}", $item->{'position'}, \PDO::PARAM_INT);
        }

        $statement->execute();

        if ($statement->rowCount() > 0) {
            Logger::getInstance()->info("user {$req->extraProperties['userId']} updated merch order");

            return $res->toJSON([
                'message' => "Merch order successfully updated",
                'data' => $req->body->items
            ]);
        }

        $next(new \Exception("Error updating Merch order", 500));
    }
}