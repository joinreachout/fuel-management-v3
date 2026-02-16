<?php
/**
 * Response Helper
 * Standardizes JSON API responses
 */

namespace App\Core;

class Response
{
    /**
     * Send JSON success response
     *
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     * @return void
     */
    public static function success($data, int $statusCode = 200): void
    {
        self::json([
            'success' => true,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Send JSON error response
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array $errors Additional error details
     * @return void
     */
    public static function error(string $message, int $statusCode = 500, array $errors = []): void
    {
        $response = [
            'success' => false,
            'error' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        self::json($response, $statusCode);
    }

    /**
     * Send raw JSON response
     *
     * @param mixed $data Data to encode
     * @param int $statusCode HTTP status code
     * @return void
     */
    public static function json($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Handle OPTIONS preflight request
     *
     * @return void
     */
    public static function handlePreflight(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            exit;
        }
    }

    /**
     * Send validation error response
     *
     * @param array $errors Validation errors
     * @return void
     */
    public static function validationError(array $errors): void
    {
        self::error('Validation failed', 422, $errors);
    }

    /**
     * Send not found response
     *
     * @param string $message Optional custom message
     * @return void
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error($message, 404);
    }

    /**
     * Send unauthorized response
     *
     * @param string $message Optional custom message
     * @return void
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401);
    }

    /**
     * Send forbidden response
     *
     * @param string $message Optional custom message
     * @return void
     */
    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403);
    }
}
