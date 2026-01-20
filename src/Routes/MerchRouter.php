<?php namespace App\Routes;

use App\Lib\Router;
use App\Lib\Request;
use App\Lib\Response;
use App\Middleware\AuthMiddleware;
use App\Middleware\Validator;
use App\Controllers\MerchController;

$merchRouter = new Router();

// GET

$merchRouter->get('/', [MerchController::class, 'getAllItems'], [[AuthMiddleware::class, 'authMiddleware']]);

$merchRouter->get('/:id', [MerchController::class, 'getItem']);

// POST

$validateMerchPOST = [
    Validator::body('name')->notEmpty()->withMessage('Name is required')
        ->isLength(['max' => 50])->withMessage('Name must not exceed 50 characters'),
    Validator::body('category')->notEmpty()->withMessage('Category is required')
        ->isLength(['max' => 50])->withMessage('Category must not exceed 50 characters'),
    Validator::body('stock')->notEmpty()->withMessage('Stock is required')
        ->isInt(['min' => 0])->withMessage('Stock must be a positive integer')
        ->isLength(['max' => 3])->withMessage('Stock must not be any larger than 999'),
    Validator::body('price')->notEmpty()->withMessage('Price is required')
        ->isNumeric(['min' => 0, 'max' => 999])->withMessage('Price must be a positive number no larger than $999'),
    Validator::body('description')->notEmpty()->withMessage('Description is required')
        ->isLength(['max' => 500])->withMessage('Description must not exceed 250 characters'),
    Validator::body('images')->isStringArray()->withMessage('Images must be an array with only strings')
];

$merchRouter->post('/', [MerchController::class, 'insertItem'], [
    [AuthMiddleware::class, 'strictAuthMiddleware'], 
    AuthMiddleware::checkRole(['webmaster', 'treasurer']),
    Validator::validate($validateMerchPOST)
]);

// REORDER

$validateMerchReorder = [
    Validator::body('items')->notEmpty()->withMessage("New order of items must be defined in array 'items'")
        ->isArray()->withMessage('Items should be an array'),
    Validator::body('items.*.id')->notEmpty()->withMessage("Items must all have 'id' defined")
        ->isInt(['min' => 0])->withMessage('All item ids must be positive integers'),
    Validator::body('items.*.position')->notEmpty()->withMessage("Items must all have 'position' defined")
        ->isInt(['min' => 0])->withMessage('All item positions must be positive integers'),
];

$merchRouter->put('/reorder', [MerchController::class, 'reorderItems'], [
    [AuthMiddleware::class, 'strictAuthMiddleware'],
    AuthMiddleware::checkRole(['webmaster', 'treasurer']),
    Validator::validate($validateMerchReorder)
]);

// PUT

$validateMerchPUT = [
    Validator::body('name')->isLength(['max' => 50])->withMessage('Name must not exceed 50 characters'),
    Validator::body('category')->isLength(['max' => 50])->withMessage('Category must not exceed 50 characters'),
    Validator::body('stock')->isInt(['min' => 0])->withMessage('Stock must be a positive integer')
        ->isLength(['max' => 3])->withMessage('Stock must not be any larger than 999'),
    Validator::body('price')->isNumeric(['min' => 0, 'max' => 999])->withMessage('Price must be a positive number no larger than $999'),
    Validator::body('description')->isLength(['max' => 500])->withMessage('Description must not exceed 250 characters')
];

$merchRouter->put('/:id', [MerchController::class, 'updateItem'], [
    [AuthMiddleware::class, 'strictAuthMiddleware'],
    AuthMiddleware::checkRole(['webmaster', 'treasurer']),
    Validator::validate($validateMerchPUT)
]);

// DELETE

$merchRouter->delete('/:id', [MerchController::class, 'deleteItem'], [
    [AuthMiddleware::class, 'strictAuthMiddleware'],
    AuthMiddleware::checkRole(['webmaster', 'treasurer'])
]);

return $merchRouter;